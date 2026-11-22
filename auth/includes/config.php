<?php

declare(strict_types=1);

function auth_load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key === '') {
            continue;
        }
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

auth_load_env(dirname(__DIR__) . '/.env');

function auth_env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

function auth_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $config = [
        'database_url' => auth_env('DATABASE_URL'),
        'db_host' => auth_env('PGHOST', '127.0.0.1'),
        'db_port' => auth_env('PGPORT', '5432'),
        'db_name' => auth_env('PGDATABASE', 'atalaya'),
        'db_user' => auth_env('PGUSER', 'postgres'),
        'db_pass' => auth_env('PGPASSWORD', ''),
        'cf_account_id' => auth_env('CF_ACCOUNT_ID', ''),
        'cf_api_token' => auth_env('CF_API_TOKEN', ''),
        'cf_from_email' => auth_env('CF_FROM_EMAIL', ''),
        'app_url' => rtrim(auth_env('APP_URL', ''), '/'),
        'session_name' => auth_env('AUTH_SESSION_NAME', 'atalaya_auth'),
        'code_ttl_minutes' => 30,
    ];

    return $config;
}
