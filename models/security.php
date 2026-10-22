<?php

namespace models;

class security
{
    private const AJAX_MUTATION_ACTIONS = [
        'getContext',
        'updatePrintList',
        'loadSlip',
        'loadCitation',
        'saveCitation',
        'deleteCitation',
        'createTranslation',
        'saveTranslation',
        'deleteTranslation',
        'deleteSlips',
        'deleteEntries',
        'createPaperSlip',
        'getPileCategoriesForNewWordclass',
        'saveSlip',
        'addTextIdToSlip',
        'autoCreateSlips',
        'saveSlipPile',
        'addPile',
        'editPile',
        'saveLemmaGrammar',
        'requestUnlock',
        'setGroup',
        'createEntry',
        'saveEntry',
        'raiseIssue',
        'updateIssue',
        'createEmendation',
        'updateEmendation',
        'deleteEmendation',
        'createDeletion',
        'updateDeletion',
        'deleteDeletion',
        'saveSense',
        'swapSense',
        'unassignSense',
        'editLemma',
    ];

    private const PUBLIC_AUTH_ACTIONS = [
        '' => 'GET',
        'login' => 'POST',
        'forgotPassword' => 'GET',
        'sendEmail' => 'POST',
        'resetPassword' => 'GET',
        'savePassword' => 'POST',
    ];

    public static function isPublicAuthenticationRequest(): bool
    {
        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($script !== 'index.php' || !empty($_GET['m']) || !empty($_GET['a'])) {
            return false;
        }

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $comparisonMethod = $method === 'HEAD' ? 'GET' : $method;
        $action = $method === 'POST'
            ? (string) ($_POST['loginAction'] ?? '')
            : (string) ($_GET['loginAction'] ?? '');

        return isset(self::PUBLIC_AUTH_ACTIONS[$action])
            && self::PUBLIC_AUTH_ACTIONS[$action] === $comparisonMethod;
    }

    public static function enforceAuthenticationRequestMethod(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($script !== 'index.php' || !empty($_GET['m']) || !empty($_GET['a'])) {
            return;
        }

        $action = (string) ($_POST['loginAction'] ?? $_GET['loginAction'] ?? '');
        if (!isset(self::PUBLIC_AUTH_ACTIONS[$action])) {
            return;
        }

        $actual = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $expected = self::PUBLIC_AUTH_ACTIONS[$action];
        if ($actual === 'HEAD' && $expected === 'GET') {
            return;
        }
        self::requireMethod($expected);
    }

    public static function denyUnauthenticatedRequest(): void
    {
        if (self::wantsJson()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'authentication_required']);
            exit;
        }

        header('Location: index.php', true, 302);
        exit;
    }

    public static function wantsJson(): bool
    {
        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));

        return $script === 'ajax.php'
            || str_contains($accept, 'application/json')
            || $requestedWith === 'xmlhttprequest';
    }

    public static function csrfToken(string $context): string
    {
        if (empty($_SESSION['_csrf'][$context])) {
            $_SESSION['_csrf'][$context] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'][$context];
    }

    public static function rotateCsrfToken(string $context): string
    {
        unset($_SESSION['_csrf'][$context]);
        return self::csrfToken($context);
    }

    public static function resetCsrfTokens(): void
    {
        unset($_SESSION['_csrf']);
    }

    public static function validateCsrf(string $context, ?string $token, bool $consume = false): bool
    {
        $expected = $_SESSION['_csrf'][$context] ?? null;
        $valid = is_string($expected)
            && is_string($token)
            && hash_equals($expected, $token);

        if ($valid && $consume) {
            unset($_SESSION['_csrf'][$context]);
        }

        return $valid;
    }

    public static function ajaxMutationActions(): array
    {
        return self::AJAX_MUTATION_ACTIONS;
    }

    public static function isAjaxMutation(string $action): bool
    {
        return in_array($action, self::AJAX_MUTATION_ACTIONS, true);
    }

    public static function requireMethod(string $method): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $actual = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $expected = strtoupper($method);
        if ($actual === $expected) {
            return;
        }

        http_response_code(405);
        header('Allow: ' . $expected);
        if (self::wantsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'method_not_allowed']);
        } else {
            echo 'Method not allowed';
        }
        exit;
    }

    public static function requireCsrf(string $context, ?string $token): void
    {
        if (PHP_SAPI === 'cli' || self::validateCsrf($context, $token)) {
            return;
        }

        http_response_code(403);
        if (self::wantsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'invalid_csrf_token']);
        } else {
            echo 'Invalid request';
        }
        exit;
    }

    public static function requireStateChangingRequest(string $context = 'application'): void
    {
        self::requireMethod('POST');
        self::requireCsrf($context, self::requestCsrfToken());
    }

    public static function requestCsrfToken(): ?string
    {
        $token = $_POST['_csrf'] ?? null;
        return is_string($token) ? $token : null;
    }

    public static function canonicalOrigin(): ?string
    {
        $origin = rtrim((string) getenv('MEANMA_APP_ORIGIN'), '/');
        if ($origin === '') {
            return null;
        }

        $parts = parse_url($origin);
        $allowHttp = getenv('MEANMA_ALLOW_INSECURE_ORIGIN') === '1';
        if (!is_array($parts)
            || empty($parts['scheme'])
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
            || ($parts['scheme'] !== 'https' && !$allowHttp)) {
            return null;
        }

        return $origin;
    }

    public static function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
