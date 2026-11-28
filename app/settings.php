<?php

require __DIR__ . '/includes/guard.php';
require __DIR__ . '/includes/avatar.php';

[$appUser, $appProfile] = app_boot();

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

$ledes = [
    'profile' => 'Your account details.',
    'notifications' => 'How we reach you.',
    'support' => 'Help with your account.',
];
$placeholders = [
    'notifications' => 'Notification preferences will live on this page.',
    'support' => 'Ways to reach support will live on this page.',
];

$navBase = 'index.php';
$settingsTab = $tab;
$headNav = require __DIR__ . '/includes/nav.php';

$headTitle = $tabs[$tab];
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
