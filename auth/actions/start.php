<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    auth_redirect('/auth/');
}

$email = trim((string) ($_POST['email'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    auth_flash_set('error', 'Enter a valid email address.');
    auth_redirect('/auth/');
}

try {
    $user = auth_find_user_by_email($email);

    if ($user === null) {
        $user = auth_create_user($email);
    }

    $code = auth_create_login_code((string) $user['id']);
    auth_send_code_email((string) $user['email'], $code);
    auth_set_pending_user((string) $user['id'], (string) $user['email']);
    auth_redirect('/auth/verify.php');
} catch (Throwable $e) {
    auth_flash_set('error', 'Could not start sign in. Try again in a moment.');
    auth_redirect('/auth/');
}
