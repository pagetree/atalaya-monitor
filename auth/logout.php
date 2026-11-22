<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

auth_logout();
auth_session_start();
auth_flash_set('notice', 'Signed out.');
auth_redirect('/auth/');
