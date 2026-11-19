<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/photos.php';
$siteName = setting('site_name', 'Gulu University Convocation');
$pageTitle = $pageTitle ?? $siteName;
$pageDesc = $pageDesc ?? ($siteName . ' (GUCO) - the alumni association of Gulu University. ' . setting('site_tagline', 'Community Transformation Actualized') . '.');
$me = current_user();
$flash = flash();
$here = basename($_SERVER['SCRIPT_NAME']);
$logo = logo_url();

// Navigation menu from Admin > Menu: top-level items with their visible children.
$nav = [];
foreach (q('SELECT * FROM menu_items WHERE is_visible = 1 ORDER BY sort_order, id')->fetchAll() as $mi) {
    if ($mi['parent_id']) {
        $nav[$mi['parent_id']]['children'][] = $mi;
    } else {
        $nav[$mi['id']]['item'] = $mi;
    }
}
$nav = array_filter($nav, fn($entry) => isset($entry['item']));
$menuUrl = fn(array $i) => preg_match('#^(https?:)?//#i', $i['url']) ? $i['url'] : url($i['url']);

$brandCss = '';
foreach (['color_primary' => ['--green', '--green-2'], 'color_accent' => ['--gold']] as $key => $vars) {
    $hex = setting($key);
    if (preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
        foreach ($vars as $v) {
            $brandCss .= "$v:$hex;";
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | <?= e(config('short_name')) ?></title>
<meta name="description" content="<?= e($pageDesc) ?>">
<link rel="icon" href="<?= e($logo) ?>">
<link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/theme.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/ui.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/corporate.css') ?>">
<?php if ($brandCss): ?><style>:root{<?= $brandCss ?>}</style><?php endif; ?>
<?php $welcomeOn = setting('show_welcome_logo', '1') === '1'; if ($welcomeOn): ?>
<script>try { if (localStorage.getItem('guco_welcome') !== '1') document.documentElement.classList.add('welcome-show'); } catch (e) {}</script>
<?php endif; ?>
</head>
<body class="site">
<?php if ($welcomeOn): ?>
<div class="welcome" id="welcome" role="dialog" aria-modal="true" aria-label="Welcome to Gulu University Convocation">
  <div class="welcome-card">
    <div class="welcome-logo"><span class="w-ring" aria-hidden="true"></span><span class="w-ring r2" aria-hidden="true"></span><img src="<?= e(asset('assets/img/logo.webp')) ?>" alt="Gulu University Convocation official logo" width="1000" height="1000"></div>
    <p class="welcome-name">Welcome to <?= e($siteName) ?></p>
    <p class="welcome-tag"><?= e(setting('site_tagline', 'Community Transformation Actualized')) ?></p>
    <button type="button" class="welcome-skip" id="welcomeSkip">Enter the website</button>
    <i class="welcome-bar" aria-hidden="true"></i>
  </div>
</div>
<script>
(function () {
  var w = document.getElementById('welcome'); if (!w) return;
  if (!document.documentElement.classList.contains('welcome-show')) { w.remove(); return; }
  try { localStorage.setItem('guco_welcome', '1'); } catch (e) {}
  var closed = false, timer;
  function close() {
    if (closed) return; closed = true; clearTimeout(timer);
    w.classList.add('out'); document.documentElement.classList.remove('welcome-open');
    setTimeout(function () { document.documentElement.classList.remove('welcome-show'); w.remove(); }, 800);
  }
  document.documentElement.classList.add('welcome-open');
  timer = setTimeout(close, 10000);
  w.addEventListener('click', close);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
  var b = document.getElementById('welcomeSkip'); if (b) b.focus({ preventScroll: true });
})();
</script>
<?php endif; ?>
<a class="skip" href="#main">Skip to main content</a>
<?php if (maintenance_on() && is_staff()): ?><div class="mt-staff-bar" role="status"><strong>Maintenance mode is ON.</strong> Visitors see the maintenance page; you can see the site because you are signed in as staff. <?php if (can('settings')): ?><a href="<?= url('admin/maintenance.php') ?>">Turn it off</a><?php endif; ?></div><?php endif; ?>
<div class="topbar">
  <div class="container tb">
    <div class="tb-l">
      <span class="tb-clock" aria-label="Current date and time in Uganda"><?= icon('calendar', 14) ?><span id="tb-date"><?= e(date('l, j F Y')) ?></span><i class="sep"></i><?= icon('clock', 14) ?><span id="tb-time"><?= e(date('H:i:s')) ?></span> <small>EAT</small></span>
      <?php if (setting('contact_email')): ?><a href="mailto:<?= e(setting('contact_email')) ?>"><?= icon('mail', 14) ?><?= e(setting('contact_email')) ?></a><?php endif; ?>
      <?php if (setting('contact_phone')): ?><span><?= e(setting('contact_phone')) ?></span><?php endif; ?>
    </div>
    <div class="tb-r">
      <a href="<?= url('verify.php') ?>"><?= icon('shield', 14) ?>Verify a card</a>
      <a href="<?= url('resources.php') ?>">Reports &amp; documents</a>
      <a href="<?= url('contact.php') ?>">Contact</a>
      <?php foreach (['social_facebook' => 'Facebook', 'social_x' => 'X', 'social_linkedin' => 'LinkedIn', 'social_youtube' => 'YouTube'] as $sk => $sl): if (setting($sk)): ?><a href="<?= e(setting($sk)) ?>" target="_blank" rel="noopener"><?= $sl ?></a><?php endif; endforeach; ?>
    </div>
  </div>
</div>
<?php $slogan = setting('site_tagline', 'Community Transformation Actualized'); ?>
<?php
// One slow, readable loop: the slogan, the association's name and its programme areas (all editable in the admin).
$sgNames = array_map(fn($p) => $p[0], array_slice(c_pairs('programmes.areas'), 0, 4));
$sgLoop = [['main', $slogan], ['name', setting('site_name', 'Gulu University Convocation')]];
foreach ($sgNames as $sgN) {
    $sgLoop[] = ['sub', $sgN];
}
?>
<div class="slogan-strip sg" aria-label="<?= e($slogan) ?>">
  <div class="sg-view" aria-hidden="true">
    <div class="sg-track">
      <?php for ($sgK = 0; $sgK < 4; $sgK++): foreach ($sgLoop as [$sgType, $sgText]): ?>
        <span class="sg-item sg-<?= $sgType ?>"><?= $sgType === 'main' ? icon('star', 16) : '' ?><?= e($sgText) ?></span><i class="sg-dot"></i>
      <?php endforeach; endfor; ?>
    </div>
  </div>
  <button type="button" class="sg-pause" aria-pressed="false" aria-label="Pause the scrolling slogan" title="Pause or play"><span></span><span></span></button>
</div>
<script>
(function () {
  var d = document.getElementById('tb-date'), t = document.getElementById('tb-time');
  if (!d || !t || !window.Intl) return;
  var df = new Intl.DateTimeFormat('en-GB', { timeZone: 'Africa/Kampala', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  var tf = new Intl.DateTimeFormat('en-GB', { timeZone: 'Africa/Kampala', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
  function tick() { var n = new Date(); d.textContent = df.format(n); t.textContent = tf.format(n); }
  tick(); setInterval(tick, 1000);
})();
</script>
<header class="site-header">
  <div class="container bar">
    <a class="brand" href="<?= url() ?>"><img src="<?= e($logo) ?>" alt="<?= e(config('short_name')) ?> logo" width="84" height="84"><span><?= e($siteName) ?></span></a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mainnav" onclick="var n=document.getElementById('mainnav');var o=n.classList.toggle('open');this.setAttribute('aria-expanded',o)">Menu</button>
    <nav id="mainnav" class="mainnav" aria-label="Main">
      <?php foreach ($nav as $navEntry): $item = $navEntry['item']; $kids = $navEntry['children'] ?? []; ?>
        <div class="navitem<?= $kids ? ' has-sub' : '' ?>">
          <a href="<?= e($menuUrl($item)) ?>"<?= $item['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?><?= $here === strtok($item['url'], '#?') ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
          <?php if ($kids): ?>
            <div class="sub">
              <?php foreach ($kids as $k): ?><a href="<?= e($menuUrl($k)) ?>"<?= $k['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($k['label']) ?></a><?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if ($me): ?><div class="account account-mobile">
        <?php if ($me): ?><a href="<?= url('member/') ?>">Member Portal</a><a href="<?= url('logout.php') ?>">Log out</a>
        <?php endif; ?>
      </div><?php endif; ?>
    </nav>
    <?php if ($me): ?><div class="account">
      <?php if ($me): ?>
        <a href="<?= url('member/') ?>" class="btn">Member Portal</a>
        <a href="<?= url('logout.php') ?>">Log out</a>
      <?php endif; ?>
    </div><?php endif; ?>
  </div>
</header>
<main id="main" class="container">
<?php if ($flash): ?><div class="flash <?= e($flash[1]) ?>" role="status"><?= e($flash[0]) ?></div><?php endif; ?>
