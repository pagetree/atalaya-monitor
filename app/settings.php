<?php

require __DIR__ . '/includes/guard.php';
require __DIR__ . '/includes/avatar.php';

[$appUser, $appProfile] = app_boot();
$onAccount = true;

$tabs = [
    'general' => 'General',
    'profile' => 'Profile',
    'members' => 'Members',
    'permissions' => 'Permissions',
    'notifications' => 'Notifications',
    'integrations' => 'Integrations',
];
$tab = (string) ($_GET['tab'] ?? 'general');
if (!isset($tabs[$tab])) {
    $tab = 'general';
}

$draft = null;
if (isset($_SESSION['profile_draft']) && is_array($_SESSION['profile_draft'])) {
    $draft = $_SESSION['profile_draft'];
    unset($_SESSION['profile_draft']);
}

$error = auth_flash_get('error');
$notice = auth_flash_get('notice');

$icons = [
    'general' => '<circle cx="8" cy="8" r="2.1"/><path d="M8 1.7v1.7M8 12.6v1.7M1.7 8h1.7M12.6 8h1.7M3.4 3.4l1.2 1.2M11.4 11.4l1.2 1.2M12.6 3.4l-1.2 1.2M4.6 11.4l-1.2 1.2"/>',
    'profile' => '<circle cx="8" cy="5.4" r="2.2"/><path d="M3.4 13.2c.7-2.3 2.4-3.4 4.6-3.4s3.9 1.1 4.6 3.4"/>',
    'members' => '<circle cx="6" cy="5.6" r="1.8"/><circle cx="11" cy="6.2" r="1.4"/><path d="M2.6 13c.5-2 1.9-3 3.4-3s2.9 1 3.4 3"/><path d="M9.4 10.2c.7-.3 1.5-.4 2.2-.2 1 .3 1.7 1.2 2.1 2.8"/>',
    'permissions' => '<path d="M8 1.8l4.5 1.6v4.2c0 2.6-1.8 4.4-4.5 5.6-2.7-1.2-4.5-3-4.5-5.6V3.4L8 1.8z"/>',
    'notifications' => '<path d="M8 2.2a3.6 3.6 0 0 1 3.6 3.6v2.2l1.2 2H3.2l1.2-2V5.8A3.6 3.6 0 0 1 8 2.2z"/><path d="M6.6 12.2a1.5 1.5 0 0 0 2.8 0"/>',
    'integrations' => '<circle cx="4" cy="8" r="1.5"/><circle cx="12" cy="4.4" r="1.5"/><circle cx="12" cy="11.6" r="1.5"/><path d="M5.4 7.3L10.6 5M5.5 8.7l5.1 2.2"/>',
];

$headNav = [];
foreach ($tabs as $key => $label) {
    $headNav[] = [
        'href' => 'settings.php?tab=' . $key,
        'label' => $label,
        'icon' => $icons[$key],
        'current' => $key === $tab,
    ];
}

$headTitle = 'Admin Settings';
$headLede = 'Manage your profile, workspace defaults, access, and connected tools.';
$pageTitle = 'Admin Settings · atalaya';

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

  <?php if ($tab === 'general'): ?>
    <div class="sheet-card">
      <div class="sheet-block">
        <h2>Account</h2>
        <p class="sheet-sub">Signed in as <?= htmlspecialchars((string) $appUser['email'], ENT_QUOTES, 'UTF-8') ?>.</p>
        <?php $shownName = trim($appProfile['given_name'] . ' ' . $appProfile['family_name']); ?>
        <?php if ($shownName !== ''): ?>
          <p><?= htmlspecialchars($shownName, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <p><a href="index.php">Open the board</a></p>
      </div>
    </div>
  <?php elseif ($tab === 'profile'): ?>
    <?php if (empty($appProfile['storage'])): ?>
      <div class="sheet-card"><div class="sheet-block"><p>The form opens once the name fields exist on the account.</p></div></div>
    <?php else: ?>
      <?php require __DIR__ . '/includes/view-profile.php'; ?>
    <?php endif; ?>
  <?php elseif ($tab === 'members'): ?>
    <div class="sheet-card"><div class="sheet-block"><h2>Members</h2><p class="sheet-sub">This account is only you. Adding other people is not open yet.</p></div></div>
  <?php elseif ($tab === 'permissions'): ?>
    <div class="sheet-card"><div class="sheet-block"><h2>Permissions</h2><p class="sheet-sub">You are the only person with access. Roles for other people are not open yet.</p></div></div>
  <?php elseif ($tab === 'notifications'): ?>
    <div class="sheet-card"><div class="sheet-block"><h2>Notifications</h2><p class="sheet-sub">Sign in codes go to <?= htmlspecialchars((string) $appUser['email'], ENT_QUOTES, 'UTF-8') ?>. Monitor alerts stay on the board.</p></div></div>
  <?php else: ?>
    <div class="sheet-card"><div class="sheet-block"><h2>Integrations</h2><p class="sheet-sub">No tools are connected. Connections are not open yet.</p></div></div>
  <?php endif; ?>
</main>
</body>
</html>
