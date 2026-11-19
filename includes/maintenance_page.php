<?php
/** The page visitors see while maintenance mode is on. Self-contained: no site header, footer or scripts from the site. */
$mTitle = setting('maintenance_title', 'We will be back soon');
$mText = setting('maintenance_message', 'Gulu University Convocation (GUCO) is briefly offline for scheduled maintenance. We are improving the website and will be back shortly. Thank you for your patience.');
$mUntil = maintenance_until_ts();
$mChannels = maintenance_channels();
$mName = setting('site_name', 'Gulu University Convocation');
$mTag = setting('site_tagline', 'Community Transformation Actualized');
$icons = ['Email' => 'M4 6h16v12H4zM4 7l8 6 8-6', 'Telephone' => 'M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z', 'WhatsApp' => 'M4 20l1.5-4.5A8 8 0 1 1 9 19z'];
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<meta http-equiv="refresh" content="300">
<title><?= e($mTitle) ?> | <?= e($mName) ?></title>
<link rel="icon" href="<?= e(logo_url()) ?>">
<style>
:root { --green: #14532d; --gold: #f2b807; --ink: #10231a; }
* { box-sizing: border-box; }
html, body { margin: 0; min-height: 100%; }
body { font-family: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif; color: #fff; background: radial-gradient(circle at 15% 10%, rgba(242, 184, 7, .22), transparent 40%), radial-gradient(circle at 90% 90%, rgba(15, 118, 110, .45), transparent 45%), linear-gradient(135deg, #07210f, #0f3d22 55%, #0b2f1b); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 28px 16px; overflow-x: hidden; }
.card { position: relative; z-index: 1; width: min(860px, 100%); text-align: center; padding: clamp(26px, 5vw, 52px); border-radius: 32px; background: rgba(255, 255, 255, .08); border: 1px solid rgba(255, 255, 255, .18); box-shadow: 0 30px 80px rgba(0, 0, 0, .4); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
.logo { width: clamp(110px, 22vw, 170px); height: auto; aspect-ratio: 1; border-radius: 50%; background: #fff; box-shadow: 0 0 0 7px rgba(242, 184, 7, .85), 0 20px 50px rgba(0, 0, 0, .45); animation: float 5s ease-in-out infinite; }
h1 { margin: 26px 0 10px; font-size: clamp(1.7rem, 5vw, 2.8rem); line-height: 1.15; }
.tag { color: var(--gold); font-weight: 700; letter-spacing: .14em; text-transform: uppercase; font-size: .78rem; }
.msg { margin: 14px auto 0; max-width: 40em; font-size: 1.08rem; line-height: 1.7; color: rgba(255, 255, 255, .92); white-space: pre-line; }
.gear { display: inline-flex; margin-top: 4px; color: var(--gold); animation: spin 8s linear infinite; }
.back { margin: 22px auto 0; display: inline-flex; flex-direction: column; gap: 4px; padding: 14px 26px; border-radius: 18px; background: rgba(0, 0, 0, .25); border: 1px solid rgba(255, 255, 255, .15); }
.back small { text-transform: uppercase; letter-spacing: .12em; font-weight: 700; font-size: .72rem; color: var(--gold); }
.back b { font-size: 1.15rem; }
.back span { font-size: .92rem; color: rgba(255, 255, 255, .85); font-variant-numeric: tabular-nums; }
h2 { margin: 34px 0 14px; font-size: 1.1rem; letter-spacing: .04em; }
.channels { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(210px, 100%), 1fr)); gap: 12px; text-align: left; }
.ch { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 18px; background: rgba(255, 255, 255, .1); border: 1px solid rgba(255, 255, 255, .16); color: #fff; text-decoration: none; transition: transform .2s, background .2s, border-color .2s; }
a.ch:hover { transform: translateY(-3px); background: rgba(255, 255, 255, .18); border-color: var(--gold); }
.ch i { flex: none; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: rgba(242, 184, 7, .18); color: var(--gold); font-style: normal; font-weight: 800; }
.ch svg { width: 22px; height: 22px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.ch span { display: flex; flex-direction: column; min-width: 0; }
.ch small { font-size: .7rem; letter-spacing: .1em; text-transform: uppercase; color: rgba(255, 255, 255, .7); font-weight: 700; }
.ch b { font-size: .95rem; overflow-wrap: anywhere; }
.foot { margin-top: 30px; font-size: .8rem; color: rgba(255, 255, 255, .6); }
.foot a { color: rgba(255, 255, 255, .85); }
@keyframes float { 50% { transform: translateY(-10px); } }
@keyframes spin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) { .logo, .gear { animation: none; } }
</style>
</head>
<body>
<main class="card" role="main">
  <img class="logo" src="<?= e(asset('assets/img/logo.webp')) ?>" alt="<?= e($mName) ?> logo" width="170" height="170">
  <h1><?= e($mTitle) ?></h1>
  <div class="tag"><?= e($mTag) ?></div>
  <span class="gear" aria-hidden="true"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg></span>
  <p class="msg"><?= e($mText) ?></p>

  <?php if ($mUntil): ?>
    <div class="back" id="back" data-until="<?= e(date('c', $mUntil)) ?>">
      <small>We expect to be back</small>
      <b><?= e(date('l, j F Y \a\t g:i A', $mUntil)) ?> (Uganda time)</b>
      <span id="backLeft"></span>
    </div>
  <?php endif; ?>

  <?php if ($mChannels): ?>
    <h2>Need us in the meantime? Reach us here</h2>
    <div class="channels">
      <?php foreach ($mChannels as [$label, $text, $link]): ?>
        <<?= $link ? 'a href="' . e($link) . '"' . (str_starts_with($link, 'http') ? ' target="_blank" rel="noopener"' : '') : 'div' ?> class="ch">
          <i><?php if (isset($icons[$label])): ?><svg viewBox="0 0 24 24" aria-hidden="true"><path d="<?= e($icons[$label]) ?>"/></svg><?php else: ?><?= e(mb_strtoupper(mb_substr($label, 0, 1))) ?><?php endif; ?></i>
          <span><small><?= e($label) ?></small><b><?= e($text) ?></b></span>
        </<?= $link ? 'a' : 'div' ?>>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <p class="foot">&copy; <?= date('Y') ?> <?= e($mName) ?> &middot; <a href="<?= e(url('admin/login.php')) ?>">Staff sign in</a></p>
</main>
<?php if ($mUntil): ?>
<script>
(function () {
  var box = document.getElementById('back'), out = document.getElementById('backLeft'); if (!box) return;
  var end = new Date(box.getAttribute('data-until')).getTime();
  function tick() {
    var s = Math.round((end - Date.now()) / 1000);
    if (s <= 0) { out.textContent = 'Any moment now. Please refresh this page.'; return; }
    var d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600), m = Math.floor(s % 3600 / 60), sec = s % 60;
    out.textContent = (d ? d + ' d ' : '') + (h || d ? h + ' h ' : '') + m + ' min ' + sec + ' s to go';
  }
  tick(); setInterval(tick, 1000);
})();
</script>
<?php endif; ?>
</body>
</html>
