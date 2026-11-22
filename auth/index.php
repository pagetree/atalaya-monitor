<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$user = auth_user();
if ($user !== null) {
    $profile = auth_load_profile($user['id']);
    auth_redirect(!empty($profile['ready']) ? '/app/' : '/app/settings.php?tab=profile');
}

$error = auth_flash_get('error');
$notice = auth_flash_get('notice');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in · Atalaya</title>
  <meta name="description" content="Sign in or create your Atalaya account with your email.">
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
    <a class="top-link" href="../">Back home</a>
  </header>

  <main id="content" class="shell" tabindex="-1">
    <section class="panel" aria-labelledby="auth-title">
      <p class="kicker">Atalaya account</p>
      <h1 id="auth-title">Your email</h1>
      <p class="lede">If the email is new, we create the account. Then we email a 9 digit code.</p>

      <?php if ($error !== null): ?>
        <p class="banner" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <?php if ($notice !== null): ?>
        <p class="banner ok" role="status"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>

      <form class="stack" method="post" action="actions/start.php" autocomplete="on">
        <label>
          Email
          <input type="email" name="email" required maxlength="254" autocomplete="email" autofocus>
        </label>
        <button class="solid" type="submit">Email me the code</button>
      </form>

      <p class="foot">Next step is the code from your inbox. Valid for 30 minutes.</p>
    </section>
  </main>
</body>
</html>
