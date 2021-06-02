<?php

namespace controllers;
use views, models;

class login
{
  private $_user, $_view;

  public function __construct() {
    $this->_view = new views\login();
    if (!isset($_REQUEST["loginAction"])) {
      $_REQUEST["loginAction"] = "";
    } else if ($_REQUEST["loginAction"] == "login") {
      $this->_authenticateUser($_POST); //need to run this twice to show the logout button!
    } else if ($_REQUEST["loginAction"] == "logout") {
      $this->_logout();
    }
  }

  public function runAction() {
    switch ($_REQUEST["loginAction"]) {
      case "login":
        if (!$this->_authenticateUser($_POST)) {
	        $user = models\users::getUser($_SESSION["email"]);
        	$error = <<<HTML
						Password not recognised for {$user->getFirstname()} {$user->getLastname()}. Try again.
HTML;
          $this->_view->writeModal("login", $error);
        }
        break;
      case "logout":
        $this->_logout();
        break;
      case "forgotPassword":
	      $user = models\users::getUser($_SESSION["email"]);
	      $name = $user->getFirstName() . ' ' . $user->getLastName();
        $this->_view->writeModal("forgotPassword", $name);
        break;
      case "sendEmail":
        if ($this->_user = models\users::getUser($_SESSION["email"])) {
          $this->_sendEmail();
          $this->_view->writeModal("emailSent");
        } else {
          $this->_view->writeModal("emailAddressError");
        }
        break;
      case "resetPassword":
        $params 	= explode('|', base64_decode($_GET["p"]));
        $email 		= $params[0];
        $passAuth	= $params[1];
        $user = models\users::getUser($email);
        if ($passAuth != $user->getPasswordAuth() || time() > $passAuth+300) {  //set a limit of five mins on auth
          $this->_view->writeModal("login", "This link has expired");
        } else {
          $_SESSION["email"] = $email;
          $this->_view->writeModal("resetPassword");
        }
        break;
      case "savePassword":
          $this->_savePassword();
          //do not write modal
        break;
      default:
        if (!$this->isLoggedIn()) {
          $this->_view->writeModal("login");
        }
        break;
    }
  }

  private function _savePassword() {
    $user = models\users::getUser($_SESSION["email"]);
    $user->setPassword($_POST["pass1"]);
    $user->encryptPassword();
    $user->setPasswordAuth(null);	//remove password auth
	  models\users::saveUser($user);
	  $this->_authenticateUser(array("password" => $_POST["pass1"])); //login the user
  }

  private function _sendEmail() {
    //set password change authorisation in the DB
    $passwordAuth = time();
    $this->_user->setPasswordAuth($passwordAuth);
    models\users::saveUser($this->_user);
    $changeParams = array($this->_user->getEmail(), $passwordAuth);
    $changeParams = base64_encode(implode('|', $changeParams));
    $url = "https://" . $_SERVER["HTTP_HOST"] . "/gadelica/corpas/code/?loginAction=resetPassword&p=" . $changeParams;

    $emailText = <<<HTML
			<p>Dear {$this->_user->getFirstName()},</p>
			<p>Please reset your password by clicking <a title="password reset" href="{$url}">here</a>.</p>
			<p>If you have received this email in error or have any other queries please contact <a title="Email DASG" href="mailto:mail@dasg.ac.uk">mail@dasg.ac.uk</a>.</p>	
			<p>Kind regards</p>
			<p>The DASG team</p>
HTML;
    $email = new models\email($this->_user->getEmail(), "Faclair Corpus Password Reset", $emailText, "mail@dasg.ac.uk");
    $email->send();
  }

  /**
   * @param $params : the POST array
   * @return bool : true on authentic user, otherwise false
   */
  private function _authenticateUser($params) {
  	$_SESSION["email"] = $params["email"] ? $params["email"] : $_SESSION["email"];
    $user = models\users::getUser($_SESSION["email"]);
    if (empty($user) || !$user->checkPassword($params["password"])) {
      return false;
    }
    $this->_user = $user;
    $_SESSION["user"] = $user->getEmail();
    models\users::saveUser($user);   //update last login in DB
    return true;
  }

  public function getUser() {
  	$this->_user = models\users::getUser($_SESSION["user"]);
    return $this->_user;
  }

  public function isLoggedIn() {
    return isset($_SESSION["user"]);
  }

  private function _logout() {
    unset($this->_user);
    unset($_SESSION["user"]);
    unset($_SESSION["email"]);
    $this->_view->writeModal("login");
  }

  public function writeLoginModal() {
    $this->_view->writeModal("login");
  }
}