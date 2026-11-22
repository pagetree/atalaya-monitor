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

$given = auth_clean_person_name((string) ($_POST['given_name'] ?? ''));
$family = auth_clean_person_name((string) ($_POST['family_name'] ?? ''));

if ($given === '' || $family === '') {
    $_SESSION['profile_draft'] = [
        'given_name' => trim((string) ($_POST['given_name'] ?? '')),
        'family_name' => trim((string) ($_POST['family_name'] ?? '')),
    ];
    auth_flash_set('error', 'Enter your first name and your last name. Each one can be up to 80 characters.');
    auth_redirect('/app/settings.php?tab=profile');
}

try {
    auth_save_profile($user['id'], $given, $family);
    unset($_SESSION['profile_draft']);
    auth_flash_set('notice', 'Name saved. The board is open.');
    auth_redirect('/app/settings.php?tab=profile');
} catch (Throwable $e) {
    auth_flash_set('error', 'The name could not be saved. Try again in a moment.');
    auth_redirect('/app/settings.php?tab=profile');
}
