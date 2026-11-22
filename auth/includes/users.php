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
        'SELECT id, email, email_norm, email_verified_at, created_at, updated_at
         FROM users
         WHERE email_norm = :email_norm
         LIMIT 1'
    );
    $stmt->execute(['email_norm' => $norm]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function auth_create_user(string $email): array
{
    $email = trim($email);
    $norm = auth_normalize_email($email);

    $stmt = auth_db()->prepare(
        'INSERT INTO users (email, email_norm)
         VALUES (:email, :email_norm)
         RETURNING id, email, email_norm, email_verified_at, created_at, updated_at'
    );
    $stmt->execute([
        'email' => $email,
        'email_norm' => $norm,
    ]);
    $row = $stmt->fetch();
    if ($row === false) {
        throw new RuntimeException('Could not create the user.');
    }
    return $row;
}

function auth_clean_person_name(string $value): string
{
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    if ($value === '') {
        return '';
    }
    $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    if ($length > 80) {
        return '';
    }
    if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
        return '';
    }
    return $value;
}

function auth_profile_empty(): array
{
    return [
        'given_name' => '',
        'family_name' => '',
        'ready' => false,
        'storage' => true,
    ];
}

function auth_load_profile(string $userId): array
{
    try {
        $stmt = auth_db()->prepare(
            'SELECT given_name, family_name
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();
    } catch (PDOException $e) {
        if ((string) $e->getCode() === '42703') {
            $empty = auth_profile_empty();
            $empty['storage'] = false;
            return $empty;
        }
        throw $e;
    }

    if ($row === false) {
        return auth_profile_empty();
    }

    $given = trim((string) ($row['given_name'] ?? ''));
    $family = trim((string) ($row['family_name'] ?? ''));
    return [
        'given_name' => $given,
        'family_name' => $family,
        'ready' => $given !== '' && $family !== '',
        'storage' => true,
    ];
}

function auth_save_profile(string $userId, string $givenName, string $familyName): void
{
    $stmt = auth_db()->prepare(
        'UPDATE users
         SET given_name = :given_name,
             family_name = :family_name,
             updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        'given_name' => $givenName,
        'family_name' => $familyName,
        'id' => $userId,
    ]);
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
