<?php

namespace controllers;
use views, models;

class login
{
  private $_user, $_view, $_authenticationResult;

  public function __construct() {
    $this->_view = new views\login();
    $_REQUEST["loginAction"] = $_POST["loginAction"] ?? $_GET["loginAction"] ?? "";
    if ($_REQUEST["loginAction"] == "login") {
      models\security::requireMethod('POST');
      $this->_authenticationResult = $this->_authenticateUser($_POST);
    } else if ($_REQUEST["loginAction"] == "logout") {
      models\security::requireMethod('POST');
      if (!models\security::validateCsrf('logout', models\security::requestCsrfToken(), true)) {
        http_response_code(403);
        exit('Invalid request');
      }
      $this->logout();
      header('Location: index.php', true, 303);
      exit;
    }
  }

  public function runAction() {
    switch ($_REQUEST["loginAction"]) {
      case "login":
        if (!$this->_authenticationResult) {
          $this->_view->writeModal("login", "Login was unsuccessful. Check your details and try again.");
        }
        break;
      case "forgotPassword":
        models\security::requireMethod('GET');
        $this->_view->writeModal("forgotPassword");
        break;
      case "sendEmail":
        models\security::requireMethod('POST');
        $recoveryStarted = microtime(true);
        if (!models\security::validateCsrf('forgotPassword', models\security::requestCsrfToken(), true)) {
          http_response_code(403);
          $this->_view->writeModal("forgotPassword", "The request expired. Please try again.");
          break;
        }
        $email = trim((string) ($_POST['email'] ?? ''));
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (models\authratelimiter::consume('recovery-ip', $ip, 5, 3600)
            && models\authratelimiter::consume('recovery-account', strtolower($email), 3, 3600)) {
          models\passwordrecovery::request($email);
        }
        $remainingDelay = 0.5 - (microtime(true) - $recoveryStarted);
        if ($remainingDelay > 0) {
          usleep((int) ($remainingDelay * 1000000));
        }
        $this->_view->writeModal("emailSent");
        break;
      case "resetPassword":
        models\security::requireMethod('GET');
        $email = (string) ($_GET['email'] ?? '');
        $token = (string) ($_GET['token'] ?? '');
        if (!models\passwordrecovery::isValid($email, $token)) {
          $this->_view->writeModal("login", "This password recovery link is invalid or has expired.");
        } else {
          $this->_view->writeModal("resetPassword", "", ['email' => $email, 'token' => $token]);
        }
        break;
      case "savePassword":
        models\security::requireMethod('POST');
        $email = (string) ($_POST['email'] ?? '');
        $token = (string) ($_POST['token'] ?? '');
        $pass1 = (string) ($_POST['pass1'] ?? '');
        $pass2 = (string) ($_POST['pass2'] ?? '');
        $validCsrf = models\security::validateCsrf('resetPassword', models\security::requestCsrfToken(), true);
        if (!$validCsrf) {
          http_response_code(403);
          $this->_view->writeModal("login", "The password could not be changed. Request a new recovery link.");
        } else if ($pass1 === '' || !hash_equals($pass1, $pass2)
            || !models\passwordrecovery::replacePassword($email, $token, $pass1)) {
          http_response_code(400);
          $this->_view->writeModal("login", "The password could not be changed. Request a new recovery link.");
        } else {
          $this->_view->writeModal("login", "Your password has been changed. You can now log in.");
        }
        break;
      default:
        if (!$this->isLoggedIn()) {
          $this->_view->writeModal("login");
        }
        break;
    }
  }

  /**
   * @param $params : the POST array
   * @return bool : true on authentic user, otherwise false
   */
  private function _authenticateUser($params) {
    $csrfToken = $params['_csrf'] ?? null;
    if (!models\security::validateCsrf('login', is_string($csrfToken) ? $csrfToken : null, true)) {
      http_response_code(403);
      return false;
    }

    $email = trim((string) ($params['email'] ?? ''));
    $password = (string) ($params['password'] ?? '');
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (!models\authratelimiter::consume('login-ip', $ip, 30, 900)
        || !models\authratelimiter::consume('login-account', $ip . "\0" . strtolower($email), 5, 900)) {
      return false;
    }

    $user = models\users::getUser($email);
    if (!$user || !$user->getIsActive()) {
      models\passwordhasher::consumeVerificationTime($password);
      return false;
    }
    if (!models\users::verifyPasswordAndMigrate($user, $password)) {
      return false;
    }
    $this->_user = $user;
    session_regenerate_id(true);
    models\security::resetCsrfTokens();
    $_SESSION["loginTime"] = time();
    $_SESSION["user"] = $user->getEmail();
    unset($_SESSION["email"]);
    return true;
  }

  public function getUser() {
  	$this->_user = models\users::getUser($_SESSION["user"]);
    return $this->_user;
  }

  public function isLoggedIn() {
    return models\users::isAuthenticated();
  }

  public function logout() {
    unset($this->_user);
    models\users::clearAuthenticationSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
      ]);
    }
    session_destroy();
  }

  public function writeLoginModal() {
    $this->_view->writeModal("login");
  }

  public function getLoginTime() {
  	return $this->_loggedInTime;
  }
}
