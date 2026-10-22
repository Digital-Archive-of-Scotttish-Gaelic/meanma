<?php

declare(strict_types=1);

use models\authratelimiter;
use models\passwordrecovery;
use models\security;
use models\users;
use Tests\Support\TestEnvironment;

require_once __DIR__ . '/../../includes/passwords.php';
require_once __DIR__ . '/../Support/TestEnvironment.php';

TestEnvironment::assertDatabaseOptIn();
$credentials = TestEnvironment::databaseCredentials();

$databaseName = 'meanma_security_test_' . bin2hex(random_bytes(6));
$rateLimitDirectory = sys_get_temp_dir() . '/meanma-security-test-' . bin2hex(random_bytes(6));
$admin = new PDO(
    'mysql:host=' . $credentials['host'] . ';charset=utf8mb4',
    $credentials['user'],
    $credentials['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$failures = [];
$checks = 0;

$assert = static function (bool $condition, string $message) use (&$failures, &$checks): void {
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
};

try {
    $admin->exec("CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci");
    $database = new PDO(
        'mysql:host=' . $credentials['host'] . ';dbname=' . $databaseName . ';charset=utf8mb4',
        $credentials['user'],
        $credentials['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $database->exec(<<<SQL
        CREATE TABLE user (
            email varchar(255) NOT NULL PRIMARY KEY,
            password varchar(255) NOT NULL,
            salt varchar(50) NOT NULL,
            firstname varchar(255) NOT NULL,
            lastname varchar(255) NOT NULL,
            slip_admin tinyint(1) NOT NULL DEFAULT 1,
            passwordAuth varchar(255) NULL,
            isActive tinyint NOT NULL DEFAULT 1,
            superuser tinyint(1) NOT NULL DEFAULT 0,
            last_logged_in timestamp NULL,
            updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
        CREATE TABLE userGroup (
            id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name varchar(255) NOT NULL,
            theme varchar(255) NULL
        );
        CREATE TABLE userGroupMembers (
            userId int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
            groupId int NOT NULL,
            userEmail varchar(255) NOT NULL,
            lastUsed timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
SQL);

    $insert = $database->prepare(
        'INSERT INTO user(email,password,salt,firstname,lastname,slip_admin,passwordAuth,isActive,superuser)
         VALUES(?,?,?,?,?,?,?,?,?)'
    );
    $seed = static function (
        string $email,
        string $plainPassword,
        bool $active,
        int $role
    ) use ($insert): void {
        $salt = 'test-salt-' . $role . '-' . ($active ? 'a' : 'i');
        $insert->execute([$email, md5($salt . $plainPassword), $salt, 'Test', 'User', 1, null, (int) $active, $role]);
    };

    $seed('active@example.test', 'old-password', true, 0);
    $seed('inactive@example.test', 'old-password', false, 0);
    $seed('admin@example.test', 'old-password', true, 1);
    $seed('editor@example.test', 'old-password', true, 2);
    $database->exec("INSERT INTO userGroup(name,theme) VALUES ('Test group','123456')");
    $database->exec("INSERT INTO userGroupMembers(groupId,userEmail) VALUES
        (1,'active@example.test'),(1,'admin@example.test'),(1,'editor@example.test')");

    putenv('MEANMA_DB_NAME=' . $databaseName);
    putenv('MEANMA_DB_HOST=' . $credentials['host']);
    putenv('MEANMA_DB_USER=' . $credentials['user']);
    putenv('MEANMA_DB_PASSWORD=' . $credentials['password']);
    putenv('MEANMA_APP_ORIGIN=https://meanma.example.test');
    putenv('MEANMA_RATE_LIMIT_DIR=' . $rateLimitDirectory);
    putenv('MEANMA_COOKIE_SECURE=0');
    putenv('MEANMA_DISABLE_EMAIL=1');
    require_once __DIR__ . '/../../includes/include.php';

    $active = users::getUser('active@example.test');
    $inactive = users::getUser('inactive@example.test');
    $assert($active && $active->getIsActive(), 'Active account was not loaded as active');
    $assert($inactive && !$inactive->getIsActive(), 'Inactive account was not loaded as inactive');
    $assert($active->checkPassword('old-password') === 1, 'Existing salted-MD5 login compatibility failed');

    $_SESSION['user'] = 'active@example.test';
    $_SESSION['email'] = 'admin@example.test';
    $_SESSION['loginTime'] = time();
    $assert(users::isAuthenticated(), 'Active authenticated session was rejected');
    $assert(!users::checkSuperuserAuth(), 'Pre-authentication email influenced superuser authorization');

    $_SESSION['user'] = 'admin@example.test';
    $assert(users::checkSuperuserAuth(), 'Existing superuser role was not preserved');
    $_SESSION['user'] = 'editor@example.test';
    $assert(users::getUser($_SESSION['user'])->getEditor(), 'Existing editor role was not preserved');
    $_SESSION['user'] = 'inactive@example.test';
    $_SESSION['loginTime'] = time();
    $assert(!users::isAuthenticated(), 'Inactive account retained an authenticated session');

    $before = $database->query(
        "SELECT email,firstname,lastname,slip_admin,isActive,superuser FROM user
         WHERE email='active@example.test'"
    )->fetch(PDO::FETCH_ASSOC);
    $membershipCount = (int) $database->query(
        "SELECT COUNT(*) FROM userGroupMembers WHERE userEmail='active@example.test'"
    )->fetchColumn();

    $capturedUrl = null;
    passwordrecovery::request(
        'active@example.test',
        static function ($user, string $url) use (&$capturedUrl): void {
            $capturedUrl = $url;
        }
    );
    $assert(is_string($capturedUrl), 'Eligible recovery request did not invoke email delivery');
    parse_str((string) parse_url((string) $capturedUrl, PHP_URL_QUERY), $query);
    $token = (string) ($query['token'] ?? '');
    $authorization = (string) $database->query(
        "SELECT passwordAuth FROM user WHERE email='active@example.test'"
    )->fetchColumn();
    $assert(str_starts_with($authorization, 'v2:'), 'Recovery authorization was not stored in v2 format');
    $assert($token !== '' && !str_contains($authorization, $token), 'Raw recovery token was stored');
    $assert(passwordrecovery::isValid('active@example.test', $token), 'Valid recovery token was rejected');
    $assert(!passwordrecovery::isValid('admin@example.test', $token), 'Recovery token was accepted for another account');
    $assert(!passwordrecovery::isValid('active@example.test', $token . 'x'), 'Invalid recovery token was accepted');

    $assert(
        passwordrecovery::replacePassword('active@example.test', $token, 'new-password'),
        'Valid recovery token could not replace the password'
    );
    $assert(!passwordrecovery::isValid('active@example.test', $token), 'Recovery token remained usable after reset');
    $assert(
        !passwordrecovery::replacePassword('active@example.test', $token, 'attacker-password'),
        'Single-use recovery token was reused'
    );
    $assert(users::getUser('active@example.test')->checkPassword('new-password') === 1, 'Reset password cannot authenticate');

    $after = $database->query(
        "SELECT email,firstname,lastname,slip_admin,isActive,superuser FROM user
         WHERE email='active@example.test'"
    )->fetch(PDO::FETCH_ASSOC);
    $assert($before === $after, 'Password recovery modified unrelated account fields');
    $assert(
        $membershipCount === (int) $database->query(
            "SELECT COUNT(*) FROM userGroupMembers WHERE userEmail='active@example.test'"
        )->fetchColumn(),
        'Password recovery modified group membership'
    );

    $unknownDelivery = false;
    passwordrecovery::request(
        'unknown@example.test',
        static function () use (&$unknownDelivery): void {
            $unknownDelivery = true;
        }
    );
    $assert(!$unknownDelivery, 'Unknown account triggered email delivery');

    $expiredHash = hash('sha256', 'expired-token');
    $database->exec(
        "UPDATE user SET passwordAuth='v2:1:2:{$expiredHash}' WHERE email='active@example.test'"
    );
    $assert(!passwordrecovery::isValid('active@example.test', 'expired-token'), 'Expired recovery token was accepted');

    $assert(authratelimiter::consume('test', 'key', 2, 60), 'Rate limiter rejected first request');
    $assert(authratelimiter::consume('test', 'key', 2, 60), 'Rate limiter rejected second request');
    $assert(!authratelimiter::consume('test', 'key', 2, 60), 'Rate limiter allowed request over limit');

    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = $_POST = $_REQUEST = [];
    $assert(security::isPublicAuthenticationRequest(), 'Login page was not recognized as public');
    $_GET = ['m' => 'corpus', 'a' => 'xsearch'];
    $_REQUEST = $_GET;
    $assert(!security::isPublicAuthenticationRequest(), 'Corpus search was treated as public');
    $_GET = ['m' => 'frequency'];
    $_REQUEST = $_GET;
    $assert(!security::isPublicAuthenticationRequest(), 'Frequency endpoint was treated as public');
    $_SERVER['SCRIPT_NAME'] = '/ajax.php';
    $_GET = $_REQUEST = ['action' => 'search'];
    $assert(!security::isPublicAuthenticationRequest(), 'AJAX operation was treated as public');
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET = [];
    $_POST = $_REQUEST = ['loginAction' => 'savePassword'];
    $assert(security::isPublicAuthenticationRequest(), 'Recovery write route was not recognized for token validation');

    echo "Security regression checks: {$checks}\n";
    if ($failures) {
        foreach ($failures as $failure) {
            fwrite(STDERR, "FAIL: {$failure}\n");
        }
        exit(1);
    }
    echo "All security regression checks passed.\n";
} finally {
    $admin->exec("DROP DATABASE IF EXISTS `{$databaseName}`");
    if (is_dir($rateLimitDirectory)) {
        foreach (glob($rateLimitDirectory . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($rateLimitDirectory);
    }
}
