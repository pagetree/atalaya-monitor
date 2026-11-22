<?php

require __DIR__ . '/includes/guard.php';
[$appUser, $appProfile] = app_boot();
$pageTitle = 'Main · atalaya';

require __DIR__ . '/includes/head.php';
?>
<?php require __DIR__ . '/includes/topbar.php'; ?>

    <main id="content" tabindex="-1">
      <p id="banner" class="banner" role="alert" hidden></p>
      <noscript>
        <p class="banner">atalaya needs JavaScript to show this board.</p>
      </noscript>

      <?php require __DIR__ . '/includes/view-main.php'; ?>
      <?php require __DIR__ . '/includes/view-monitors.php'; ?>
      <?php require __DIR__ . '/includes/view-add.php'; ?>
      <?php require __DIR__ . '/includes/view-alerts.php'; ?>
      <?php require __DIR__ . '/includes/view-log.php'; ?>
      <?php require __DIR__ . '/includes/view-status.php'; ?>
      <?php require __DIR__ . '/includes/view-settings.php'; ?>
    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
