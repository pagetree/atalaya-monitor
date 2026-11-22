<?php

$countries = require __DIR__ . '/countries.php';
$found = [
    '' => 'Choose one',
    'friend' => 'A friend or colleague',
    'search' => 'Search',
    'social' => 'Social',
    'article' => 'An article or post',
    'other' => 'Somewhere else',
];
$weeks = [
    'monday' => 'Monday',
    'sunday' => 'Sunday',
];
$hours = [
    '24' => '24 hour',
    '12' => '12 hour',
];

$val = static function (string $key) use ($appProfile, $draft): string {
    if (is_array($draft) && array_key_exists($key, $draft)) {
        return (string) $draft[$key];
    }
    return (string) ($appProfile[$key] ?? '');
};

$esc = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};

$initial = $val('given_name');
if ($initial === '') {
    $initial = (string) ($appUser['email'] ?? '');
}
$letter = function_exists('mb_substr') ? mb_substr($initial, 0, 1) : substr($initial, 0, 1);
$letter = function_exists('mb_strtoupper') ? mb_strtoupper($letter) : strtoupper($letter);

$zones = [];
foreach (DateTimeZone::listIdentifiers() as $zone) {
    $parts = explode('/', $zone, 2);
    $zones[$parts[0]][] = $zone;
}

$avatar = app_avatar_url((string) $appUser['id']);
$verified = !empty($appProfile['email_verified_at']);
?>
<div class="sheet-card">
  <div class="sheet-block">
    <div class="portrait">
      <div class="portrait-face" aria-hidden="true">
        <?php if ($avatar !== null): ?>
          <img src="<?= $esc($avatar) ?>" alt="">
        <?php else: ?>
          <span><?= $esc($letter !== '' ? $letter : '?') ?></span>
        <?php endif; ?>
      </div>
      <div>
        <h2>Profile picture</h2>
        <p class="sheet-sub">PNG, JPEG, or GIF. 10 MB at most.</p>
        <div class="portrait-actions">
          <form method="post" action="actions/avatar.php" enctype="multipart/form-data">
            <label class="btn-ink file-btn">
              <svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 11V3M5 5.5L8 2.5 11 5.5"/><path d="M3 11.5v1.2h10v-1.2"/></svg>
              Upload
              <input id="avatar-file" type="file" name="avatar" accept="image/png,image/jpeg,image/gif" required>
            </label>
            <noscript><button class="btn-ink" type="submit">Upload picture</button></noscript>
          </form>
          <form method="post" action="actions/avatar.php">
            <input type="hidden" name="remove" value="1">
            <button class="btn-danger" type="submit">
              <svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M3 4.2h10M6.2 4.2V2.8h3.6v1.4M4.2 4.2l.6 9h6.4l.6-9"/></svg>
              Remove
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <form method="post" action="actions/save-profile.php">
    <div class="sheet-block">
      <h2>Basic details</h2>
      <p class="sheet-sub">Keep the contact and identity fields current.</p>
      <div class="field-grid">
        <label class="field">
          <span class="label-row">First name</span>
          <input type="text" name="given_name" maxlength="80" autocomplete="given-name" value="<?= $esc($val('given_name')) ?>">
        </label>
        <label class="field">
          <span class="label-row">Last name</span>
          <input type="text" name="family_name" maxlength="80" autocomplete="family-name" placeholder="Optional" value="<?= $esc($val('family_name')) ?>">
        </label>
        <label class="field">
          <span class="label-row">Primary email <abbr class="hint-mark" title="This address receives the sign in code.">i</abbr></span>
          <span class="email-row">
            <input type="email" value="<?= $esc((string) $appUser['email']) ?>" readonly>
            <?php if ($verified): ?><span class="verified">Verified</span><?php endif; ?>
          </span>
          <span class="field-note">This address receives the sign in code.</span>
        </label>
        <label class="field">
          <span class="label-row">Preferred name <abbr class="hint-mark" title="Used when it should differ from the first name.">i</abbr></span>
          <input type="text" name="preferred_name" maxlength="80" autocomplete="nickname" placeholder="Optional" value="<?= $esc($val('preferred_name')) ?>">
        </label>
        <label class="field">
          <span class="label-row">Country</span>
          <select name="country" autocomplete="country">
            <option value="">Choose a country</option>
            <?php foreach ($countries as $code => $countryName): ?>
              <option value="<?= $esc($code) ?>"<?= $val('country') === $code ? ' selected' : '' ?>><?= $esc($countryName) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="field">
          <span class="label-row">How did you find Atalaya?</span>
          <select name="found_via">
            <?php foreach ($found as $foundKey => $foundLabel): ?>
              <option value="<?= $esc($foundKey) ?>"<?= $val('found_via') === $foundKey ? ' selected' : '' ?>><?= $esc($foundLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="field">
          <span class="label-row">Phone number</span>
          <input type="tel" name="phone" maxlength="32" autocomplete="tel" placeholder="Phone number" value="<?= $esc($val('phone')) ?>">
          <span class="field-note">Optional. Sign in does not use this number.</span>
        </label>
        <label class="field">
          <span class="label-row">Website</span>
          <input type="url" name="website" maxlength="200" inputmode="url" placeholder="https://" value="<?= $esc($val('website')) ?>">
        </label>
      </div>
    </div>

    <div class="sheet-block">
      <h2>Regional preferences</h2>
      <p class="sheet-sub">Saved on the account. The board still shows times in this browser.</p>
      <div class="field-grid">
        <label class="field">
          <span class="label-row">Preferred timezone <abbr class="hint-mark" title="Stored for this account.">i</abbr></span>
          <select name="timezone">
            <option value="">Choose a timezone</option>
            <?php foreach ($zones as $group => $groupZones): ?>
              <optgroup label="<?= $esc($group) ?>">
                <?php foreach ($groupZones as $zone): ?>
                  <option value="<?= $esc($zone) ?>"<?= $val('timezone') === $zone ? ' selected' : '' ?>><?= $esc(str_replace('_', ' ', $zone)) ?></option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="field">
          <span class="label-row">Start week on <abbr class="hint-mark" title="Stored for this account.">i</abbr></span>
          <select name="week_start">
            <?php foreach ($weeks as $weekKey => $weekLabel): ?>
              <option value="<?= $esc($weekKey) ?>"<?= $val('week_start') === $weekKey ? ' selected' : '' ?>><?= $esc($weekLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="field">
          <span class="label-row">Language</span>
          <select name="language_code">
            <option value="en" selected>English</option>
          </select>
        </label>
        <label class="field">
          <span class="label-row">Time format</span>
          <select name="time_format">
            <?php foreach ($hours as $hourKey => $hourLabel): ?>
              <option value="<?= $esc($hourKey) ?>"<?= $val('time_format') === $hourKey ? ' selected' : '' ?>><?= $esc($hourLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
    </div>

    <div class="sheet-foot">
      <p>Save to keep this profile on the account.</p>
      <div class="sheet-actions">
        <button class="btn-quiet" type="reset">Reset</button>
        <button class="btn-ink" type="submit">Save changes</button>
      </div>
    </div>
  </form>
</div>
<script>
document.getElementById('avatar-file').addEventListener('change', function () {
  if (this.files && this.files.length) this.form.submit();
});
</script>
