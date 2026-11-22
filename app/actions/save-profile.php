<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/auth/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    auth_redirect('/app/settings.php?tab=profile');
}

$user = auth_user();
if ($user === null) {
    auth_redirect('/auth/');
}

$roles = ['founder', 'developer', 'operations', 'support'];
$draft = [
    'given_name' => trim((string) ($_POST['given_name'] ?? '')),
    'family_name' => trim((string) ($_POST['family_name'] ?? '')),
    'preferred_name' => trim((string) ($_POST['preferred_name'] ?? '')),
    'username' => strtolower(trim((string) ($_POST['username'] ?? ''))),
    'job_role' => (string) ($_POST['job_role'] ?? ''),
    'phone' => trim((string) ($_POST['phone'] ?? '')),
    'website' => trim((string) ($_POST['website'] ?? '')),
    'timezone' => (string) ($_POST['timezone'] ?? ''),
    'week_start' => (string) ($_POST['week_start'] ?? 'monday'),
    'language_code' => (string) ($_POST['language_code'] ?? 'en'),
    'time_format' => (string) ($_POST['time_format'] ?? '24'),
];

$given = auth_clean_person_name($draft['given_name']);
$family = $draft['family_name'] === '' ? '' : auth_clean_person_name($draft['family_name']);
$preferred = $draft['preferred_name'] === '' ? '' : auth_clean_person_name($draft['preferred_name']);

if (($draft['given_name'] !== '' && $given === '') || ($draft['family_name'] !== '' && $family === '') || ($draft['preferred_name'] !== '' && $preferred === '')) {
    $_SESSION['profile_draft'] = $draft;
    auth_flash_set('error', 'Each name can be up to 80 characters.');
    auth_redirect('/app/settings.php?tab=profile');
}

if ($draft['username'] !== '' && preg_match('/^[a-z0-9_]{3,32}$/', $draft['username']) !== 1) {
    $_SESSION['profile_draft'] = $draft;
    auth_flash_set('error', 'Username must be 3 to 32 characters: letters, numbers, and underscores.');
    auth_redirect('/app/settings.php?tab=profile');
}

if (!in_array($draft['job_role'], $roles, true)) {
    $draft['job_role'] = '';
}

if ($draft['phone'] !== '' && preg_match('/^[0-9+().\s-]{6,32}$/', $draft['phone']) !== 1) {
    $_SESSION['profile_draft'] = $draft;
    auth_flash_set('error', 'Enter a phone number with digits, or leave it empty.');
    auth_redirect('/app/settings.php?tab=profile');
}

if ($draft['website'] !== '' && (
    !filter_var($draft['website'], FILTER_VALIDATE_URL)
    || !preg_match('#^https?://#i', $draft['website'])
)) {
    $_SESSION['profile_draft'] = $draft;
    auth_flash_set('error', 'Website must start with http:// or https://, or stay empty.');
    auth_redirect('/app/settings.php?tab=profile');
}

if ($draft['timezone'] !== '' && !in_array($draft['timezone'], DateTimeZone::listIdentifiers(), true)) {
    $draft['timezone'] = '';
}

if (!in_array($draft['week_start'], ['monday', 'sunday'], true)) {
    $draft['week_start'] = 'monday';
}
if ($draft['language_code'] !== 'en') {
    $draft['language_code'] = 'en';
}
if (!in_array($draft['time_format'], ['24', '12'], true)) {
    $draft['time_format'] = '24';
}

$current = auth_load_profile($user['id']);
if (empty($current['storage'])) {
    auth_flash_set('error', 'The name cannot be saved yet.');
    auth_redirect('/app/settings.php?tab=profile');
}

$profile = [
    'given_name' => $given,
    'family_name' => $family,
    'preferred_name' => $preferred,
    'username' => $draft['username'],
    'job_role' => $draft['job_role'],
    'phone' => $draft['phone'],
    'website' => $draft['website'],
    'timezone' => $draft['timezone'],
    'week_start' => $draft['week_start'],
    'language_code' => $draft['language_code'],
    'time_format' => $draft['time_format'],
];

try {
    if (!empty($current['details']) && $profile['username'] !== '' && auth_username_taken($profile['username'], $user['id'])) {
        $_SESSION['profile_draft'] = $draft;
        auth_flash_set('error', 'That username is already used.');
        auth_redirect('/app/settings.php?tab=profile');
    }

    auth_save_profile($user['id'], $profile, !empty($current['details']));
    unset($_SESSION['profile_draft']);
    auth_flash_set('notice', 'Profile saved.');
    auth_redirect('/app/settings.php?tab=profile');
} catch (Throwable $e) {
    $_SESSION['profile_draft'] = $draft;
    auth_flash_set('error', 'The profile could not be saved. Try again in a moment.');
    auth_redirect('/app/settings.php?tab=profile');
}
