<?php

declare(strict_types=1);

use Tests\Support\TestEnvironment;

require_once __DIR__ . '/../Support/TestEnvironment.php';

TestEnvironment::assertDatabaseOptIn();
$credentials = TestEnvironment::databaseCredentials();

$action = $argv[1] ?? '';
$databaseName = $argv[2] ?? '';
try {
    TestEnvironment::assertGeneratedDatabaseName($databaseName);
} catch (RuntimeException $exception) {
    fwrite(STDERR, "Invalid test database name\n");
    exit(2);
}

$admin = new PDO(
    'mysql:host=' . $credentials['host'] . ';charset=utf8mb4',
    $credentials['user'],
    $credentials['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

if ($action === 'drop') {
    $admin->exec("DROP DATABASE IF EXISTS `{$databaseName}`");
    exit;
}

if ($action === 'last-used' || $action === 'issue-count' || $action === 'set-recovery') {
    $database = new PDO(
        'mysql:host=' . $credentials['host'] . ';dbname=' . $databaseName . ';charset=utf8mb4',
        $credentials['user'],
        $credentials['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    if ($action === 'last-used') {
        echo $database->query(
            "SELECT UNIX_TIMESTAMP(lastUsed) FROM userGroupMembers WHERE userEmail='active@example.test' AND groupId=1"
        )->fetchColumn(), PHP_EOL;
    } elseif ($action === 'issue-count') {
        echo $database->query('SELECT COUNT(*) FROM issue')->fetchColumn(), PHP_EOL;
    } else {
        $token = 'isolated-http-recovery-token';
        $created = time();
        $authorization = 'v2:' . $created . ':' . ($created + 1800) . ':' . hash('sha256', $token);
        $statement = $database->prepare(
            "UPDATE user SET passwordAuth = :authorization WHERE email = 'active@example.test'"
        );
        $statement->execute(['authorization' => $authorization]);
        echo $token, PHP_EOL;
    }
    exit;
}

if ($action !== 'create') {
    fwrite(STDERR, "Expected a supported fixture action\n");
    exit(2);
}

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
        must_change_password tinyint(1) NOT NULL DEFAULT 0,
        auth_version bigint unsigned NOT NULL DEFAULT 1,
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
    CREATE TABLE issue (
        id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
        description text NOT NULL,
        userEmail varchar(255) NOT NULL,
        status varchar(50) NOT NULL,
        updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    CREATE TABLE security_audit (
        id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        actor_email varchar(255) NOT NULL,
        target_email varchar(255) NULL,
        action varchar(64) NOT NULL,
        outcome varchar(32) NOT NULL,
        details_json text NULL,
        created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
    );
SQL);

$insert = $database->prepare(
    'INSERT INTO user(email,password,salt,firstname,lastname,slip_admin,isActive,superuser)
     VALUES(?,?,?,?,?,?,?,?)'
);
foreach ([
    ['active@example.test', true, 0],
    ['inactive@example.test', false, 0],
    ['admin@example.test', true, 1],
    ['editor@example.test', true, 2],
] as [$email, $active, $role]) {
    $salt = 'http-test-salt-' . (int) $active;
    $insert->execute([$email, md5($salt . 'test-password'), $salt, 'HTTP', 'Tester', 1, (int) $active, $role]);
}
$database->exec("INSERT INTO userGroup(name,theme) VALUES ('Test group','123456')");
$database->exec("INSERT INTO userGroupMembers(groupId,userEmail,lastUsed) VALUES (1,'active@example.test','2000-01-01 00:00:00')");
$database->exec("INSERT INTO userGroupMembers(groupId,userEmail,lastUsed) VALUES (1,'admin@example.test','2000-01-01 00:00:00'),(1,'editor@example.test','2000-01-01 00:00:00')");
