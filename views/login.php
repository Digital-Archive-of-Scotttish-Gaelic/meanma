<?php

namespace views;
use models;

class login
{
  public function writeModal($type, $msg = "", array $params = []) {
    $title = $body = $footer = $js = $formId = "";
    $safeMessage = models\security::escape($msg);
    switch ($type) {
      case "login":
	        $csrfToken = models\security::escape(models\security::csrfToken('login'));
        $dropdownHtml = $this->_getUserSelectHtml();
        $title = "Login to MEANMA";
        $emailHide = "";
        $passwordHide = "hide";
        $loginButton = "loginButton";
        if ($msg) {
	          $body = <<<HTML
	            <p class="loginMessage">{$safeMessage}</p>
HTML;
        }
        $body .= <<<HTML
            <div id="emailSelectContainer" class="{$emailHide}">
                {$dropdownHtml}
            </div>
            <div id="passwordContainer" class="{$passwordHide}">
              <label id="passwordLabel" class="{$emailHide}" data-error="wrong" data-success="right" for="password">enter password for <span id="selectedUser"></span></label>
              <input type="password" id="password" name="password" class="form-control validate">
              <div>
                <a href="?loginAction=forgotPassword" title="Forgot my password"><small>Forgot my password</small></a>
              </div>
            </div>
HTML;
        $footer = <<<HTML
            <input type="hidden" name="loginAction" value="login">
	            <input type="hidden" name="_csrf" value="{$csrfToken}">
						<button type="submit" id="login" class="{$loginButton} btn btn-primary">login</button>
						<button type="button" id="loginCancel" class="{$loginButton} btn btn-secondary">cancel</button>
HTML;
      break;
      case "forgotPassword":
	        $csrfToken = models\security::escape(models\security::csrfToken('forgotPassword'));
        $title = "Forgot Password";
        $body = <<<HTML
            <div>
                <p>Enter your account email address. If the account is eligible, a recovery link will be sent.</p>
                <label for="recoveryEmail">Email address</label>
                <input type="email" name="email" id="recoveryEmail" class="form-control" required autocomplete="email">
                <p class="loginMessage">{$safeMessage}</p>
            </div>
HTML;
        $footer = <<<HTML
            <input type="hidden" name="loginAction" value="sendEmail">
	            <input type="hidden" name="_csrf" value="{$csrfToken}">
            <a href="?" class="btn btn btn-secondary">cancel</a>
            <button type="submit" class="btn btn-primary">send</button>
HTML;
        break;
      case "emailSent":
        $title = "Password Recovery";
        $body = <<<HTML
            <div>
                <p>If an eligible account matches that address, a password recovery email will be sent.</p>
                <p>Please follow the link in the email to reset the password.</p>
            </div>
HTML;
        break;
      case "emailAddressError":
        $title = "Email Address Error";
        $body = <<<HTML
            <div>
                <h4>Email address not recognised.</h4>
                <p><a href="?loginAction=forgotPassword" title="Forgot my password">
                    Enter a different email address.</a>
                </p>
            </div>
HTML;
        break;
      case "resetPassword":
        $formId = ' id="savePassword"';
        $title = "Reset Password";
	        $safeEmail = models\security::escape($params['email'] ?? '');
	        $safeToken = models\security::escape($params['token'] ?? '');
	        $csrfToken = models\security::escape(models\security::csrfToken('resetPassword'));
        $body = <<<HTML
            <div>
                <label for="pass1">password</label>
                <input type="password" name="pass1" id="pass1">
            </div>
            <div>
                <label for="pass2">retype password</label>
                <input type="password" name="pass2" id="pass2">
            </div>
HTML;
        $footer = <<<HTML
							<input type="hidden" name="email" value="{$safeEmail}">
							<input type="hidden" name="token" value="{$safeToken}">
							<input type="hidden" name="_csrf" value="{$csrfToken}">
            <input type="hidden" name="loginAction" value="savePassword">
            <button type="submit" class="btn btn-primary">submit</button>
HTML;
        $js = <<<HTML
            $(function () {
              $('#savePassword').validate({
                rules: {
                  pass1: "required",
                  pass2: {
                    equalTo: "#pass1"
                  }
                }
              });
            });
HTML;
        break;
    }
    echo <<<HTML
        <form method="post" {$formId}>
        <div class="modal" id="loginModal" tabindex="-1" role="dialog">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{$title}</h5>
              </div>
              <div class="modal-body">
                {$body}
              </div>
              <div class="modal-footer">
                {$footer}
              </div>
            </div>
          </div>
        </div>
        </form>
        <script>
          $('#loginModal').modal({backdrop: 'static', keyboard: false});
          {$js}
        </script>
HTML;
  }

  private function _getUserSelectHtml() {
    $users = models\users::getAllUsers();
    $dropdownHtml = '<option value="">-- select a user --</option>';
    foreach ($users as $user) {
	    $email = models\security::escape($user->getEmail());
	    $name = models\security::escape($user->getFirstname() . ' ' . $user->getLastname());
      $dropdownHtml .= <<<HTML
	         <option value="{$email}">{$name}</option>
HTML;
    }
    $html = <<<HTML
        <select name="email" id="email" class="form-control">
            {$dropdownHtml}
        </select>
HTML;
    return $html;
  }
}
