<?php

// All web entry points that bootstrap the application are private. Only the
// narrowly scoped login and password-recovery actions may run anonymously.
if (PHP_SAPI !== 'cli'
    && !models\security::isPublicAuthenticationRequest()
    && !models\users::isAuthenticated()) {
    models\security::denyUnauthenticatedRequest();
}
