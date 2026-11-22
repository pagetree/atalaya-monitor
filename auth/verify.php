<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$user = auth_user();
if ($user !== null) {
    $profile = auth_load_profile($user['id']);
    auth_redirect(!empty($profile['ready']) ? '/app/' : '/app/settings.php?tab=profile');
}

$pending = auth_get_pending_user();
if ($pending === null) {
    auth_flash_set('error', 'Start with your email first.');
    auth_redirect('/auth/');
}

$error = auth_flash_get('error');
$notice = auth_flash_get('notice');
$masked = preg_replace('/(^.).*(@.*$)/', '$1***$2', $pending['email']) ?? $pending['email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Enter your code · Atalaya</title>
  <meta name="description" content="Enter the 9 digit verification code sent to your email.">
  <link rel="icon" href="../assets/img/logo-icon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700;800&display=swap">
  <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
  <a class="skip" href="#content">Skip to content</a>
  <header class="top">
    <a class="brand" href="../">
      <img class="pulse" src="../assets/img/logo-icon.svg" alt="atalaya" width="48" height="34">
      <span class="mark-reveal">
        <span class="mark-clip">
          <img class="wordmark" src="../assets/img/logo-wordmark.svg" alt="" width="98" height="34">
        </span>
      </span>
    </a>
    <a class="top-link" href="./">Use another email</a>
  </header>

  <main id="content" class="shell" tabindex="-1">
    <section class="panel" aria-labelledby="verify-title">
      <p class="kicker">Check your inbox</p>
      <h1 id="verify-title">Enter the 9 digit code</h1>
      <p class="lede">We sent a code to <?= htmlspecialchars($masked, ENT_QUOTES, 'UTF-8') ?>. It stays valid for 30 minutes.</p>

      <?php if ($error !== null): ?>
        <p class="banner" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <?php if ($notice !== null): ?>
        <p class="banner ok" role="status"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>

      <form class="stack" method="post" action="actions/confirm.php" autocomplete="one-time-code">
        <label>
          Verification code
          <input class="code" type="text" name="code" inputmode="numeric" pattern="[0-9]{9}" maxlength="9" minlength="9" required autofocus placeholder="123456789" autocomplete="one-time-code">
        </label>
        <button class="solid" type="submit">Verify and open the board</button>
      </form>

      <p class="foot">Wrong account? <a href="./">Go back</a> and start again.</p>
    </section>
  </main>
</body>
</html>
