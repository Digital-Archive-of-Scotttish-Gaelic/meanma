<?php

declare(strict_types=1);

namespace Tests\Unit;

use models\user;
use models\usergroup;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testLegacySaltedMd5PasswordsRemainCompatible(): void
    {
        $user = new user('legacy@example.test');
        $user->setSalt('legacy-salt');
        $user->setPassword(md5('legacy-salt' . 'correct-password'));

        self::assertSame(1, $user->checkPassword('correct-password'));
        self::assertSame(0, $user->checkPassword('wrong-password'));
        self::assertSame('legacy@example.test', $user->getEmail());
    }

    public function testExistingRoleValuesKeepTheirMeaning(): void
    {
        $user = new user('role@example.test');

        $user->setUserFlag(user::SUPERUSER);
        self::assertTrue($user->getSuperuser());
        self::assertFalse($user->getEditor());

        $user->setUserFlag(user::EDITOR);
        self::assertFalse($user->getSuperuser());
        self::assertTrue($user->getEditor());
    }

    public function testGroupObjectsArePreservedOnTheUser(): void
    {
        $user = new user('member@example.test');
        $group = new usergroup(7, 'Editors', 'abcdef', '2026-01-01');
        $user->addGroup($group);
        $user->setLastUsedGroup($group);

        self::assertSame([$group], $user->getGroups());
        self::assertSame($group, $user->getLastUsedGroup());
        self::assertSame(7, $group->getId());
        self::assertSame('Editors', $group->getName());
    }
}
