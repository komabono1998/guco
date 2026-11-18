<?php
/**
 * "Related pages" cards shown at the foot of landing pages, so pages that no longer have their
 * own menu entry stay one click away. $items: [link, icon, title, description].
 */
function related_links(string $eyebrow, string $title, array $items): void
{
    ?>
<section class="section rl" aria-label="<?= e($title) ?>">
  <div class="rl-head"><span class="eyebrow-s"><?= e($eyebrow) ?></span><h2><?= e($title) ?></h2></div>
  <div class="rl-grid">
    <?php foreach ($items as $i => [$href, $ic, $t, $d]): ?>
      <a class="rl-card" href="<?= e(url($href)) ?>" style="--d:<?= $i * 80 ?>ms">
        <span class="rl-ic"><?= icon($ic, 26) ?></span>
        <span class="rl-text"><strong><?= e($t) ?></strong><span><?= e($d) ?></span></span>
        <i aria-hidden="true">&rarr;</i>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php
}
