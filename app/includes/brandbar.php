<?php

$homeHref = 'index.php';
$onAccount = !empty($onAccount);
?>
<div class="dash-brand">
  <a class="brand" href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>">
    <img class="pulse" src="../assets/img/logo-icon.svg" alt="atalaya" width="56" height="40">
    <span class="mark-reveal">
      <span class="mark-clip">
        <img class="wordmark" src="../assets/img/logo-wordmark.svg" alt="" width="115" height="40">
      </span>
    </span>
  </a>
  <nav class="account-links" aria-label="Main">
    <a href="index.php"<?= $onAccount ? '' : ' aria-current="page"' ?>>App</a>
    <a href="settings.php"<?= $onAccount ? ' aria-current="page"' : '' ?>>Settings</a>
    <a href="../auth/logout.php">Log out</a>
  </nav>
</div>
