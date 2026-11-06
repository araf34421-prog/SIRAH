<?php
/** Shared session helpers for SIRAH pages and API handlers. */
function startAppSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower($_SERVER['HTTPS']) !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    verifyCsrfRequest();
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfTokenField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCsrfRequest(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals(csrfToken(), $submittedToken)) {
        http_response_code(403);
        exit('Permintaan ditolak. Muat ulang halaman lalu coba kembali.');
    }
}

function isAuthenticated(): bool
{
    return isset($_SESSION['user_id']);
}

function userHasRole(array $roles): bool
{
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles, true);
}
