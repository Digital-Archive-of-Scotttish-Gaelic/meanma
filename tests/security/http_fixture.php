<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/passwords.php';

$action = $argv[1] ?? '';
$databaseName = $argv[2] ?? '';
if (!preg_match('/^meanma_security_http_test_[a-f0-9]{12}$/', $databaseName)) {
    fwrite(STDERR, "Invalid test database name\n");
    exit(2);
}

$admin = new PDO(
    'mysql:host=' . DB_HOST . ';charset=utf8mb4',
    DB_USER,
    DB_PASSWORD,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

if ($action === 'drop') {
    $admin->exec("DROP DATABASE IF EXISTS `{$databaseName}`");
    exit;
}

if ($action !== 'create') {
    fwrite(STDERR, "Expected create or drop\n");
    exit(2);
}

$admin->exec("CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci");
$database = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . $databaseName . ';charset=utf8mb4',
    DB_USER,
    DB_PASSWORD,
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
    'INSERT INTO user(email,password,salt,firstname,lastname,slip_admin,isActive,superuser)
     VALUES(?,?,?,?,?,?,?,?)'
);
foreach ([
    ['active@example.test', true],
    ['inactive@example.test', false],
] as [$email, $active]) {
    $salt = 'http-test-salt-' . (int) $active;
    $insert->execute([$email, md5($salt . 'test-password'), $salt, 'HTTP', 'Tester', 1, (int) $active, 0]);
}
$database->exec("INSERT INTO userGroup(name,theme) VALUES ('Test group','123456')");
$database->exec("INSERT INTO userGroupMembers(groupId,userEmail) VALUES (1,'active@example.test')");
