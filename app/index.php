<?php

require __DIR__ . '/includes/guard.php';
[$appUser, $appProfile] = app_boot();
app_require_profile($appProfile);
$pageTitle = 'Monitors · atalaya';

require __DIR__ . '/includes/head.php';
?>
<?php require __DIR__ . '/includes/topbar.php'; ?>

    <main id="content" tabindex="-1">
      <p id="banner" class="banner" role="alert" hidden></p>
      <noscript>
        <p class="banner">atalaya needs JavaScript to run checks in this browser.</p>
      </noscript>

      <?php require __DIR__ . '/includes/view-monitors.php'; ?>
      <?php require __DIR__ . '/includes/view-alerts.php'; ?>
      <?php require __DIR__ . '/includes/view-log.php'; ?>
      <?php require __DIR__ . '/includes/view-settings.php'; ?>
    </main>
<?php require __DIR__ . '/includes/drawer.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
