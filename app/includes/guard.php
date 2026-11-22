<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/auth/includes/bootstrap.php';

function app_boot(): array
{
    $user = auth_user();
    if ($user === null) {
        auth_redirect('/auth/');
    }

    return [$user, auth_load_profile($user['id'])];
}

function app_require_profile(array $profile): void
{
    if (empty($profile['ready'])) {
        auth_redirect('/app/settings.php?tab=profile');
    }
}
