<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/auth/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/avatar.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    auth_redirect('/app/settings.php?tab=profile');
}

$user = auth_user();
if ($user === null) {
    auth_redirect('/auth/');
}

if (isset($_POST['remove'])) {
    app_avatar_remove($user['id']);
    auth_flash_set('notice', 'Profile picture removed.');
    auth_redirect('/app/settings.php?tab=profile');
}

$message = app_avatar_store($user['id'], $_FILES['avatar'] ?? []);
if ($message !== null) {
    auth_flash_set('error', $message);
} else {
    auth_flash_set('notice', 'Profile picture saved.');
}
auth_redirect('/app/settings.php?tab=profile');
