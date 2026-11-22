<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$user = auth_user();
if ($user !== null) {
    auth_redirect('/app/');
}

$pending = auth_get_pending_user();
if ($pending === null) {
    auth_flash_set('error', 'Start with your email first.');
    auth_redirect('/auth/');
}

$error = auth_flash_get('error');
auth_flash_get('notice');
$masked = preg_replace('/(^.).*(@.*$)/', '$1***$2', $pending['email']) ?? $pending['email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Enter your code · Atalaya</title>
  <meta name="description" content="Enter the 6 digit verification code sent to your email.">
  <link rel="icon" href="../assets/img/logo-icon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700;800&display=swap">
  <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
  <header class="top code-shell">
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

  <main id="content" class="shell code-shell" tabindex="-1">
    <section class="panel" aria-labelledby="verify-title">
      <h1 id="verify-title">Enter the 6 digit code</h1>
      <p class="lede">We sent a code to <?= htmlspecialchars($masked, ENT_QUOTES, 'UTF-8') ?>. It stays valid for 30 minutes.</p>

      <?php if ($error !== null): ?>
        <p class="banner" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>

      <form class="stack" method="post" action="actions/confirm.php">
        <fieldset class="digits-set">
          <legend>Verification code</legend>
          <div class="digits">
            <?php for ($i = 1; $i <= 6; $i++): ?>
              <input type="text" name="code[]" inputmode="numeric" pattern="[0-9]" maxlength="1" required autocomplete="<?= $i === 1 ? 'one-time-code' : 'off' ?>" aria-label="Digit <?= $i ?> of 6"<?= $i === 1 ? ' autofocus' : '' ?>>
            <?php endfor; ?>
          </div>
        </fieldset>
        <button class="solid fit" type="submit">Verify and open the board</button>
      </form>

      <p class="foot">Wrong account? <a href="./">Go back</a> and start again.</p>
    </section>
  </main>
  <script src="assets/js/code.js"></script>
</body>
</html>
