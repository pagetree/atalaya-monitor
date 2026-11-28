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
      <?php if (!empty($item['menu']) && is_array($item['menu'])): ?>
        <details class="nav-drop">
          <summary<?= !empty($item['current']) ? ' aria-current="page"' : '' ?>>
            <svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><?= $item['icon'] ?></svg>
            <?= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?>
            <svg class="nav-chevron" viewBox="0 0 16 16" aria-hidden="true"><path d="M4 6.5 L8 10.5 L12 6.5"/></svg>
          </summary>
          <div class="nav-menu">
            <?php foreach ($item['menu'] as $child): ?>
              <a href="<?= htmlspecialchars((string) $child['href'], ENT_QUOTES, 'UTF-8') ?>"<?= !empty($child['current']) ? ' aria-current="page"' : '' ?>>
                <svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><?= $child['icon'] ?></svg>
                <?= htmlspecialchars((string) $child['label'], ENT_QUOTES, 'UTF-8') ?>
              </a>
            <?php endforeach; ?>
          </div>
        </details>
      <?php else: ?>
        <a href="<?= htmlspecialchars((string) $item['href'], ENT_QUOTES, 'UTF-8') ?>"<?= !empty($item['current']) ? ' aria-current="page"' : '' ?>>
          <svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><?= $item['icon'] ?></svg>
          <?= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>
</div>
<script type="module" src="assets/js/nav.js"></script>
