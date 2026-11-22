<?php

$headTitle = (string) ($headTitle ?? '');
$headLede = (string) ($headLede ?? '');
$headTitleId = (string) ($headTitleId ?? '');
$headNav = is_array($headNav ?? null) ? $headNav : [];
$titleAttr = $headTitleId !== '' ? ' id="' . htmlspecialchars($headTitleId, ENT_QUOTES, 'UTF-8') . '"' : '';
$ledeAttr = $headTitleId !== '' ? ' id="' . htmlspecialchars($headTitleId, ENT_QUOTES, 'UTF-8') . '-lede"' : '';
?>
<div class="page-head">
  <h1<?= $titleAttr ?>><?= htmlspecialchars($headTitle, ENT_QUOTES, 'UTF-8') ?></h1>
  <p class="page-lede"<?= $ledeAttr ?>><?= htmlspecialchars($headLede, ENT_QUOTES, 'UTF-8') ?></p>
  <nav class="page-nav" aria-label="Sections">
    <?php foreach ($headNav as $item): ?>
      <a href="<?= htmlspecialchars((string) $item['href'], ENT_QUOTES, 'UTF-8') ?>"<?= !empty($item['current']) ? ' aria-current="page"' : '' ?>>
        <svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><?= $item['icon'] ?></svg>
        <?= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?>
      </a>
    <?php endforeach; ?>
  </nav>
</div>
