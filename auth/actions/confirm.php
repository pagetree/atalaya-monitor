<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    auth_redirect('/auth/verify.php');
}

$pending = auth_get_pending_user();
if ($pending === null) {
    auth_flash_set('error', 'Start with your email first.');
    auth_redirect('/auth/');
}

$code = (string) ($_POST['code'] ?? '');
$code = preg_replace('/\D+/', '', $code) ?? '';

if (strlen($code) !== 9) {
    auth_flash_set('error', 'Enter the 9 digit code from your email.');
    auth_redirect('/auth/verify.php');
}

try {
    $ok = auth_consume_login_code($pending['id'], $code);
    if (!$ok) {
        auth_flash_set('error', 'That code is wrong or expired.');
        auth_redirect('/auth/verify.php');
    }

    auth_mark_email_verified($pending['id']);
    auth_login_user($pending['id'], $pending['email']);
    auth_redirect('/app/settings.php?tab=profile');
} catch (Throwable $e) {
    auth_flash_set('error', 'Could not verify the code. Try again.');
    auth_redirect('/auth/verify.php');
}
