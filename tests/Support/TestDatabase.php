<?php

declare(strict_types=1);

namespace Tests\Support;

use PDO;

final class TestDatabase
{
    private PDO $admin;
    private ?PDO $connection;
    private string $name;

    public function __construct()
    {
        $credentials = TestEnvironment::databaseCredentials();
        $this->name = 'meanma_phpunit_test_' . bin2hex(random_bytes(6));
        TestEnvironment::assertGeneratedDatabaseName($this->name);

        $this->admin = new PDO(
            'mysql:host=' . $credentials['host'] . ';charset=utf8mb4',
            $credentials['user'],
            $credentials['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $this->admin->exec(
            "CREATE DATABASE `{$this->name}` CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci"
        );
        try {
            $this->connection = new PDO(
                'mysql:host=' . $credentials['host'] . ';dbname=' . $this->name . ';charset=utf8mb4',
                $credentials['user'],
                $credentials['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            putenv('MEANMA_DB_NAME=' . $this->name);
            putenv('MEANMA_DB_HOST=' . $credentials['host']);
            putenv('MEANMA_DB_USER=' . $credentials['user']);
            putenv('MEANMA_DB_PASSWORD=' . $credentials['password']);
            putenv('MEANMA_APP_ORIGIN=https://meanma.example.test');
            putenv('MEANMA_DISABLE_EMAIL=1');

            $this->createSchema();
            $this->seedAccounts();
        } catch (\Throwable $exception) {
            $this->connection = null;
            $this->admin->exec("DROP DATABASE IF EXISTS `{$this->name}`");
            throw $exception;
        }
    }

    public function connection(): PDO
    {
        if ($this->connection === null) {
            throw new \LogicException('The test database has already been dropped.');
        }
        return $this->connection;
    }

    public function drop(): void
    {
        TestEnvironment::assertGeneratedDatabaseName($this->name);
        $this->connection = null;
        $this->admin->exec("DROP DATABASE IF EXISTS `{$this->name}`");
        foreach (['MEANMA_DB_NAME', 'MEANMA_DB_HOST', 'MEANMA_DB_USER', 'MEANMA_DB_PASSWORD'] as $name) {
            putenv($name);
        }
    }

    private function createSchema(): void
    {
        $this->connection->exec(<<<SQL
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
    }

    private function seedAccounts(): void
    {
        $insert = $this->connection->prepare(
            'INSERT INTO user(email,password,salt,firstname,lastname,slip_admin,passwordAuth,isActive,superuser)
             VALUES(?,?,?,?,?,?,?,?,?)'
        );
        foreach ([
            ['active@example.test', true, 0],
            ['inactive@example.test', false, 0],
            ['admin@example.test', true, 1],
            ['editor@example.test', true, 2],
        ] as [$email, $active, $role]) {
            $salt = 'fixture-salt-' . $role . '-' . (int) $active;
            $insert->execute([
                $email,
                md5($salt . 'fixture-password'),
                $salt,
                'Fixture',
                'User',
                1,
                null,
                (int) $active,
                $role,
            ]);
        }
        $this->connection->exec("INSERT INTO userGroup(name,theme) VALUES ('Fixture group','123456')");
        $this->connection->exec("INSERT INTO userGroupMembers(groupId,userEmail) VALUES
            (1,'active@example.test'),(1,'admin@example.test'),(1,'editor@example.test')");
    }
}
