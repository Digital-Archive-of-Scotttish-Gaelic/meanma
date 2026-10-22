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
        $user = users::getUser(trim($email));
        if (!$user || !$user->getIsActive()) {
            return false;
        }

        $authorization = (string) $user->getPasswordAuth();
        $parts = explode(':', $authorization, 4);
        if (count($parts) !== 4 || $parts[0] !== 'v2') {
            return false;
        }

        [, $created, $expires, $storedHash] = $parts;
        if (!ctype_digit($created) || !ctype_digit($expires)) {
            return false;
        }

        $now = time();
        if ((int) $created > $now || (int) $expires < $now) {
            return false;
        }

        return hash_equals($storedHash, hash('sha256', $token));
    }

    public static function replacePassword(string $email, string $token, string $password): bool
    {
        if ($password === '' || strlen($password) > 1024 || !self::isValid($email, $token)) {
            return false;
        }

        $user = users::getUser(trim($email));
        $authorization = $user->getPasswordAuth();
        $user->setPassword($password);
        $user->setSalt(bin2hex(random_bytes(16)));
        $user->encryptPassword();

        return users::replacePasswordWithAuthorization(
            $user->getEmail(),
            $user->getPassword(),
            $user->getSalt(),
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
