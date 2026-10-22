<?php

namespace views;

use models;

class login
{
  public function writePage($type, $msg = "", array $params = [])
  {
    $safeMessage = models\security::escape($msg);
    $loginUrl = 'index.php?loginAction=loginPage';
    $title = 'Sign in to MEANMA';
    $body = '';

    switch ($type) {
      case 'login':
        $csrfToken = models\security::escape(models\security::csrfToken('login'));
        $next = models\security::safeInternalDestination($params['next'] ?? null);
        $nextInput = $next === null ? '' : '<input type="hidden" name="next" value="' . models\security::escape($next) . '">';
        $message = $safeMessage === '' ? '' : '<p class="auth-message" role="alert" aria-live="polite">' . $safeMessage . '</p>';
        $body = <<<HTML
          {$message}
          <form method="post" action="index.php" class="auth-form">
            <input type="hidden" name="loginAction" value="login">
            <input type="hidden" name="_csrf" value="{$csrfToken}">
            {$nextInput}
            <div class="auth-field">
              <label for="email">Email address</label>
              <input type="email" id="email" name="email" class="form-control" required autocomplete="username" inputmode="email" autofocus>
            </div>
            <div class="auth-field">
              <label for="password">Password</label>
              <div class="auth-password">
                <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
                <button type="button" class="auth-password-toggle" data-password-toggle="password" aria-controls="password" aria-pressed="false">Show</button>
              </div>
            </div>
            <div class="auth-actions">
              <button type="submit" class="btn btn-primary">Sign in</button>
              <a href="index.php?loginAction=forgotPassword">Forgot your password?</a>
            </div>
          </form>
HTML;
        break;

      case 'forgotPassword':
        $title = 'Reset your password';
        $csrfToken = models\security::escape(models\security::csrfToken('forgotPassword'));
        $message = $safeMessage === '' ? '' : '<p class="auth-message" role="alert" aria-live="polite">' . $safeMessage . '</p>';
        $body = <<<HTML
          <p>Enter your account email address. If the account is eligible, a recovery link will be sent.</p>
          {$message}
          <form method="post" action="index.php" class="auth-form">
            <input type="hidden" name="loginAction" value="sendEmail">
            <input type="hidden" name="_csrf" value="{$csrfToken}">
            <div class="auth-field">
              <label for="recoveryEmail">Email address</label>
              <input type="email" name="email" id="recoveryEmail" class="form-control" required autocomplete="email" inputmode="email" autofocus>
            </div>
            <div class="auth-actions">
              <button type="submit" class="btn btn-primary">Send recovery email</button>
              <a href="{$loginUrl}">Back to sign in</a>
            </div>
          </form>
HTML;
        break;

      case 'emailSent':
        $title = 'Check your email';
        $body = <<<HTML
          <p>If an eligible account matches that address, a password recovery email will be sent.</p>
          <p>Follow the link in the email to reset the password. The link is time-limited and can be used only once.</p>
          <p><a href="{$loginUrl}">Back to sign in</a></p>
HTML;
        break;

      case 'resetPassword':
        $title = 'Choose a new password';
        $safeEmail = models\security::escape($params['email'] ?? '');
        $safeToken = models\security::escape($params['token'] ?? '');
        $csrfToken = models\security::escape(models\security::csrfToken('resetPassword'));
        $message = $safeMessage === '' ? '' : '<p class="auth-message" role="alert" aria-live="polite">' . $safeMessage . '</p>';
        $body = <<<HTML
          {$message}
          <form method="post" action="index.php" class="auth-form" data-password-match>
            <input type="hidden" name="email" value="{$safeEmail}">
            <input type="hidden" name="token" value="{$safeToken}">
            <input type="hidden" name="_csrf" value="{$csrfToken}">
            <input type="hidden" name="loginAction" value="savePassword">
            <div class="auth-field">
              <label for="pass1">New password</label>
              <div class="auth-password">
                <input type="password" name="pass1" id="pass1" class="form-control" required autocomplete="new-password" autofocus>
                <button type="button" class="auth-password-toggle" data-password-toggle="pass1" aria-controls="pass1" aria-pressed="false">Show</button>
              </div>
            </div>
            <div class="auth-field">
              <label for="pass2">Confirm new password</label>
              <div class="auth-password">
                <input type="password" name="pass2" id="pass2" class="form-control" required autocomplete="new-password">
                <button type="button" class="auth-password-toggle" data-password-toggle="pass2" aria-controls="pass2" aria-pressed="false">Show</button>
              </div>
            </div>
            <div class="auth-actions">
              <button type="submit" class="btn btn-primary">Save new password</button>
            </div>
          </form>
HTML;
        break;

      default:
        $body = '<p class="auth-message" role="alert">The requested authentication page is unavailable.</p><p><a href="' . $loginUrl . '">Return to sign in</a></p>';
    }

    $safeTitle = models\security::escape($title);
    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>{$safeTitle}</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
  <link rel="stylesheet" href="css/style.css">
  <script src="js/login.js" defer></script>
</head>
<body class="auth-page">
  <main class="auth-layout">
    <section class="auth-card" aria-labelledby="auth-title">
      <div class="auth-brand">MEANMA</div>
      <h1 id="auth-title">{$safeTitle}</h1>
      {$body}
    </section>
  </main>
</body>
</html>
HTML;
  }
}
