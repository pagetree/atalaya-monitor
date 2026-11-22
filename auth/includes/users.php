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

function auth_profile_blank(): array
{
    return [
        'given_name' => '',
        'family_name' => '',
        'preferred_name' => '',
        'username' => '',
        'job_role' => '',
        'phone' => '',
        'website' => '',
        'timezone' => '',
        'week_start' => 'monday',
        'language_code' => 'en',
        'time_format' => '24',
        'email_verified_at' => null,
        'ready' => false,
        'storage' => true,
        'details' => true,
    ];
}

function auth_profile_from_row(array $row, bool $details): array
{
    $profile = auth_profile_blank();
    $profile['given_name'] = trim((string) ($row['given_name'] ?? ''));
    $profile['family_name'] = trim((string) ($row['family_name'] ?? ''));
    $profile['email_verified_at'] = $row['email_verified_at'] ?? null;
    $profile['ready'] = $profile['given_name'] !== '';
    $profile['storage'] = true;
    $profile['details'] = $details;
    if (!$details) {
        return $profile;
    }

    foreach (['preferred_name', 'username', 'job_role', 'phone', 'website', 'timezone', 'week_start', 'language_code', 'time_format'] as $key) {
        $value = trim((string) ($row[$key] ?? ''));
        if ($value !== '') {
            $profile[$key] = $value;
        }
    }

    return $profile;
}

function auth_load_profile(string $userId): array
{
    $full = 'SELECT given_name, family_name, preferred_name, username, job_role, phone, website, timezone, week_start, language_code, time_format, email_verified_at
             FROM users
             WHERE id = :id
             LIMIT 1';
    try {
        $stmt = auth_db()->prepare($full);
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();
        if ($row === false) {
            return auth_profile_blank();
        }
        return auth_profile_from_row($row, true);
    } catch (PDOException $e) {
        if ((string) $e->getCode() !== '42703') {
            throw $e;
        }
    }

    try {
        $stmt = auth_db()->prepare(
            'SELECT given_name, family_name, email_verified_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();
        if ($row === false) {
            $profile = auth_profile_blank();
            $profile['details'] = false;
            return $profile;
        }
        return auth_profile_from_row($row, false);
    } catch (PDOException $e) {
        if ((string) $e->getCode() === '42703') {
            $profile = auth_profile_blank();
            $profile['storage'] = false;
            $profile['details'] = false;
            return $profile;
        }
        throw $e;
    }
}

function auth_username_taken(string $username, string $userId): bool
{
    $stmt = auth_db()->prepare(
        'SELECT id
         FROM users
         WHERE username = :username AND id <> :id
         LIMIT 1'
    );
    $stmt->execute([
        'username' => $username,
        'id' => $userId,
    ]);
    return $stmt->fetch() !== false;
}

function auth_save_profile(string $userId, array $profile, bool $withDetails): void
{
    if (!$withDetails) {
        $stmt = auth_db()->prepare(
            'UPDATE users
             SET given_name = :given_name,
                 family_name = :family_name,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'given_name' => $profile['given_name'],
            'family_name' => $profile['family_name'] !== '' ? $profile['family_name'] : null,
            'id' => $userId,
        ]);
        return;
    }

    $stmt = auth_db()->prepare(
        'UPDATE users
         SET given_name = :given_name,
             family_name = :family_name,
             preferred_name = :preferred_name,
             username = :username,
             job_role = :job_role,
             phone = :phone,
             website = :website,
             timezone = :timezone,
             week_start = :week_start,
             language_code = :language_code,
             time_format = :time_format,
             updated_at = NOW()
         WHERE id = :id'
    );
    $optional = static function (string $value): ?string {
        return $value === '' ? null : $value;
    };
    $stmt->execute([
        'given_name' => $profile['given_name'],
        'family_name' => $optional($profile['family_name']),
        'preferred_name' => $optional($profile['preferred_name']),
        'username' => $optional($profile['username']),
        'job_role' => $optional($profile['job_role']),
        'phone' => $optional($profile['phone']),
        'website' => $optional($profile['website']),
        'timezone' => $optional($profile['timezone']),
        'week_start' => $profile['week_start'],
        'language_code' => $profile['language_code'],
        'time_format' => $profile['time_format'],
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
