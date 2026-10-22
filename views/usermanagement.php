<?php

namespace views;

use models;

final class usermanagement
{
  public function browse(array $accounts): void {
    $q = strtolower(trim((string) ($_GET['q'] ?? '')));
    $roleFilter = (string) ($_GET['role'] ?? '');
    $statusFilter = (string) ($_GET['status'] ?? '');
    echo '<div class="d-flex justify-content-between align-items-center"><h1>User management</h1><div><a class="btn btn-outline-secondary" href="?m=users&a=audit">Audit trail</a> <a class="btn btn-primary" href="?m=users&a=new">Create account</a></div></div>';
    echo '<form method="get" class="form-inline mb-3"><input type="hidden" name="m" value="users"><input class="form-control mr-2" name="q" value="' . models\security::escape($q) . '" placeholder="Name or email"><select class="form-control mr-2" name="role"><option value="">All roles</option>';
    foreach ([0 => 'Normal user', 1 => 'Superuser', 2 => 'Editor'] as $value => $label) {
      echo '<option value="' . $value . '"' . ($roleFilter === (string) $value ? ' selected' : '') . '>' . $label . '</option>';
    }
    echo '</select><select class="form-control mr-2" name="status"><option value="">All statuses</option><option value="active"' . ($statusFilter === 'active' ? ' selected' : '') . '>Active</option><option value="inactive"' . ($statusFilter === 'inactive' ? ' selected' : '') . '>Inactive</option></select><button class="btn btn-secondary">Filter</button></form>';
    echo '<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Groups</th><th>Action</th></tr></thead><tbody>';
    foreach ($accounts as $account) {
      $haystack = strtolower($account->getFirstName() . ' ' . $account->getLastName() . ' ' . $account->getEmail());
      if ($q !== '' && !str_contains($haystack, $q)) { continue; }
      if ($roleFilter !== '' && (int) $roleFilter !== $account->getUserFlag()) { continue; }
      if ($statusFilter === 'active' && !$account->getIsActive()) { continue; }
      if ($statusFilter === 'inactive' && $account->getIsActive()) { continue; }
      $groups = implode(', ', array_map(static fn($group) => models\security::escape($group->getName()), $account->getGroups()));
      $email = models\security::escape($account->getEmail());
      echo '<tr><td>' . models\security::escape($account->getFirstName() . ' ' . $account->getLastName()) . '</td><td>' . $email . '</td><td>' . models\usermanagement::roleLabel($account->getUserFlag()) . '</td><td>' . ($account->getIsActive() ? 'Active' : 'Inactive') . '</td><td>' . $groups . '</td><td><a href="?m=users&a=edit&email=' . rawurlencode($account->getEmail()) . '">Manage</a></td></tr>';
    }
    echo '</tbody></table></div>';
  }

  public function form($account, array $groups, string $message = ''): void {
    $editing = $account !== null;
    $csrf = models\security::escape(models\security::csrfToken('application'));
    $email = models\security::escape($editing ? $account->getEmail() : (string) ($_POST['email'] ?? ''));
    $firstname = models\security::escape($editing ? $account->getFirstName() : (string) ($_POST['firstname'] ?? ''));
    $lastname = models\security::escape($editing ? $account->getLastName() : (string) ($_POST['lastname'] ?? ''));
    $role = $editing ? $account->getUserFlag() : (int) ($_POST['role'] ?? 0);
    $active = $editing ? $account->getIsActive() : (!isset($_POST['active']) || !empty($_POST['active']));
    $selectedGroups = $editing ? array_map(static fn($group) => (int) $group->getId(), $account->getGroups()) : array_map('intval', (array) ($_POST['groups'] ?? []));
    echo '<p><a href="?m=users">&larr; User management</a></p><h1>' . ($editing ? 'Manage account' : 'Create account') . '</h1>';
    if ($message !== '') { echo '<div class="alert alert-info" role="alert">' . models\security::escape($message) . '</div>'; }
    echo '<form method="post" action="index.php?m=users&a=' . ($editing ? 'save' : 'create') . '"><input type="hidden" name="_csrf" value="' . $csrf . '">';
    if ($editing) { echo '<input type="hidden" name="target_email" value="' . $email . '">'; }
    echo '<div class="form-group"><label for="accountEmail">Email address</label><input id="accountEmail" class="form-control" type="email" name="email" value="' . $email . '" required ' . ($editing ? 'readonly' : 'autocomplete="off"') . '></div>';
    echo '<div class="form-row"><div class="form-group col-md-6"><label for="firstName">First name</label><input id="firstName" class="form-control" name="firstname" value="' . $firstname . '" required></div><div class="form-group col-md-6"><label for="lastName">Last name</label><input id="lastName" class="form-control" name="lastname" value="' . $lastname . '" required></div></div>';
    echo '<div class="form-group"><label for="role">Role</label><select id="role" class="form-control" name="role">';
    foreach ([0 => 'Normal user', 2 => 'Editor', 1 => 'Superuser'] as $value => $label) { echo '<option value="' . $value . '"' . ($role === $value ? ' selected' : '') . '>' . $label . '</option>'; }
    echo '</select></div><fieldset><legend class="h5">Group memberships</legend>';
    foreach ($groups as $group) {
      $id = (int) $group->getId();
      echo '<div class="form-check"><input class="form-check-input" type="checkbox" name="groups[]" value="' . $id . '" id="group' . $id . '"' . (in_array($id, $selectedGroups, true) ? ' checked' : '') . '><label class="form-check-label" for="group' . $id . '">' . models\security::escape($group->getName()) . '</label></div>';
    }
    echo '</fieldset><div class="form-check my-3"><input class="form-check-input" type="checkbox" name="active" value="1" id="active"' . ($active ? ' checked' : '') . '><label class="form-check-label" for="active">Active account</label></div>';
    if (!$editing) { echo '<div class="form-group"><label for="password">Initial password</label><input id="password" class="form-control" type="password" name="password" required autocomplete="new-password"><small class="form-text text-muted">The user must change this password at first login.</small></div>'; }
    echo '<div class="form-group"><label for="currentPassword">Your current password (required for superuser-sensitive changes)</label><input id="currentPassword" class="form-control" type="password" name="current_password" autocomplete="current-password"></div><button class="btn btn-primary" type="submit">' . ($editing ? 'Save account' : 'Create account') . '</button></form>';
    if ($editing) {
      echo '<hr><h2 class="h4">Assign temporary password</h2><p>This revokes existing sessions and requires a password change at next login.</p><form method="post" action="index.php?m=users&a=resetPassword"><input type="hidden" name="_csrf" value="' . $csrf . '"><input type="hidden" name="target_email" value="' . $email . '"><div class="form-group"><label for="newPassword">New temporary password</label><input id="newPassword" class="form-control" type="password" name="new_password" required autocomplete="new-password"></div><div class="form-group"><label for="resetCurrentPassword">Your current password (required when resetting a superuser)</label><input id="resetCurrentPassword" class="form-control" type="password" name="current_password" autocomplete="current-password"></div><button class="btn btn-warning" type="submit">Assign temporary password</button></form>';
    }
  }

  public function audit(array $records): void {
    echo '<p><a href="?m=users">&larr; User management</a></p><h1>Security audit trail</h1><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Time</th><th>Actor</th><th>Target</th><th>Action</th><th>Outcome</th><th>Details</th></tr></thead><tbody>';
    foreach ($records as $record) {
      echo '<tr><td>' . models\security::escape($record['created_at']) . '</td><td>' . models\security::escape($record['actor_email']) . '</td><td>' . models\security::escape($record['target_email'] ?? '') . '</td><td>' . models\security::escape($record['action']) . '</td><td>' . models\security::escape($record['outcome']) . '</td><td><code>' . models\security::escape($record['details_json'] ?? '') . '</code></td></tr>';
    }
    echo '</tbody></table></div>';
  }
}
