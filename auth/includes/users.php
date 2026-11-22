<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function auth_normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function auth_find_user_by_email(string $email): ?array
{
    $norm = auth_normalize_email($email);
    $stmt = auth_db()->prepare(
        'SELECT id, email, email_norm, password_hash, email_verified_at, created_at, updated_at
         FROM users
         WHERE email_norm = :email_norm
         LIMIT 1'
    );
    $stmt->execute(['email_norm' => $norm]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function auth_create_user(string $email, string $password): array
{
    $email = trim($email);
    $norm = auth_normalize_email($email);
    $hash = password_hash($password, PASSWORD_ARGON2ID);
    if ($hash === false) {
        throw new RuntimeException('Could not hash the password.');
    }

    $stmt = auth_db()->prepare(
        'INSERT INTO users (email, email_norm, password_hash)
         VALUES (:email, :email_norm, :password_hash)
         RETURNING id, email, email_norm, password_hash, email_verified_at, created_at, updated_at'
    );
    $stmt->execute([
        'email' => $email,
        'email_norm' => $norm,
        'password_hash' => $hash,
    ]);
    $row = $stmt->fetch();
    if ($row === false) {
        throw new RuntimeException('Could not create the user.');
    }
    return $row;
}

function auth_verify_password(array $user, string $password): bool
{
    return password_verify($password, (string) $user['password_hash']);
}

function auth_mark_email_verified(string $userId): void
{
    $stmt = auth_db()->prepare(
        'UPDATE users
         SET email_verified_at = COALESCE(email_verified_at, NOW()),
             updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute(['id' => $userId]);
}
