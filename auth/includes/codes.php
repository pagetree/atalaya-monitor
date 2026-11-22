<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

function auth_generate_code(): string
{
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function auth_hash_code(string $code): string
{
    $hash = password_hash($code, PASSWORD_ARGON2ID);
    if ($hash === false) {
        throw new RuntimeException('Could not hash the verification code.');
    }
    return $hash;
}

function auth_create_login_code(string $userId): string
{
    $cfg = auth_config();
    $code = auth_generate_code();
    $hash = auth_hash_code($code);
    $minutes = (int) $cfg['code_ttl_minutes'];

    $pdo = auth_db();
    $pdo->beginTransaction();
    try {
        $invalidate = $pdo->prepare(
            'UPDATE auth_codes
             SET used_at = NOW()
             WHERE user_id = :user_id
               AND used_at IS NULL'
        );
        $invalidate->execute(['user_id' => $userId]);

        $insert = $pdo->prepare(
            'INSERT INTO auth_codes (user_id, code_hash, expires_at)
             VALUES (:user_id, :code_hash, NOW() + make_interval(mins => :minutes))'
        );
        $insert->bindValue('user_id', $userId);
        $insert->bindValue('code_hash', $hash);
        $insert->bindValue('minutes', $minutes, PDO::PARAM_INT);
        $insert->execute();

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return $code;
}

function auth_consume_login_code(string $userId, string $code): bool
{
    $code = preg_replace('/\D+/', '', $code) ?? '';
    if (strlen($code) !== 6) {
        return false;
    }

    $stmt = auth_db()->prepare(
        'SELECT id, code_hash, expires_at, used_at
         FROM auth_codes
         WHERE user_id = :user_id
           AND used_at IS NULL
           AND expires_at > NOW()
         ORDER BY created_at DESC
         LIMIT 5'
    );
    $stmt->execute(['user_id' => $userId]);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        if (!password_verify($code, (string) $row['code_hash'])) {
            continue;
        }

        $mark = auth_db()->prepare(
            'UPDATE auth_codes
             SET used_at = NOW()
             WHERE id = :id
               AND used_at IS NULL'
        );
        $mark->execute(['id' => $row['id']]);
        return $mark->rowCount() === 1;
    }

    return false;
}
