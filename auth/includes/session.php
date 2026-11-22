<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function auth_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $cfg = auth_config();
    session_name($cfg['session_name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function auth_flash_set(string $key, string $message): void
{
    auth_session_start();
    $_SESSION['_flash'][$key] = $message;
}

function auth_flash_get(string $key): ?string
{
    auth_session_start();
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }
    $message = (string) $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $message;
}

function auth_set_pending_user(string $userId, string $email): void
{
    auth_session_start();
    $_SESSION['pending_user_id'] = $userId;
    $_SESSION['pending_email'] = $email;
}

function auth_get_pending_user(): ?array
{
    auth_session_start();
    if (empty($_SESSION['pending_user_id']) || empty($_SESSION['pending_email'])) {
        return null;
    }
    return [
        'id' => (string) $_SESSION['pending_user_id'],
        'email' => (string) $_SESSION['pending_email'],
    ];
}

function auth_clear_pending_user(): void
{
    auth_session_start();
    unset($_SESSION['pending_user_id'], $_SESSION['pending_email']);
}

function auth_login_user(string $userId, string $email): void
{
    auth_session_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    auth_clear_pending_user();
}

function auth_user(): ?array
{
    auth_session_start();
    if (empty($_SESSION['user_id']) || empty($_SESSION['user_email'])) {
        return null;
    }
    return [
        'id' => (string) $_SESSION['user_id'],
        'email' => (string) $_SESSION['user_email'],
    ];
}

function auth_logout(): void
{
    auth_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'] ?? '',
            'secure' => (bool) $params['secure'],
            'httponly' => (bool) $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

function auth_web_root(): string
{
    $cfg = auth_config();
    if ($cfg['app_url'] !== '') {
        return rtrim($cfg['app_url'], '/');
    }

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (preg_match('#^(.*?)/auth(?:/|$)#', $script, $m)) {
        return $m[1];
    }

    return '';
}

function auth_redirect(string $path): never
{
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        header('Location: ' . $path);
        exit;
    }

    if (!str_starts_with($path, '/')) {
        $path = '/' . $path;
    }

    header('Location: ' . auth_web_root() . $path);
    exit;
}
