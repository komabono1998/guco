<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Our Anthems';
$pageDesc = 'The Gulu University anthem, the Uganda national anthem and the East African Community anthem: listen and sing along.';
$anthems = q('SELECT * FROM anthems WHERE is_public = 1 ORDER BY sort_order, id')->fetchAll();
$want = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_GET['a'] ?? '')));
$active = 0;
foreach ($anthems as $i => $a) {
    if ($a['slug'] === $want) {
        $active = $i;
    }
}
$tones = ['#14532d', '#b45309', '#1d4ed8', '#7e22ce', '#0f766e'];
$verses = fn(string $t) => array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', trim($t))), fn($v) => $v !== ''));
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Our Anthems</h1><p>Listen, read the words and sing along with the anthems we share</p></div>
<?php if (!$anthems): ?>
  <div class="empty-state"><?= icon('music', 36) ?><strong>No anthems published yet</strong><span>Anthems will appear here once they are added.</span></div>
<?php else: ?>
<section class="an" id="an">
  <div class="an-tabs" role="tablist" aria-label="Choose an anthem">
    <?php foreach ($anthems as $i => $a): ?>
      <button type="button" role="tab" class="an-tab<?= $i === $active ? ' on' : '' ?>" id="an-t-<?= e($a['slug']) ?>" aria-controls="an-p-<?= e($a['slug']) ?>" aria-selected="<?= $i === $active ? 'true' : 'false' ?>" data-i="<?= $i ?>" style="--c:<?= $tones[$i % 5] ?>">
        <span class="an-tab-ic"><?= icon('music', 24) ?></span>
        <span class="an-tab-t"><small><?= e($a['kicker']) ?></small><b><?= e($a['title']) ?></b></span>
      </button>
    <?php endforeach; ?>
  </div>

  <?php foreach ($anthems as $i => $a): $vs = $verses((string) $a['lyrics']); $src = $a['audio'] ? url('uploads/' . rawurlencode($a['audio'])) : ''; ?>
    <article class="an-panel<?= $i === $active ? ' on' : '' ?>" id="an-p-<?= e($a['slug']) ?>" role="tabpanel" aria-labelledby="an-t-<?= e($a['slug']) ?>" style="--c:<?= $tones[$i % 5] ?>">
      <div class="an-side">
        <span class="an-kicker"><?= e($a['kicker']) ?><?= $a['body'] ? ' &middot; ' . e($a['body']) : '' ?></span>
        <h2><?= e($a['title']) ?></h2>
        <?php if ($a['description']): ?><p class="an-desc"><?= e($a['description']) ?></p><?php endif; ?>
        <ul class="an-meta">
          <?php if ($a['language']): ?><li><?= icon('globe', 16) ?> <?= e($a['language']) ?></li><?php endif; ?>
          <?php if ($vs): ?><li><?= icon('book', 16) ?> <?= count($vs) ?> verse<?= count($vs) === 1 ? '' : 's' ?></li><?php endif; ?>
        </ul>

        <?php if ($src): ?>
          <div class="an-player" data-src="<?= e($src) ?>">
            <button type="button" class="an-play" aria-label="Play <?= e($a['title']) ?>"><?= icon('play', 28) ?></button>
            <div class="an-track">
              <div class="an-bar"><input type="range" min="0" max="1000" value="0" aria-label="Seek" class="an-seek"></div>
              <div class="an-time"><span class="an-cur">0:00</span><span class="an-dur">--:--</span></div>
            </div>
            <audio preload="none" src="<?= e($src) ?>"></audio>
          </div>
        <?php else: ?>
          <div class="an-soon"><?= icon('play', 22) ?><span>The recording will be added soon.</span></div>
        <?php endif; ?>

        <?php if ($a['video_url']): ?><div class="an-video"><?= video_embed($a['video_url'], 'Watch: ' . $a['title']) ?></div><?php endif; ?>
        <?php if ($a['credit']): ?><p class="an-credit"><?= e($a['credit']) ?></p><?php endif; ?>
        <?php if ($vs): ?><div class="an-actions"><button type="button" class="btn secondary an-full-open"><?= icon('search', 16) ?> Full-screen lyrics</button><button type="button" class="btn secondary" onclick="window.print()">Print</button></div><?php endif; ?>
      </div>

      <div class="an-lyrics">
        <?php if ($vs): ?>
          <?php foreach ($vs as $vi => $v): ?>
            <div class="an-verse" style="--d:<?= $vi * 90 ?>ms"><span class="an-vn"><?= $vi + 1 ?></span><p><?= nl2br(e($v)) ?></p></div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state"><?= icon('book', 34) ?><strong>The lyrics will be added soon</strong><span>The Secretariat will publish the official words here.</span></div>
        <?php endif; ?>
      </div>
      <?php if ($vs): ?>
        <div class="an-full" hidden role="dialog" aria-modal="true" aria-label="Lyrics of <?= e($a['title']) ?>">
          <button type="button" class="an-full-close" aria-label="Close">&times;</button>
          <div class="an-full-in"><h3><?= e($a['title']) ?></h3><?php foreach ($vs as $v): ?><p><?= nl2br(e($v)) ?></p><?php endforeach; ?></div>
        </div>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</section>
<script>
(function () {
  var box = document.getElementById('an'); if (!box) return;
  var tabs = [].slice.call(box.querySelectorAll('.an-tab')), panels = [].slice.call(box.querySelectorAll('.an-panel'));
  function fmt(s) { s = Math.max(0, Math.floor(s || 0)); return Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2); }
  function stopAll(except) { box.querySelectorAll('audio').forEach(function (a) { if (a !== except) a.pause(); }); }
  function show(i) {
    tabs.forEach(function (t, k) { t.classList.toggle('on', k === i); t.setAttribute('aria-selected', k === i ? 'true' : 'false'); });
    panels.forEach(function (p, k) { p.classList.toggle('on', k === i); });
    stopAll(null);
    history.replaceState(null, '', '?a=' + panels[i].id.replace('an-p-', ''));
  }
  tabs.forEach(function (t, k) {
    t.addEventListener('click', function () { show(k); });
    t.addEventListener('keydown', function (e) { var d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0; if (d) { e.preventDefault(); var n = (k + d + tabs.length) % tabs.length; show(n); tabs[n].focus(); } });
  });
  [].forEach.call(box.querySelectorAll('.an-player'), function (pl) {
    var a = pl.querySelector('audio'), b = pl.querySelector('.an-play'), seek = pl.querySelector('.an-seek'), cur = pl.querySelector('.an-cur'), dur = pl.querySelector('.an-dur');
    var playIcon = b.innerHTML, pauseIcon = '<svg class="icon" width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>';
    b.addEventListener('click', function () { if (a.paused) { stopAll(a); a.play(); } else { a.pause(); } });
    a.addEventListener('play', function () { b.innerHTML = pauseIcon; pl.classList.add('playing'); });
    a.addEventListener('pause', function () { b.innerHTML = playIcon; pl.classList.remove('playing'); });
    a.addEventListener('ended', function () { b.innerHTML = playIcon; pl.classList.remove('playing'); seek.value = 0; });
    a.addEventListener('loadedmetadata', function () { dur.textContent = fmt(a.duration); });
    a.addEventListener('timeupdate', function () { cur.textContent = fmt(a.currentTime); if (a.duration) { seek.value = Math.round(a.currentTime / a.duration * 1000); seek.style.setProperty('--p', (a.currentTime / a.duration * 100) + '%'); } });
    seek.addEventListener('input', function () { if (a.duration) a.currentTime = a.duration * seek.value / 1000; });
  });
  [].forEach.call(box.querySelectorAll('.an-panel'), function (p) {
    var open = p.querySelector('.an-full-open'), full = p.querySelector('.an-full'); if (!open || !full) return;
    function close() { full.hidden = true; document.body.style.overflow = ''; open.focus(); }
    open.addEventListener('click', function () { full.hidden = false; document.body.style.overflow = 'hidden'; full.querySelector('.an-full-close').focus(); });
    full.querySelector('.an-full-close').addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !full.hidden) close(); });
  });
})();
</script>
<?php endif; ?>
<?php related_links('Keep exploring', 'More about GUCO', [
    ['about.php', 'users', 'About GUCO', 'Who we are, our vision and values'],
    ['events.php', 'calendar', 'Events Calendar', 'Join us at the next GUCO event'],
    ['gallery.php', 'image', 'Photo Gallery', 'Albums from GUCO events'],
    ['programmes.php', 'target', 'Programmes', 'What GUCO does for alumni and the University'],
]); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
