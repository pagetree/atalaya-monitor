<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    auth_redirect('/auth/');
}

$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    auth_flash_set('error', 'Enter a valid email address.');
    auth_redirect('/auth/');
}

if (strlen($password) < 8) {
    auth_flash_set('error', 'Password must be at least 8 characters.');
    auth_redirect('/auth/');
}

try {
    $user = auth_find_user_by_email($email);

    if ($user === null) {
        $user = auth_create_user($email, $password);
    } else {
        if (!auth_verify_password($user, $password)) {
            auth_flash_set('error', 'Email or password is wrong.');
            auth_redirect('/auth/');
        }
    }

    $code = auth_create_login_code((string) $user['id']);
    auth_send_code_email((string) $user['email'], $code);
    auth_set_pending_user((string) $user['id'], (string) $user['email']);
    auth_flash_set('notice', 'We sent a 9 digit code to your email. It is valid for 30 minutes.');
    auth_redirect('/auth/verify.php');
} catch (Throwable $e) {
    auth_flash_set('error', 'Could not start sign in. Try again in a moment.');
    auth_redirect('/auth/');
}
