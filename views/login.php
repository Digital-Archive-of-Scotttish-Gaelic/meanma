<?php

namespace views;
use models;

class login
{
  public function writeModal($type, $msg = "") {
    $title = $body = $footer = $js = $formId = "";
    switch ($type) {
      case "login":
        $dropdownHtml = $this->_getUserSelectHtml();
        $title = "Login to MEANMA";
        $emailHide = "";
        $passwordHide = "hide";
        $loginButton = "loginButton";
        if ($msg) {
        	$emailHide = "hide";
        	$passwordHide = $loginButton = "";
          $body = <<<HTML
            <p class="loginMessage">{$msg}</p>
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
						<button type="submit" id="login" class="{$loginButton} btn btn-primary">login</button>
						<button type="button" id="loginCancel" class="{$loginButton} btn btn-secondary">cancel</button>
HTML;
      break;
      case "forgotPassword":
        $title = "Forgot Password";
        $body = <<<HTML
            <div>
                <p>Send password reset to {$msg}'s email address?</p>
                <input type="hidden" name="email" id="email" value="{$_POST["email"]}">
            </div>
HTML;
        $footer = <<<HTML
            <input type="hidden" name="loginAction" value="sendEmail">
            <a href="?" class="btn btn btn-secondary">cancel</a>
            <button type="submit" class="btn btn-primary">send</button>
HTML;
        break;
      case "emailSent":
        $title = "Email Sent";
        $body = <<<HTML
            <div>
                <p>An email has been sent to your email address.</p>
                <p>Please click the link in the email to reset your password.</p>
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
						<input type="hidden" name="email" id="email" value="{$_SESSION["email"]}">
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
      $dropdownHtml .= <<<HTML
         <option value="{$user->getEmail()}">{$user->getFirstname()} {$user->getLastname()}</option>
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
