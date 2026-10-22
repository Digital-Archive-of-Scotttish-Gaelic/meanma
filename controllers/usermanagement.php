<?php

namespace controllers;

use DomainException;
use models;
use views;

final class usermanagement
{
  public function run($action): void {
    try {
      models\usermanagement::requireSuperuser();
    } catch (DomainException $exception) {
      http_response_code(403);
      echo '<h2>Access denied</h2>';
      return;
    }

    $view = new views\usermanagement();
    try {
      switch ($action) {
        case 'new':
          models\security::requireMethod('GET');
          $view->form(null, models\users::getAllGroups());
          return;
        case 'create':
          models\security::requireMethod('POST');
          $created = models\usermanagement::create($_POST, $_POST['current_password'] ?? null);
          if (ob_get_level() > 0) { ob_clean(); }
          header('Location: index.php?m=users&a=edit&email=' . rawurlencode($created->getEmail()) . '&created=1', true, 303);
          exit;
        case 'edit':
          models\security::requireMethod('GET');
          $account = models\users::getUser((string) ($_GET['email'] ?? ''));
          if (!$account) { throw new DomainException('The selected account does not exist.'); }
          $view->form($account, models\users::getAllGroups(), !empty($_GET['created']) ? 'Account created.' : (!empty($_GET['saved']) ? 'Account updated.' : (!empty($_GET['reset']) ? 'Temporary password assigned.' : '')));
          return;
        case 'save':
          models\security::requireMethod('POST');
          $account = models\usermanagement::update((string) ($_POST['target_email'] ?? ''), $_POST, $_POST['current_password'] ?? null);
          if (ob_get_level() > 0) { ob_clean(); }
          header('Location: index.php?m=users&a=edit&email=' . rawurlencode($account->getEmail()) . '&saved=1', true, 303);
          exit;
        case 'resetPassword':
          models\security::requireMethod('POST');
          $email = (string) ($_POST['target_email'] ?? '');
          models\usermanagement::resetPassword($email, (string) ($_POST['new_password'] ?? ''), $_POST['current_password'] ?? null);
          if (ob_get_level() > 0) { ob_clean(); }
          header('Location: index.php?m=users&a=edit&email=' . rawurlencode($email) . '&reset=1', true, 303);
          exit;
        case 'audit':
          models\security::requireMethod('GET');
          $view->audit(models\securityaudit::recent());
          return;
        default:
          models\security::requireMethod('GET');
          $view->browse(models\users::getAllAccounts());
      }
    } catch (DomainException $exception) {
      $message = $exception->getMessage();
      $forbidden = str_contains($message, 'verification')
        || str_contains($message, 'Administrators cannot')
        || str_contains($message, 'last active superuser');
      http_response_code($forbidden ? 403 : 400);
      $email = (string) ($_POST['target_email'] ?? $_GET['email'] ?? '');
      $account = $email !== '' ? models\users::getUser($email) : null;
      $view->form($account, models\users::getAllGroups(), $message);
    }
  }
}
