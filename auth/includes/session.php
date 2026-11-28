<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function auth_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $cfg = auth_config();
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.sid_length', '48');
    ini_set('session.sid_bits_per_character', '6');
    ini_set('session.gc_maxlifetime', (string) $cfg['session_idle_seconds']);

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
    $_SESSION['pending_at'] = time();
}

function auth_get_pending_user(): ?array
{
    auth_session_start();
    if (empty($_SESSION['pending_user_id']) || empty($_SESSION['pending_email'])) {
        return null;
    }

    $started = (int) ($_SESSION['pending_at'] ?? 0);
    $ttl = (int) auth_config()['code_ttl_minutes'] * 60;
    if ($started === 0 || (time() - $started) > $ttl) {
        auth_clear_pending_user();
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
    unset($_SESSION['pending_user_id'], $_SESSION['pending_email'], $_SESSION['pending_at']);
}

function auth_login_user(string $userId, string $email): void
{
    auth_session_start();
    session_regenerate_id(true);
    auth_clear_pending_user();
    unset(
        $_SESSION['user_id'],
        $_SESSION['user_email'],
        $_SESSION['auth_started'],
        $_SESSION['auth_seen'],
        $_SESSION['auth_rotated']
    );

    if (auth_store_login($userId)) {
        return;
    }

    $now = time();
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['auth_started'] = $now;
    $_SESSION['auth_seen'] = $now;
    $_SESSION['auth_rotated'] = $now;
}

function auth_user(): ?array
{
    static $resolved = false;
    static $user = null;
    if ($resolved) {
        return $user;
    }
    $resolved = true;

    auth_session_start();
    $fromToken = auth_user_from_token();
    if ($fromToken !== null) {
        $user = $fromToken;
        return $user;
    }

    $user = auth_user_from_php_session();
    return $user;
}

function auth_logout(): void
{
    $name = auth_token_cookie_name();
    $token = (string) ($_COOKIE[$name] ?? '');
    if ($token !== '') {
        try {
            auth_revoke_token($token);
        } catch (PDOException $e) {
            if (!auth_is_missing_table($e)) {
                throw $e;
            }
        }
        auth_clear_token_cookie();
    }

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

function auth_token_cookie_name(): string
{
    return (string) auth_config()['session_cookie'];
}

function auth_token_cookie_options(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function auth_set_token_cookie(string $token): void
{
    setcookie(auth_token_cookie_name(), $token, auth_token_cookie_options(0));
}

function auth_clear_token_cookie(): void
{
    setcookie(auth_token_cookie_name(), '', auth_token_cookie_options(time() - 42000));
}

function auth_store_login(string $userId): bool
{
    $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $absolute = (int) auth_config()['session_absolute_seconds'];

    try {
        $stmt = auth_db()->prepare(
            'INSERT INTO auth_sessions (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, NOW() + CAST(:lifetime AS interval))'
        );
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
            'lifetime' => $absolute . ' seconds',
        ]);
    } catch (PDOException $e) {
        if (auth_is_missing_table($e)) {
            return false;
        }
        throw $e;
    }

    auth_set_token_cookie($token);
    return true;
}

function auth_user_from_token(): ?array
{
    $token = (string) ($_COOKIE[auth_token_cookie_name()] ?? '');
    if ($token === '' || strlen($token) < 32) {
        return null;
    }

    $idle = (int) auth_config()['session_idle_seconds'];
    try {
        $stmt = auth_db()->prepare(
            'SELECT u.id, u.email
             FROM auth_sessions s
             JOIN users u ON u.id = s.user_id
             WHERE s.token_hash = :token_hash
               AND s.revoked_at IS NULL
               AND s.expires_at > NOW()
               AND s.last_seen_at > NOW() - CAST(:idle AS interval)
             LIMIT 1'
        );
        $stmt->execute([
            'token_hash' => hash('sha256', $token),
            'idle' => $idle . ' seconds',
        ]);
        $row = $stmt->fetch();
    } catch (PDOException $e) {
        if (auth_is_missing_table($e)) {
            return null;
        }
        throw $e;
    }

    if ($row === false) {
        auth_revoke_token($token);
        auth_clear_token_cookie();
        return null;
    }

    $touch = auth_db()->prepare(
        'UPDATE auth_sessions
         SET last_seen_at = NOW()
         WHERE token_hash = :token_hash
           AND revoked_at IS NULL
           AND last_seen_at < NOW() - INTERVAL \'60 seconds\''
    );
    $touch->execute(['token_hash' => hash('sha256', $token)]);

    return [
        'id' => (string) $row['id'],
        'email' => (string) $row['email'],
    ];
}

function auth_user_from_php_session(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $now = time();
    $started = (int) ($_SESSION['auth_started'] ?? 0);
    $seen = (int) ($_SESSION['auth_seen'] ?? 0);
    if ($started === 0) {
        $started = $now;
        $_SESSION['auth_started'] = $now;
    }
    if ($seen === 0) {
        $seen = $now;
    }

    $cfg = auth_config();
    $idle = (int) $cfg['session_idle_seconds'];
    $absolute = (int) $cfg['session_absolute_seconds'];
    if (($now - $seen) > $idle || ($now - $started) > $absolute) {
        auth_logout();
        return null;
    }

    $_SESSION['auth_seen'] = $now;
    $rotated = (int) ($_SESSION['auth_rotated'] ?? $started);
    if (($now - $rotated) >= 900) {
        session_regenerate_id(true);
        $_SESSION['auth_rotated'] = $now;
    }

    $stmt = auth_db()->prepare('SELECT id, email FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (string) $_SESSION['user_id']]);
    $row = $stmt->fetch();
    if ($row === false) {
        auth_logout();
        return null;
    }

    return [
        'id' => (string) $row['id'],
        'email' => (string) $row['email'],
    ];
}

function auth_revoke_token(string $token): void
{
    if ($token === '') {
        return;
    }

    $stmt = auth_db()->prepare(
        'UPDATE auth_sessions
         SET revoked_at = NOW()
         WHERE token_hash = :token_hash
           AND revoked_at IS NULL'
    );
    $stmt->execute(['token_hash' => hash('sha256', $token)]);
}

function auth_is_missing_table(PDOException $e): bool
{
    if ((string) $e->getCode() === '42P01') {
        return true;
    }
    $info = $e->errorInfo ?? [];
    return isset($info[0]) && (string) $info[0] === '42P01';
}
