<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function auth_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = auth_config();
    $dsn = null;
    $user = $cfg['db_user'];
    $pass = $cfg['db_pass'];

    if (!empty($cfg['database_url'])) {
        $parts = parse_url($cfg['database_url']);
        if ($parts === false || empty($parts['host'])) {
            throw new RuntimeException('DATABASE_URL is invalid.');
        }
        $host = $parts['host'];
        $port = isset($parts['port']) ? (string) $parts['port'] : '5432';
        $name = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
        $user = isset($parts['user']) ? urldecode($parts['user']) : $user;
        $pass = isset($parts['pass']) ? urldecode($parts['pass']) : $pass;
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $name);
    } else {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $cfg['db_host'],
            $cfg['db_port'],
            $cfg['db_name']
        );
    }

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
