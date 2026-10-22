<?php

namespace models;

use DomainException;
use PDO;
use Throwable;

final class usermanagement
{
  private const ROLES = [0, user::SUPERUSER, user::EDITOR];
  private const REAUTH_SECONDS = 300;

  public static function roleLabel(int $role): string {
    return $role === user::SUPERUSER ? 'Superuser' : ($role === user::EDITOR ? 'Editor' : 'Normal user');
  }

  public static function requireSuperuser(): user {
    $actor = users::getUser((string) ($_SESSION['user'] ?? ''));
    if (!$actor || !$actor->getIsActive() || !$actor->getSuperuser()) {
      throw new DomainException('You are not authorised to manage accounts.');
    }
    return $actor;
  }

  public static function create(array $input, ?string $currentPassword = null): user {
    $actor = self::requireSuperuser();
    $data = self::validatedAccountInput($input, true);
    if ($data['role'] === user::SUPERUSER) {
      self::requireRecentReauthentication($actor, $currentPassword, $data['email'], 'create_superuser');
    }

    $passwordHash = passwordhasher::hash($data['password']);
    $db = new database();
    $pdo = $db->getDatabaseHandle();
    $pdo->beginTransaction();
    try {
      $duplicate = $pdo->prepare('SELECT email FROM user WHERE LOWER(email)=LOWER(:email) FOR UPDATE');
      $duplicate->execute([':email' => $data['email']]);
      if ($duplicate->fetchColumn()) {
        throw new DomainException('An account with that email address already exists.');
      }
      self::validateGroups($pdo, $data['groups']);
      $statement = $pdo->prepare(
        "INSERT INTO user(email,password,salt,firstname,lastname,slip_admin,passwordAuth,isActive,superuser,must_change_password,auth_version,last_logged_in)
         VALUES(:email,:password,'',:firstname,:lastname,1,NULL,:active,:role,1,1,NULL)"
      );
      $statement->execute([
        ':email' => $data['email'], ':password' => $passwordHash,
        ':firstname' => $data['firstname'], ':lastname' => $data['lastname'],
        ':active' => $data['active'], ':role' => $data['role'],
      ]);
      self::replaceGroups($pdo, $data['email'], $data['groups']);
      securityaudit::record($actor->getEmail(), $data['email'], 'account_created', 'success', [
        'role' => $data['role'], 'active' => $data['active'], 'group_ids' => $data['groups'],
      ], $pdo);
      $pdo->commit();
    } catch (Throwable $exception) {
      if ($pdo->inTransaction()) { $pdo->rollBack(); }
      if ($exception instanceof \PDOException && (string) $exception->getCode() === '23000') {
        throw new DomainException('An account with that email address already exists.');
      }
      throw $exception;
    }
    return users::getUser($data['email']);
  }

  public static function update(string $targetEmail, array $input, ?string $currentPassword = null): user {
    $actor = self::requireSuperuser();
    $target = users::getUser($targetEmail);
    if (!$target) { throw new DomainException('The selected account does not exist.'); }
    $data = self::validatedAccountInput($input, false);
    $data['email'] = $target->getEmail();
    if (strcasecmp($actor->getEmail(), $target->getEmail()) === 0
        && ($data['role'] !== user::SUPERUSER || !$data['active'])) {
      self::rejected($actor, $target, 'self_role_or_status_change');
      throw new DomainException('Administrators cannot change their own role or disable their own account.');
    }

    $oldGroups = array_map(static fn($group) => (int) $group->getId(), $target->getGroups());
    sort($oldGroups);
    $newGroups = $data['groups'];
    $sensitiveSuperuserOperation = $target->getSuperuser()
      && ($data['role'] !== user::SUPERUSER || !$data['active'] || $oldGroups !== $newGroups);
    if ($data['role'] === user::SUPERUSER && !$target->getSuperuser()) {
      $sensitiveSuperuserOperation = true;
    }
    if ($sensitiveSuperuserOperation) {
      self::requireRecentReauthentication($actor, $currentPassword, $target->getEmail(), 'modify_superuser');
    }

    $db = new database();
    $pdo = $db->getDatabaseHandle();
    $pdo->beginTransaction();
    try {
      self::validateGroups($pdo, $newGroups);
      if ($target->getSuperuser() && $target->getIsActive()
          && ($data['role'] !== user::SUPERUSER || !$data['active'])) {
        $locked = $pdo->query('SELECT email FROM user WHERE superuser=1 AND isActive=1 FOR UPDATE')->fetchAll();
        if (count($locked) <= 1) {
          throw new DomainException('The last active superuser cannot be disabled or demoted.');
        }
      }
      $securityChanged = $target->getUserFlag() !== $data['role']
        || (int) $target->getIsActive() !== $data['active']
        || $oldGroups !== $newGroups;
      $statement = $pdo->prepare(
        'UPDATE user SET firstname=:firstname,lastname=:lastname,superuser=:role,isActive=:active,
         auth_version=auth_version+:versionIncrement WHERE email=:email'
      );
      $statement->execute([
        ':firstname' => $data['firstname'], ':lastname' => $data['lastname'],
        ':role' => $data['role'], ':active' => $data['active'],
        ':versionIncrement' => $securityChanged ? 1 : 0, ':email' => $target->getEmail(),
      ]);
      if ($oldGroups !== $newGroups) { self::replaceGroups($pdo, $target->getEmail(), $newGroups); }
      if ($target->getUserFlag() !== $data['role']) {
        securityaudit::record($actor->getEmail(), $target->getEmail(), 'role_changed', 'success', ['from' => $target->getUserFlag(), 'to' => $data['role']], $pdo);
      }
      if ((int) $target->getIsActive() !== $data['active']) {
        securityaudit::record($actor->getEmail(), $target->getEmail(), $data['active'] ? 'account_activated' : 'account_deactivated', 'success', [], $pdo);
      }
      if ($oldGroups !== $newGroups) {
        securityaudit::record($actor->getEmail(), $target->getEmail(), 'group_memberships_changed', 'success', ['from' => $oldGroups, 'to' => $newGroups], $pdo);
      }
      if ($target->getFirstName() !== $data['firstname'] || $target->getLastName() !== $data['lastname']) {
        securityaudit::record($actor->getEmail(), $target->getEmail(), 'profile_changed', 'success', [], $pdo);
      }
      $pdo->commit();
    } catch (Throwable $exception) {
      if ($pdo->inTransaction()) { $pdo->rollBack(); }
      if ($exception instanceof DomainException
          && $exception->getMessage() === 'The last active superuser cannot be disabled or demoted.') {
        self::rejected($actor, $target, 'last_active_superuser');
      }
      throw $exception;
    }
    return users::getUser($target->getEmail());
  }

  public static function resetPassword(string $targetEmail, string $newPassword, ?string $currentPassword = null): void {
    $actor = self::requireSuperuser();
    $target = users::getUser($targetEmail);
    if (!$target) { throw new DomainException('The selected account does not exist.'); }
    self::validatePassword($newPassword);
    if ($target->getSuperuser()) {
      self::requireRecentReauthentication($actor, $currentPassword, $target->getEmail(), 'reset_superuser_password');
    }
    $hash = passwordhasher::hash($newPassword);
    $db = new database();
    $pdo = $db->getDatabaseHandle();
    $pdo->beginTransaction();
    try {
      $statement = $pdo->prepare(
        "UPDATE user SET password=:password,salt='',passwordAuth=NULL,must_change_password=1,
         auth_version=auth_version+1 WHERE email=:email"
      );
      $statement->execute([':password' => $hash, ':email' => $target->getEmail()]);
      securityaudit::record($actor->getEmail(), $target->getEmail(), 'administrator_password_reset', 'success', [], $pdo);
      $pdo->commit();
    } catch (Throwable $exception) {
      if ($pdo->inTransaction()) { $pdo->rollBack(); }
      throw $exception;
    }
  }

  public static function requireRecentReauthentication(user $actor, ?string $password, ?string $target, string $action): void {
    $recent = isset($_SESSION['adminReauthenticatedAt'], $_SESSION['adminReauthenticatedUser'])
      && hash_equals($actor->getEmail(), (string) $_SESSION['adminReauthenticatedUser'])
      && time() - (int) $_SESSION['adminReauthenticatedAt'] <= self::REAUTH_SECONDS;
    if ($recent) { return; }
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $withinLimit = authratelimiter::consume('admin-reauth', $actor->getEmail() . "\0" . $ip, 5, 900);
    if (!$withinLimit || !is_string($password) || $password === '' || !$actor->checkPassword($password)) {
      securityaudit::record($actor->getEmail(), $target, 'superuser_reauthentication', 'rejected', ['operation' => $action]);
      throw new DomainException('Recent administrator password verification is required.');
    }
    $_SESSION['adminReauthenticatedAt'] = time();
    $_SESSION['adminReauthenticatedUser'] = $actor->getEmail();
    securityaudit::record($actor->getEmail(), $target, 'superuser_reauthentication', 'success', ['operation' => $action]);
  }

  private static function validatedAccountInput(array $input, bool $creating): array {
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    if ($creating && !filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new DomainException('Enter a valid email address.'); }
    $firstname = trim((string) ($input['firstname'] ?? ''));
    $lastname = trim((string) ($input['lastname'] ?? ''));
    if ($firstname === '' || $lastname === '' || strlen($firstname) > 255 || strlen($lastname) > 255) {
      throw new DomainException('First name and last name are required.');
    }
    $roleValue = $input['role'] ?? null;
    if (!is_scalar($roleValue) || !preg_match('/^[012]$/', (string) $roleValue)) { throw new DomainException('Select a valid role.'); }
    $role = (int) $roleValue;
    if (!in_array($role, self::ROLES, true)) { throw new DomainException('Select a valid role.'); }
    $groups = self::normalizeGroupIds($input['groups'] ?? []);
    $data = ['email' => $email, 'firstname' => $firstname, 'lastname' => $lastname,
      'role' => $role, 'active' => !empty($input['active']) ? 1 : 0, 'groups' => $groups];
    if ($creating) {
      $data['password'] = (string) ($input['password'] ?? '');
      self::validatePassword($data['password']);
    }
    return $data;
  }

  private static function validatePassword(string $password): void {
    if ($password === '' || strlen($password) > 1024) { throw new DomainException('Enter a valid temporary password.'); }
  }

  private static function normalizeGroupIds($values): array {
    if (!is_array($values)) { throw new DomainException('Invalid group selection.'); }
    $ids = [];
    foreach ($values as $value) {
      if (!is_scalar($value) || !ctype_digit((string) $value) || (int) $value < 1) { throw new DomainException('Invalid group selection.'); }
      $ids[] = (int) $value;
    }
    $ids = array_values(array_unique($ids));
    sort($ids);
    return $ids;
  }

  private static function validateGroups(PDO $pdo, array $ids): void {
    if (!$ids) { return; }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statement = $pdo->prepare("SELECT id FROM userGroup WHERE id IN ({$placeholders})");
    $statement->execute($ids);
    $found = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    sort($found);
    if ($found !== $ids) { throw new DomainException('One or more selected groups do not exist.'); }
  }

  private static function replaceGroups(PDO $pdo, string $email, array $ids): void {
    $existingStatement = $pdo->prepare('SELECT groupId FROM userGroupMembers WHERE userEmail=:email');
    $existingStatement->execute([':email' => $email]);
    $existing = array_map('intval', $existingStatement->fetchAll(PDO::FETCH_COLUMN));
    $remove = array_values(array_diff($existing, $ids));
    $add = array_values(array_diff($ids, $existing));
    if ($remove) {
      $placeholders = implode(',', array_fill(0, count($remove), '?'));
      $delete = $pdo->prepare("DELETE FROM userGroupMembers WHERE userEmail=? AND groupId IN ({$placeholders})");
      $delete->execute(array_merge([$email], $remove));
    }
    $insert = $pdo->prepare('INSERT INTO userGroupMembers(groupId,userEmail) VALUES(:groupId,:email)');
    foreach ($add as $id) { $insert->execute([':groupId' => $id, ':email' => $email]); }
  }

  private static function rejected(user $actor, user $target, string $reason): void {
    securityaudit::record($actor->getEmail(), $target->getEmail(), 'account_update', 'rejected', ['reason' => $reason]);
  }
}
