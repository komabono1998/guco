<?php
$pageTitle = 'Awards & Hall of Fame';
require_once __DIR__ . '/includes/bootstrap.php';
$pageDesc = 'GUCO awards: Outstanding Achievement and Distinguished Service awards for Gulu University alumni.';
require __DIR__ . '/includes/header.php';
$awards = q('SELECT * FROM awards WHERE is_public = 1 ORDER BY id')->fetchAll();
$status = ['not_open' => ['Nominations not open', 'warn'], 'open' => ['Nominations open', ''], 'closed' => ['Nominations closed', 'bad']];
?>
<div class="page-head"><h1>Awards &amp; Hall of Fame</h1><p>Celebrating outstanding alumni</p></div>
<div class="lead"><?= cr('awards.intro') ?></div>
<div class="split" style="margin:24px 0">
  <?php if ($img = c_image('awards.image')): ?><figure class="photo"><img src="<?= e($img) ?>" alt="GUCO awards" loading="lazy"></figure><?php else: ?><?= dinner_figure(4, '', 'Certificates of Recognition are presented at GUCO events.') ?><?php endif; ?>
  <div class="card accent"><h2 style="margin-top:0">Recognising excellence</h2><?= cr('awards.feature') ?></div>
</div>
<div class="grid two">
<?php foreach ($awards as $a): [$label, $cls] = $status[$a['nomination_status']]; ?>
  <article class="card accent">
    <div class="award-head"><span class="ic-circle"><?= icon('award', 26) ?></span><div><h2 style="margin:0"><?= e($a['name']) ?></h2>
    <span class="badge <?= $cls ?>"><?= e($label) ?></span></div></div>
    <p><?= nl2br(e($a['description'])) ?></p>
    <?php if ($a['eligibility']): ?><p><strong>Eligibility:</strong> <?= nl2br(e($a['eligibility'])) ?></p><?php endif; ?>
    <?php if ($a['nomination_status'] === 'open'): ?>
      <?php if ($a['deadline']): ?><p><strong>Deadline:</strong> <?= e(fmt_date($a['deadline'])) ?></p><?php endif; ?>
      <?php if ($a['required_documents']): ?><p><strong>Required documents:</strong> <?= nl2br(e($a['required_documents'])) ?></p><?php endif; ?>
    <?php endif; ?>
    <?php if ($a['selection_process']): ?><p><strong>Selection process:</strong> <?= nl2br(e($a['selection_process'])) ?></p><?php endif; ?>
    <?php if ($a['past_recipients']): ?><p><strong>Past recipients:</strong> <?= nl2br(e($a['past_recipients'])) ?></p><?php endif; ?>
    <?php if ($a['contact']): ?><p class="meta">Enquiries: <?= e($a['contact']) ?></p><?php endif; ?>
  </article>
<?php endforeach; ?>
</div>
<div class="notice"><?= cr('awards.notice') ?></div>

<?php
$fame = q('SELECT * FROM hall_of_fame WHERE is_public = 1 ORDER BY featured DESC, sort_order, inducted_year DESC, name')->fetchAll();
$fameCats = array_values(array_unique(array_column($fame, 'category')));
?>
<section class="section" id="hall">
  <div class="sec-head"><div><span class="eyebrow-s">Alumni Hall of Fame</span><h2>Honouring our outstanding alumni</h2></div></div>
  <?php if ($fame): ?>
    <?php if (count($fameCats) > 1): ?>
      <div class="fame-filter" role="group" aria-label="Filter by category">
        <button type="button" class="on" data-cat="">All</button>
        <?php foreach ($fameCats as $fc): ?><button type="button" data-cat="<?= e($fc) ?>"><?= e($fc) ?></button><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="fame-grid" id="fameGrid">
      <?php foreach ($fame as $h): $ph = $h['photo'] ? url('uploads/' . rawurlencode($h['photo'])) : ''; ?>
        <a class="fame-card" href="<?= url('hall-of-fame.php?id=' . (int) $h['id']) ?>" data-cat="<?= e($h['category']) ?>">
          <?php if ($ph): ?><img src="<?= e($ph) ?>" alt="" loading="lazy" width="110" height="110">
          <?php else: ?><span class="fame-init"><?= e(mb_strtoupper(mb_substr($h['name'], 0, 1))) ?></span><?php endif; ?>
          <strong><?= e($h['name']) ?></strong>
          <span><?= e($h['title'] ?: $h['category']) ?></span>
          <em><?= e($h['category']) ?></em>
        </a>
      <?php endforeach; ?>
    </div>
    <script>
    (function () {
      var bar = document.querySelector('.fame-filter'); if (!bar) return;
      var cards = [].slice.call(document.querySelectorAll('#fameGrid .fame-card'));
      bar.addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        [].forEach.call(bar.querySelectorAll('button'), function (x) { x.classList.toggle('on', x === b); });
        var c = b.getAttribute('data-cat');
        cards.forEach(function (k) { k.hidden = c !== '' && k.getAttribute('data-cat') !== c; });
      });
    })();
    </script>
  <?php else: ?>
    <div class="empty-state"><?= icon('award', 36) ?><strong>The Hall of Fame will honour its first inductees soon</strong><span>Know an outstanding alumnus? <a href="<?= url('contact.php') ?>">Contact the Secretariat</a> to suggest them.</span></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
