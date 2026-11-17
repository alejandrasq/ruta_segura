<?php
declare(strict_types=1);

function startUserSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('rutas_seguras_user');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function createUserCsrfToken(): string
{
    if (empty($_SESSION['user_csrf'])) {
        $_SESSION['user_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['user_csrf'];
}

function isUserAuthenticated(): bool
{
    return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
}

function requireUserApiAccess(): void
{
    startUserSession();

    if (!isUserAuthenticated()) {
        respond(401, ['ok' => false, 'error' => 'Inicia sesión con una cuenta de usuario para publicar reportes']);
    }

    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!isset($_SESSION['user_csrf']) || !hash_equals($_SESSION['user_csrf'], $csrf)) {
        respond(403, ['ok' => false, 'error' => 'Token de seguridad no válido']);
    }
}
