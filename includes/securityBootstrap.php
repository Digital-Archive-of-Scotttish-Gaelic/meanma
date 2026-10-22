<?php

// All web entry points that bootstrap the application are private. Only the
// narrowly scoped login and password-recovery actions may run anonymously.
models\security::enforceAuthenticationRequestMethod();
if (PHP_SAPI !== 'cli' && !models\security::isPublicAuthenticationRequest()) {
    $hadAuthentication = !empty($_SESSION['user']) || !empty($_SESSION['loginTime']);
    if (!models\users::isAuthenticated()) {
        models\security::denyUnauthenticatedRequest($hadAuthentication);
    }
    if (!empty($_SESSION['mustChangePassword'])
        && !models\security::isMandatoryPasswordChangeRequest()) {
        models\security::denyMandatoryPasswordChangeBypass();
    }
}
