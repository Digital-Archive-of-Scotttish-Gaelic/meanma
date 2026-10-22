<?php

declare(strict_types=1);

namespace Tests\Integration;

use DomainException;
use models\passwordhasher;
use models\securityaudit;
use models\user;
use models\usermanagement;
use models\users;
use PHPUnit\Framework\TestCase;
use Tests\Support\TestDatabase;

final class UserManagementTest extends TestCase
{
    private TestDatabase $database;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->database = new TestDatabase();
    }

    protected function tearDown(): void
    {
        $this->database->drop();
    }

    private function authenticateAdmin(): void
    {
        $_SESSION = ['user' => 'admin@example.test', 'loginTime' => time(), 'authVersion' => 1];
    }

    public function testOnlyActiveSuperusersCanManageAccounts(): void
    {
        foreach (['active@example.test', 'editor@example.test', 'inactive@example.test'] as $email) {
            $_SESSION = ['user' => $email, 'loginTime' => time(), 'authVersion' => 1];
            try {
                usermanagement::requireSuperuser();
                self::fail($email . ' was allowed to administer accounts');
            } catch (DomainException $exception) {
                self::assertSame('You are not authorised to manage accounts.', $exception->getMessage());
            }
        }
        $this->authenticateAdmin();
        self::assertSame('admin@example.test', usermanagement::requireSuperuser()->getEmail());
    }

    public function testAccountCreationValidatesAndPreservesRoleAndGroups(): void
    {
        $this->authenticateAdmin();
        $created = usermanagement::create([
            'email' => ' New.User@Example.Test ', 'firstname' => 'New', 'lastname' => 'User',
            'role' => '2', 'groups' => ['1'], 'active' => '1', 'password' => 'temporary-password',
        ]);
        self::assertSame('new.user@example.test', $created->getEmail());
        self::assertSame(user::EDITOR, $created->getUserFlag());
        self::assertTrue($created->getIsActive());
        self::assertTrue($created->getMustChangePassword());
        self::assertCount(1, $created->getGroups());
        self::assertTrue(passwordhasher::isModernHash($created->getPassword()));
        self::assertSame('', $created->getStoredSalt());
        self::assertSame(1, $created->checkPassword('temporary-password'));

        foreach ([
            ['email' => 'NEW.USER@example.test', 'firstname' => 'Duplicate', 'lastname' => 'User', 'role' => '0', 'groups' => [], 'active' => '1', 'password' => 'x'],
            ['email' => 'role@example.test', 'firstname' => 'Bad', 'lastname' => 'Role', 'role' => '9', 'groups' => [], 'active' => '1', 'password' => 'x'],
            ['email' => 'group@example.test', 'firstname' => 'Bad', 'lastname' => 'Group', 'role' => '0', 'groups' => ['999'], 'active' => '1', 'password' => 'x'],
        ] as $invalid) {
            try {
                usermanagement::create($invalid);
                self::fail('Invalid account data was accepted');
            } catch (DomainException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
        self::assertSame(5, (int) $this->database->connection()->query('SELECT COUNT(*) FROM user')->fetchColumn());
    }

    public function testRoleGroupAndStatusChangesRevokeSessions(): void
    {
        $this->authenticateAdmin();
        $pdo = $this->database->connection();
        $pdo->exec("INSERT INTO userGroup(name,theme) VALUES('Second group','654321')");
        $updated = usermanagement::update('active@example.test', [
            'firstname' => 'Updated', 'lastname' => 'Person', 'role' => '2',
            'groups' => ['2'], 'active' => '1',
        ]);
        self::assertSame(user::EDITOR, $updated->getUserFlag());
        self::assertSame([2], array_map(static fn($group) => (int) $group->getId(), $updated->getGroups()));
        self::assertSame(2, $updated->getAuthVersion());
        $_SESSION = ['user' => 'active@example.test', 'loginTime' => time(), 'authVersion' => 1];
        self::assertFalse(users::isAuthenticated());

        $this->authenticateAdmin();
        $disabled = usermanagement::update('active@example.test', [
            'firstname' => 'Updated', 'lastname' => 'Person', 'role' => '2',
            'groups' => ['2'],
        ]);
        self::assertFalse($disabled->getIsActive());
        self::assertSame(3, $disabled->getAuthVersion());
        self::assertCount(1, $disabled->getGroups());
    }

    public function testAdministratorPasswordResetForcesChangeAndRevokesSessions(): void
    {
        $this->authenticateAdmin();
        $before = users::getUser('active@example.test');
        $beforeGroups = array_map(static fn($group) => $group->getId(), $before->getGroups());
        usermanagement::resetPassword('active@example.test', 'assigned-password');
        $after = users::getUser('active@example.test');
        self::assertTrue(passwordhasher::isModernHash($after->getPassword()));
        self::assertSame(1, $after->checkPassword('assigned-password'));
        self::assertTrue($after->getMustChangePassword());
        self::assertSame(2, $after->getAuthVersion());
        self::assertSame($before->getUserFlag(), $after->getUserFlag());
        self::assertSame($before->getIsActive(), $after->getIsActive());
        self::assertSame($beforeGroups, array_map(static fn($group) => $group->getId(), $after->getGroups()));

        $_SESSION = ['user' => 'active@example.test', 'loginTime' => time(), 'authVersion' => 1];
        self::assertFalse(users::isAuthenticated());
        $newVersion = users::completeRequiredPassword('active@example.test', passwordhasher::hash('chosen-password'), 2);
        self::assertSame(3, $newVersion);
        self::assertFalse(users::getUser('active@example.test')->getMustChangePassword());
        self::assertSame(1, users::getUser('active@example.test')->checkPassword('chosen-password'));
    }

    public function testSuperuserSafeguardsAndReauthenticationWindow(): void
    {
        $this->authenticateAdmin();
        try {
            usermanagement::create([
                'email' => 'second-admin@example.test', 'firstname' => 'Second', 'lastname' => 'Admin',
                'role' => '1', 'groups' => ['1'], 'active' => '1', 'password' => 'temporary',
            ]);
            self::fail('Superuser creation did not require reauthentication');
        } catch (DomainException $exception) {
            self::assertStringContainsString('verification', $exception->getMessage());
        }
        $second = usermanagement::create([
            'email' => 'second-admin@example.test', 'firstname' => 'Second', 'lastname' => 'Admin',
            'role' => '1', 'groups' => ['1'], 'active' => '1', 'password' => 'temporary',
        ], 'fixture-password');
        self::assertTrue($second->getSuperuser());

        $_SERVER['REMOTE_ADDR'] = 'promotion-test';
        $_SESSION['adminReauthenticatedAt'] = time() - 301;
        try {
            usermanagement::update('editor@example.test', [
                'firstname' => 'Fixture', 'lastname' => 'User', 'role' => '1', 'groups' => ['1'], 'active' => '1',
            ]);
            self::fail('Promotion did not require recent reauthentication');
        } catch (DomainException $exception) {
            self::assertStringContainsString('verification', $exception->getMessage());
        }
        $promoted = usermanagement::update('editor@example.test', [
            'firstname' => 'Fixture', 'lastname' => 'User', 'role' => '1', 'groups' => ['1'], 'active' => '1',
        ], 'fixture-password');
        self::assertTrue($promoted->getSuperuser());
        self::assertSame(2, $promoted->getAuthVersion());

        try {
            usermanagement::update('admin@example.test', [
                'firstname' => 'Fixture', 'lastname' => 'User', 'role' => '0', 'groups' => ['1'], 'active' => '1',
            ]);
            self::fail('Self-demotion was accepted');
        } catch (DomainException $exception) {
            self::assertStringContainsString('own role', $exception->getMessage());
        }

        try {
            usermanagement::update('admin@example.test', [
                'firstname' => 'Fixture', 'lastname' => 'User', 'role' => '1', 'groups' => ['1'],
            ]);
            self::fail('Self-disablement was accepted');
        } catch (DomainException $exception) {
            self::assertStringContainsString('disable', $exception->getMessage());
        }

        $_SERVER['REMOTE_ADDR'] = 'reset-test';
        $_SESSION['adminReauthenticatedAt'] = time() - 301;
        try {
            usermanagement::resetPassword('second-admin@example.test', 'new-temp');
            self::fail('Expired reauthentication was accepted');
        } catch (DomainException $exception) {
            self::assertStringContainsString('verification', $exception->getMessage());
        }
        usermanagement::resetPassword('second-admin@example.test', 'new-temp', 'fixture-password');

        $this->database->connection()->exec("UPDATE user SET isActive=0 WHERE email IN ('second-admin@example.test','editor@example.test')");
        try {
            usermanagement::update('admin@example.test', [
                'firstname' => 'Fixture', 'lastname' => 'User', 'role' => '1', 'groups' => ['1'],
            ], 'fixture-password');
            self::fail('Last active superuser was disabled');
        } catch (DomainException $exception) {
            self::assertStringContainsString('own role or disable', $exception->getMessage());
        }
        self::assertSame(1, (int) $this->database->connection()->query(
            'SELECT COUNT(*) FROM user WHERE superuser=1 AND isActive=1'
        )->fetchColumn());
    }

    public function testAuditTrailContainsActionsButRejectsSecretFields(): void
    {
        $this->authenticateAdmin();
        usermanagement::resetPassword('active@example.test', 'assigned-password');
        $record = $this->database->connection()->query(
            "SELECT action,details_json FROM security_audit WHERE action='administrator_password_reset'"
        )->fetch(\PDO::FETCH_ASSOC);
        self::assertSame('administrator_password_reset', $record['action']);
        self::assertNull($record['details_json']);
        $auditText = implode(' ', $this->database->connection()->query(
            'SELECT CONCAT(action,\' \',COALESCE(details_json,\'\')) FROM security_audit'
        )->fetchAll(\PDO::FETCH_COLUMN));
        self::assertStringNotContainsString('assigned-password', $auditText);
        $this->expectException(\InvalidArgumentException::class);
        securityaudit::record('admin@example.test', 'active@example.test', 'unsafe', 'rejected', ['password' => 'never']);
    }
}
