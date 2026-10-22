<?php

namespace models;

use RuntimeException;

final class passwordhasher
{
    private const ARGON2_OPTIONS = [
        'memory_cost' => 65536,
        'time_cost' => 3,
        'threads' => 1,
    ];

    public static function isArgon2idSupported(): bool
    {
        return defined('PASSWORD_ARGON2ID')
            && in_array('argon2id', password_algos(), true);
    }

    public static function hash(string $password): string
    {
        if (!self::isArgon2idSupported()) {
            throw new RuntimeException('Argon2id password hashing is not available in this PHP runtime.');
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID, self::ARGON2_OPTIONS);
        if (!is_string($hash)) {
            throw new RuntimeException('Argon2id password hashing failed.');
        }

        return $hash;
    }

    public static function verify(string $password, string $storedHash, ?string $legacySalt): bool
    {
        if (self::isModernHash($storedHash)) {
            return password_verify($password, $storedHash);
        }

        if (!self::isLegacyHash($storedHash, $legacySalt)) {
            return false;
        }

        return hash_equals($storedHash, md5((string) $legacySalt . $password));
    }

    public static function consumeVerificationTime(string $password): void
    {
        try {
            self::hash($password);
        } catch (\Throwable $exception) {
            hash('sha256', $password);
        }
    }

    public static function needsRehash(string $storedHash, ?string $legacySalt): bool
    {
        if (self::isLegacyHash($storedHash, $legacySalt)) {
            return true;
        }
        if (!self::isModernHash($storedHash)) {
            return false;
        }
        if (!self::isArgon2idSupported()) {
            throw new RuntimeException('Argon2id password hashing is not available in this PHP runtime.');
        }

        return password_needs_rehash($storedHash, PASSWORD_ARGON2ID, self::ARGON2_OPTIONS);
    }

    public static function isModernHash(string $storedHash): bool
    {
        return (password_get_info($storedHash)['algoName'] ?? 'unknown') !== 'unknown';
    }

    public static function isLegacyHash(string $storedHash, ?string $legacySalt): bool
    {
        return is_string($legacySalt)
            && $legacySalt !== ''
            && preg_match('/\A[a-f0-9]{32}\z/D', $storedHash) === 1;
    }
}
