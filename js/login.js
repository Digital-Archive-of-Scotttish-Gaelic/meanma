(function () {
  'use strict';

  function updatePasswordToggle(button) {
    var input = document.getElementById(button.getAttribute('data-password-toggle'));
    if (!input) {
      return;
    }
    var showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    button.textContent = showing ? 'Show' : 'Hide';
    button.setAttribute('aria-pressed', showing ? 'false' : 'true');
    input.focus();
  }

  function validatePasswordMatch(form) {
    var password = form.querySelector('#pass1');
    var confirmation = form.querySelector('#pass2');
    if (!password || !confirmation) {
      return;
    }
    confirmation.setCustomValidity(password.value === confirmation.value ? '' : 'Passwords do not match.');
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
      button.addEventListener('click', function () {
        updatePasswordToggle(button);
      });
    });
    document.querySelectorAll('[data-password-match]').forEach(function (form) {
      form.addEventListener('input', function () { validatePasswordMatch(form); });
      form.addEventListener('submit', function () { validatePasswordMatch(form); });
    });
  });
})();
