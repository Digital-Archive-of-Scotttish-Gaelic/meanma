<?php

declare(strict_types=1);

namespace Tests\Integration;

use models\passwordrecovery;
use models\passwordhasher;
use models\user;
use models\users;
use PHPUnit\Framework\TestCase;
use Tests\Support\TestDatabase;

final class AccountSecurityTest extends TestCase
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

    public function testActiveAndInactiveLegacyAccountsRetainTheirAuthenticationState(): void
    {
        $active = users::getUser('active@example.test');
        $inactive = users::getUser('inactive@example.test');

        self::assertNotNull($active);
        self::assertTrue($active->getIsActive());
        self::assertSame(1, $active->checkPassword('fixture-password'));
        $legacyHash = $active->getPassword();
        self::assertFalse(users::verifyPasswordAndMigrate($active, 'wrong-password'));
        self::assertSame(
            $legacyHash,
            $this->database->connection()->query(
                "SELECT password FROM user WHERE email='active@example.test'"
            )->fetchColumn()
        );
        self::assertNotNull($inactive);
        self::assertFalse($inactive->getIsActive());
        self::assertFalse(users::verifyPasswordAndMigrate($inactive, 'fixture-password'));
        self::assertTrue(passwordhasher::isLegacyHash($inactive->getPassword(), $inactive->getStoredSalt()));

        $_SESSION = ['user' => 'active@example.test', 'loginTime' => time(), 'authVersion' => 1];
        self::assertTrue(users::isAuthenticated());
        $_SESSION = ['user' => 'inactive@example.test', 'loginTime' => time(), 'authVersion' => 1];
        self::assertFalse(users::isAuthenticated());
        self::assertArrayNotHasKey('user', $_SESSION);

        $_SESSION = ['user' => 'active@example.test', 'loginTime' => time() - (60 * 60 * 10) - 1, 'authVersion' => 1];
        self::assertFalse(users::isAuthenticated());
        self::assertArrayNotHasKey('user', $_SESSION);
    }

    public function testRolesAndGroupMembershipsRetainTheirMeaning(): void
    {
        $admin = users::getUser('admin@example.test');
        $editor = users::getUser('editor@example.test');
        $active = users::getUser('active@example.test');

        self::assertTrue($admin->getSuperuser());
        self::assertSame(user::SUPERUSER, $admin->getUserFlag());
        self::assertTrue($editor->getEditor());
        self::assertSame(user::EDITOR, $editor->getUserFlag());
        self::assertCount(1, $active->getGroups());
        self::assertSame(1, $active->getLastUsedGroup()->getId());

        $_SESSION = [
            'user' => 'active@example.test',
            'email' => 'admin@example.test',
            'loginTime' => time(),
        ];
        self::assertFalse(users::checkSuperuserAuth());
    }

    public function testSuccessfulLegacyAuthenticationMigratesOnlyPasswordFields(): void
    {
        $connection = $this->database->connection();
        $before = $connection->query(
            "SELECT email,firstname,lastname,slip_admin,isActive,superuser FROM user
             WHERE email='active@example.test'"
        )->fetch(\PDO::FETCH_ASSOC);
        $membershipsBefore = (int) $connection->query(
            "SELECT COUNT(*) FROM userGroupMembers WHERE userEmail='active@example.test'"
        )->fetchColumn();
        $user = users::getUser('active@example.test');

        self::assertTrue(passwordhasher::isLegacyHash($user->getPassword(), $user->getStoredSalt()));
        self::assertTrue(users::verifyPasswordAndMigrate($user, 'fixture-password'));

        $migrated = users::getUser('active@example.test');
        self::assertTrue(passwordhasher::isModernHash($migrated->getPassword()));
        self::assertStringStartsWith('$argon2id$', $migrated->getPassword());
        self::assertSame('', $migrated->getStoredSalt());
        self::assertTrue(users::verifyPasswordAndMigrate($migrated, 'fixture-password'));
        self::assertFalse(users::verifyPasswordAndMigrate($migrated, 'wrong-password'));

        $after = $connection->query(
            "SELECT email,firstname,lastname,slip_admin,isActive,superuser FROM user
             WHERE email='active@example.test'"
        )->fetch(\PDO::FETCH_ASSOC);
        self::assertSame($before, $after);
        self::assertSame(
            $membershipsBefore,
            (int) $connection->query(
                "SELECT COUNT(*) FROM userGroupMembers WHERE userEmail='active@example.test'"
            )->fetchColumn()
        );
    }

    public function testConcurrentPasswordChangeCannotBeOverwrittenByMigration(): void
    {
        $connection = $this->database->connection();
        $staleUser = users::getUser('active@example.test');
        $concurrentHash = passwordhasher::hash('concurrent-password');
        $update = $connection->prepare("UPDATE user SET password = ?, salt = '' WHERE email = ?");
        $update->execute([$concurrentHash, 'active@example.test']);

        self::assertFalse(users::rehashPasswordIfCurrent($staleUser, 'fixture-password'));
        self::assertSame(
            $concurrentHash,
            $connection->query(
                "SELECT password FROM user WHERE email='active@example.test'"
            )->fetchColumn()
        );
        self::assertTrue(users::verifyPasswordAndMigrate(
            users::getUser('active@example.test'),
            'concurrent-password'
        ));
    }

    public function testRecoveryIsAccountBoundSingleUseAndChangesNoUnrelatedFields(): void
    {
        $connection = $this->database->connection();
        $before = $connection->query(
            "SELECT email,firstname,lastname,slip_admin,isActive,superuser FROM user
             WHERE email='active@example.test'"
        )->fetch(\PDO::FETCH_ASSOC);
        $membershipsBefore = (int) $connection->query(
            "SELECT COUNT(*) FROM userGroupMembers WHERE userEmail='active@example.test'"
        )->fetchColumn();

        $capturedUrl = null;
        passwordrecovery::request(
            'active@example.test',
            static function ($user, string $url) use (&$capturedUrl): void {
                $capturedUrl = $url;
            }
        );
        self::assertIsString($capturedUrl);
        parse_str((string) parse_url($capturedUrl, PHP_URL_QUERY), $query);
        $token = (string) ($query['token'] ?? '');
        $authorization = (string) $connection->query(
            "SELECT passwordAuth FROM user WHERE email='active@example.test'"
        )->fetchColumn();

        self::assertNotSame('', $token);
        self::assertStringStartsWith('v2:', $authorization);
        self::assertStringNotContainsString($token, $authorization);
        self::assertTrue(passwordrecovery::isValid('active@example.test', $token));
        self::assertFalse(passwordrecovery::isValid('admin@example.test', $token));
        self::assertTrue(passwordrecovery::replacePassword('active@example.test', $token, 'changed-password'));
        self::assertFalse(passwordrecovery::replacePassword('active@example.test', $token, 'attacker-password'));
        $recoveredUser = users::getUser('active@example.test');
        self::assertStringStartsWith('$argon2id$', $recoveredUser->getPassword());
        self::assertSame('', $recoveredUser->getStoredSalt());
        self::assertSame(1, $recoveredUser->checkPassword('changed-password'));

        $after = $connection->query(
            "SELECT email,firstname,lastname,slip_admin,isActive,superuser FROM user
             WHERE email='active@example.test'"
        )->fetch(\PDO::FETCH_ASSOC);
        self::assertSame($before, $after);
        self::assertSame(
            $membershipsBefore,
            (int) $connection->query(
                "SELECT COUNT(*) FROM userGroupMembers WHERE userEmail='active@example.test'"
            )->fetchColumn()
        );
    }

    public function testUnknownOrInactiveRecoveryRequestsNeverInvokeDelivery(): void
    {
        $deliveries = 0;
        $sender = static function () use (&$deliveries): void {
            $deliveries++;
        };

        passwordrecovery::request('unknown@example.test', $sender);
        passwordrecovery::request('inactive@example.test', $sender);

        self::assertSame(0, $deliveries);
    }
}
