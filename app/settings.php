<?php

require __DIR__ . '/includes/guard.php';
require __DIR__ . '/includes/avatar.php';

[$appUser, $appProfile] = app_boot();
$onAccount = true;

$tabs = [
    'profile' => 'Profile',
    'notifications' => 'Notifications',
    'support' => 'Support',
];

$tab = (string) ($_GET['tab'] ?? 'profile');
if (!isset($tabs[$tab])) {
    $tab = 'profile';
}

$draft = null;
if (isset($_SESSION['profile_draft']) && is_array($_SESSION['profile_draft'])) {
    $draft = $_SESSION['profile_draft'];
    unset($_SESSION['profile_draft']);
}

$error = auth_flash_get('error');
$notice = auth_flash_get('notice');

$icons = [
    'profile' => '<circle cx="8" cy="5.4" r="2.2"/><path d="M3.4 13.2c.7-2.3 2.4-3.4 4.6-3.4s3.9 1.1 4.6 3.4"/>',
    'notifications' => '<path d="M8 2.2a3.6 3.6 0 0 1 3.6 3.6v2.2l1.2 2H3.2l1.2-2V5.8A3.6 3.6 0 0 1 8 2.2z"/><path d="M6.6 12.2a1.5 1.5 0 0 0 2.8 0"/>',
    'support' => '<circle cx="8" cy="8" r="5.2"/><circle cx="8" cy="8" r="2"/><path d="M4.2 4.2l2.2 2.2M11.8 4.2l-2.2 2.2M4.2 11.8l2.2-2.2M11.8 11.8l-2.2-2.2"/>',
];
$hrefs = [
    'profile' => 'settings.php?tab=profile',
    'notifications' => 'settings.php?tab=notifications',
    'support' => 'settings.php?tab=support',
];
$ledes = [
    'profile' => 'Your account details.',
    'notifications' => 'How we reach you.',
    'support' => 'Help with your account.',
];
$placeholders = [
    'notifications' => 'Notification preferences will live on this page.',
    'support' => 'Ways to reach support will live on this page.',
];

$headNav = [];
foreach ($tabs as $key => $label) {
    $headNav[] = [
        'href' => $hrefs[$key],
        'label' => $label,
        'icon' => $icons[$key],
        'current' => $key === $tab,
    ];
}

$headTitle = 'Settings';
$headLede = $ledes[$tab];
$pageTitle = $tabs[$tab] . ' · atalaya';

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

  <?php if ($tab === 'profile'): ?>
    <?php if (empty($appProfile['storage'])): ?>
      <div class="sheet-card"><div class="sheet-block"><p>The form opens once the name fields exist on the account.</p></div></div>
    <?php else: ?>
      <?php require __DIR__ . '/includes/view-profile.php'; ?>
    <?php endif; ?>
  <?php else: ?>
    <div class="sheet-card"><div class="sheet-block"><p><?= htmlspecialchars($placeholders[$tab], ENT_QUOTES, 'UTF-8') ?></p></div></div>
  <?php endif; ?>
</main>
</body>
</html>
