<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

final class TestEnvironment
{
    public static function assertDatabaseOptIn(): void
    {
        if (getenv('MEANMA_TEST_DATABASE') !== '1') {
            throw new RuntimeException(
                'Database tests are disabled. Run them through Composer or set MEANMA_TEST_DATABASE=1 explicitly.'
            );
        }

        require_once __DIR__ . '/../../includes/passwords.php';
        $host = (string) (getenv('MEANMA_TEST_DB_HOST') ?: DB_HOST);
        $localHosts = ['localhost', '127.0.0.1', '::1'];
        if (!in_array($host, $localHosts, true)
            && getenv('MEANMA_TEST_ALLOW_REMOTE_DATABASE') !== '1') {
            throw new RuntimeException(
                'Remote database tests require the explicit MEANMA_TEST_ALLOW_REMOTE_DATABASE=1 opt-in.'
            );
        }
    }

    /** @return array{host:string,user:string,password:string} */
    public static function databaseCredentials(): array
    {
        self::assertDatabaseOptIn();

        return [
            'host' => (string) (getenv('MEANMA_TEST_DB_HOST') ?: DB_HOST),
            'user' => (string) (getenv('MEANMA_TEST_DB_USER') ?: DB_USER),
            'password' => (string) (($password = getenv('MEANMA_TEST_DB_PASSWORD')) === false
                ? DB_PASSWORD
                : $password),
        ];
    }

    public static function assertGeneratedDatabaseName(string $databaseName): void
    {
        if (!preg_match('/^meanma_(?:security|security_http|phpunit)_test_[a-f0-9]{12}$/', $databaseName)) {
            throw new RuntimeException('Refusing to use a database without a generated MEANMA test name.');
        }
    }
}
