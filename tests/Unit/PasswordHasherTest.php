<?php

declare(strict_types=1);

namespace Tests\Unit;

use models\passwordhasher;
use PHPUnit\Framework\TestCase;

final class PasswordHasherTest extends TestCase
{
    public function testArgon2idHashingAndVerification(): void
    {
        self::assertTrue(passwordhasher::isArgon2idSupported());
        $hash = passwordhasher::hash('correct-password');

        self::assertStringStartsWith('$argon2id$', $hash);
        self::assertTrue(passwordhasher::isModernHash($hash));
        self::assertTrue(passwordhasher::verify('correct-password', $hash, 'ignored-legacy-salt'));
        self::assertFalse(passwordhasher::verify('wrong-password', $hash, 'ignored-legacy-salt'));
        self::assertFalse(passwordhasher::needsRehash($hash, null));
    }

    public function testExactLegacySaltedMd5FormatIsAccepted(): void
    {
        $salt = 'legacy-salt';
        $hash = md5($salt . 'correct-password');

        self::assertTrue(passwordhasher::isLegacyHash($hash, $salt));
        self::assertTrue(passwordhasher::verify('correct-password', $hash, $salt));
        self::assertFalse(passwordhasher::verify('wrong-password', $hash, $salt));
        self::assertTrue(passwordhasher::needsRehash($hash, $salt));
    }

    /**
     * @dataProvider malformedLegacyProvider
     */
    public function testMalformedOrUnsupportedLegacyRecordsAreRejected(string $hash, ?string $salt): void
    {
        self::assertFalse(passwordhasher::isLegacyHash($hash, $salt));
        self::assertFalse(passwordhasher::verify('password', $hash, $salt));
        self::assertFalse(passwordhasher::needsRehash($hash, $salt));
    }

    public function malformedLegacyProvider(): array
    {
        return [
            'empty hash' => ['', 'salt'],
            'short hash' => ['abc123', 'salt'],
            'uppercase MD5' => [strtoupper(md5('saltpassword')), 'salt'],
            'non-hex hash' => [str_repeat('z', 32), 'salt'],
            'missing salt' => [md5('saltpassword'), null],
            'empty salt' => [md5('password'), ''],
        ];
    }

    public function testRecognisedModernHashNeverFallsBackToLegacyVerification(): void
    {
        $hash = password_hash('modern-password', PASSWORD_BCRYPT);
        self::assertIsString($hash);
        self::assertTrue(passwordhasher::isModernHash($hash));
        self::assertFalse(passwordhasher::verify('wrong-password', $hash, 'legacy-salt'));
        self::assertTrue(passwordhasher::needsRehash($hash, 'legacy-salt'));
    }
}
