<?php

declare(strict_types=1);

namespace Tests\Unit;

use models\security;
use PHPUnit\Framework\TestCase;

final class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        $_GET = $_POST = $_REQUEST = $_SERVER = $_SESSION = [];
        putenv('MEANMA_APP_ORIGIN');
        putenv('MEANMA_ALLOW_INSECURE_ORIGIN');
    }

    /**
     * @dataProvider publicAuthenticationRequestProvider
     */
    public function testOnlyExpectedAuthenticationRoutesArePublic(
        string $method,
        string $action,
        bool $expected
    ): void {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_METHOD'] = $method;
        if ($method === 'POST') {
            $_POST['loginAction'] = $action;
        } else {
            $_GET['loginAction'] = $action;
        }

        self::assertSame($expected, security::isPublicAuthenticationRequest());
    }

    public function publicAuthenticationRequestProvider(): array
    {
        return [
            'login form' => ['GET', '', true],
            'login submit' => ['POST', 'login', true],
            'recovery form' => ['GET', 'forgotPassword', true],
            'recovery request' => ['POST', 'sendEmail', true],
            'reset form' => ['GET', 'resetPassword', true],
            'password save' => ['POST', 'savePassword', true],
            'login cannot use GET' => ['GET', 'login', false],
            'password save cannot use GET' => ['GET', 'savePassword', false],
            'unknown action' => ['GET', 'unknown', false],
        ];
    }

    public function testApplicationAndAjaxRoutesAreNeverPublic(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['m' => 'corpus', 'a' => 'xsearch'];
        self::assertFalse(security::isPublicAuthenticationRequest());

        $_GET = ['m' => 'frequency'];
        self::assertFalse(security::isPublicAuthenticationRequest());

        $_SERVER['SCRIPT_NAME'] = '/ajax.php';
        $_GET = ['action' => 'search'];
        self::assertFalse(security::isPublicAuthenticationRequest());
    }

    public function testCsrfTokensAreSessionBoundRotatableAndConsumable(): void
    {
        $first = security::csrfToken('application');
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $first);
        self::assertSame($first, security::csrfToken('application'));
        self::assertTrue(security::validateCsrf('application', $first));
        self::assertFalse(security::validateCsrf('application', 'invalid'));

        $second = security::rotateCsrfToken('application');
        self::assertNotSame($first, $second);
        self::assertFalse(security::validateCsrf('application', $first));
        self::assertTrue(security::validateCsrf('application', $second, true));
        self::assertFalse(security::validateCsrf('application', $second));
    }

    public function testMutationInventoryRecognisesRepresentativeProtectedActions(): void
    {
        foreach (['saveSlip', 'saveCitation', 'createTranslation', 'raiseIssue', 'setGroup'] as $action) {
            self::assertTrue(security::isAjaxMutation($action), $action);
        }
        self::assertFalse(security::isAjaxMutation('search'));
        self::assertFalse(security::isAjaxMutation('loadSlipData'));
        self::assertSame(
            count(security::ajaxMutationActions()),
            count(array_unique(security::ajaxMutationActions()))
        );
    }

    public function testJsonDetectionCoversAjaxAcceptAndRequestedWith(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/ajax.php';
        self::assertTrue(security::wantsJson());

        $_SERVER = ['SCRIPT_NAME' => '/index.php', 'HTTP_ACCEPT' => 'application/json'];
        self::assertTrue(security::wantsJson());

        $_SERVER = ['SCRIPT_NAME' => '/index.php', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];
        self::assertTrue(security::wantsJson());

        $_SERVER = ['SCRIPT_NAME' => '/index.php', 'HTTP_ACCEPT' => 'text/html'];
        self::assertFalse(security::wantsJson());
    }

    public function testCanonicalOriginRejectsUntrustedOrInsecureValues(): void
    {
        putenv('MEANMA_APP_ORIGIN=https://meanma.example.test/');
        self::assertSame('https://meanma.example.test', security::canonicalOrigin());

        putenv('MEANMA_APP_ORIGIN=http://meanma.example.test');
        self::assertNull(security::canonicalOrigin());

        putenv('MEANMA_APP_ORIGIN=https://user:pass@meanma.example.test');
        self::assertNull(security::canonicalOrigin());

        putenv('MEANMA_APP_ORIGIN=https://meanma.example.test/?next=evil');
        self::assertNull(security::canonicalOrigin());
    }

    public function testRequestCsrfTokenRejectsNonScalarInput(): void
    {
        $_POST['_csrf'] = ['unexpected'];
        self::assertNull(security::requestCsrfToken());

        $_POST['_csrf'] = 'token';
        self::assertSame('token', security::requestCsrfToken());
    }
}
