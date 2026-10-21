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
$nav = array_filter($nav, fn($n) => isset($n['item']));
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
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<?php if ($brandCss): ?><style>:root{<?= $brandCss ?>}</style><?php endif; ?>
</head>
<body>
<a class="skip" href="#main">Skip to main content</a>
<header class="site-header">
  <div class="container bar">
    <a class="brand" href="<?= url() ?>"><img src="<?= e($logo) ?>" alt="<?= e(config('short_name')) ?> logo" width="56" height="56"><span><?= e($siteName) ?></span></a>
    <div class="account">
      <?php if ($me): ?>
        <a href="<?= url('portal.php') ?>">Member Portal</a>
        <a href="<?= url('logout.php') ?>">Log out</a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>">Log in</a>
        <a class="btn" href="<?= url('register.php') ?>">Join GUCO</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="container">
    <button class="menu-toggle secondary" aria-expanded="false" aria-controls="mainnav" onclick="var n=document.getElementById('mainnav');var o=n.classList.toggle('open');this.setAttribute('aria-expanded',o)">Menu</button>
    <nav id="mainnav" class="mainnav" aria-label="Main">
      <?php foreach ($nav as $n): $item = $n['item']; $kids = $n['children'] ?? []; ?>
        <div class="navitem<?= $kids ? ' has-sub' : '' ?>">
          <a href="<?= e($menuUrl($item)) ?>"<?= $item['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?><?= !$kids && $here === strtok($item['url'], '#?') ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
          <?php if ($kids): ?>
            <div class="sub">
              <?php foreach ($kids as $k): ?><a href="<?= e($menuUrl($k)) ?>"<?= $k['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($k['label']) ?></a><?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </nav>
  </div>
</header>
<main id="main" class="container">
<?php if ($flash): ?><div class="flash <?= e($flash[1]) ?>" role="status"><?= e($flash[0]) ?></div><?php endif; ?>
