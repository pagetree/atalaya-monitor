<?php

require __DIR__ . '/includes/guard.php';
require __DIR__ . '/includes/avatar.php';

[$appUser, $appProfile] = app_boot();
$onAccount = true;

$tabs = [
    'profile' => 'Profile',
    'alerts' => 'Alerts',
    'logs' => 'Logs',
];

$draft = null;
if (isset($_SESSION['profile_draft']) && is_array($_SESSION['profile_draft'])) {
    $draft = $_SESSION['profile_draft'];
    unset($_SESSION['profile_draft']);
}

$error = auth_flash_get('error');
$notice = auth_flash_get('notice');

$icons = [
    'profile' => '<circle cx="8" cy="5.4" r="2.2"/><path d="M3.4 13.2c.7-2.3 2.4-3.4 4.6-3.4s3.9 1.1 4.6 3.4"/>',
    'alerts' => '<path d="M8 2.2a3.6 3.6 0 0 1 3.6 3.6v2.2l1.2 2H3.2l1.2-2V5.8A3.6 3.6 0 0 1 8 2.2z"/><path d="M6.6 12.2a1.5 1.5 0 0 0 2.8 0"/>',
    'logs' => '<path d="M4 2.5h6.2L13 5.3V13.5H4z"/><path d="M10 2.5V5.5h3M6 8h4M6 10.5h4"/>',
];
$hrefs = [
    'profile' => 'settings.php?tab=profile',
    'alerts' => 'index.php#alerts',
    'logs' => 'index.php#log',
];

$headNav = [];
foreach ($tabs as $key => $label) {
    $headNav[] = [
        'href' => $hrefs[$key],
        'label' => $label,
        'icon' => $icons[$key],
        'current' => $key === 'profile',
    ];
}

$headTitle = 'Settings';
$headLede = 'Your profile, the alert list, and the check log.';
$pageTitle = 'Settings · atalaya';
$bodyClass = 'account';

require __DIR__ . '/includes/head.php';
?>
<header class="dash">
  <?php require __DIR__ . '/includes/brandbar.php'; ?>
  <?php require __DIR__ . '/includes/page-head.php'; ?>
</header>
<main id="content" tabindex="-1">
  <?php if ($error !== null): ?>
    <p class="banner" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if ($notice !== null): ?>
    <p class="banner ok" role="status"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if (empty($appProfile['storage'])): ?>
    <div class="sheet-card"><div class="sheet-block"><p>The form opens once the name fields exist on the account.</p></div></div>
  <?php else: ?>
    <?php require __DIR__ . '/includes/view-profile.php'; ?>
  <?php endif; ?>
</main>
</body>
</html>
