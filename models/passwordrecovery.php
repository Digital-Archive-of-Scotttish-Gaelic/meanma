<?php

namespace models;

class passwordrecovery
{
    private const TOKEN_TTL_SECONDS = 1800;

    public static function request(string $email, ?callable $sender = null): void
    {
        $email = trim($email);
        $user = users::getUser($email);
        if (!$user || !$user->getIsActive()) {
            return;
        }

        $origin = security::canonicalOrigin();
        if ($origin === null) {
            error_log('MEANMA password recovery is unavailable: MEANMA_APP_ORIGIN is not configured securely');
            return;
        }

        $token = self::base64UrlEncode(random_bytes(32));
        $created = time();
        $expires = $created + self::TOKEN_TTL_SECONDS;
        $authorization = 'v2:' . $created . ':' . $expires . ':' . hash('sha256', $token);

        users::setPasswordRecoveryAuthorization($user->getEmail(), $authorization);

        $url = $origin . '/index.php?loginAction=resetPassword&email='
            . rawurlencode($user->getEmail()) . '&token=' . rawurlencode($token);

        if ($sender) {
            $sender($user, $url);
            return;
        }

        self::sendEmail($user, $url);
    }

    public static function isValid(string $email, string $token): bool
    {
        return self::authorizationForToken($email, $token) !== null;
    }

    private static function authorizationForToken(string $email, string $token): ?string
    {
        $user = users::getUser(trim($email));
        if (!$user || !$user->getIsActive()) {
            return null;
        }

        $authorization = (string) $user->getPasswordAuth();
        $parts = explode(':', $authorization, 4);
        if (count($parts) !== 4 || $parts[0] !== 'v2') {
            return null;
        }

        [, $created, $expires, $storedHash] = $parts;
        if (!ctype_digit($created) || !ctype_digit($expires)) {
            return null;
        }

        $now = time();
        if ((int) $created > $now || (int) $expires < $now) {
            return null;
        }

        return hash_equals($storedHash, hash('sha256', $token)) ? $authorization : null;
    }

    public static function replacePassword(string $email, string $token, string $password): bool
    {
        if ($password === '' || strlen($password) > 1024) {
            return false;
        }

        $email = trim($email);
        $authorization = self::authorizationForToken($email, $token);
        if ($authorization === null) {
            return false;
        }
        try {
            $passwordHash = passwordhasher::hash($password);
        } catch (\Throwable $exception) {
            error_log('MEANMA password recovery hashing failed: ' . $exception->getMessage());
            return false;
        }

        return users::replacePasswordWithAuthorization(
            $email,
            $passwordHash,
            '',
            $authorization
        );
    }

    private static function sendEmail(user $user, string $url): void
    {
        if (getenv('MEANMA_DISABLE_EMAIL') === '1') {
            return;
        }

        $firstName = security::escape($user->getFirstName());
        $safeUrl = security::escape($url);
        $emailText = <<<HTML
            <p>Dear {$firstName},</p>
            <p>Please reset your password by clicking <a title="password reset" href="{$safeUrl}">here</a>.</p>
            <p>If you received this email in error, no action is required.</p>
            <p>Kind regards</p>
            <p>The DASG team</p>
HTML;
        $email = new email($user->getEmail(), 'MEANMA Password Reset', $emailText, 'mail@meanma.net');
        $email->send();
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
