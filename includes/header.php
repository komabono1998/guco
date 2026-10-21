<?php
require_once __DIR__ . '/bootstrap.php';
$pageTitle = $pageTitle ?? config('site_name');
$pageDesc = $pageDesc ?? 'Gulu University Convocation (GUCO) - the alumni association of Gulu University. Community Transformation Actualized.';
$me = current_user();
$flash = flash();
$here = basename($_SERVER['SCRIPT_NAME']);

$nav = [
    ['Home', 'index.php'],
    ['About GUCO', 'about.php', [
        ['Overview', 'about.php#overview'], ['Vision and Mission', 'about.php#vision'],
        ['Objectives', 'about.php#objectives'], ['Constitution', 'resources.php?q=constitution'],
    ]],
    ['Membership', 'membership.php', [
        ['Membership Categories', 'membership.php#categories'], ['Benefits', 'membership.php#benefits'],
        ['Join GUCO', 'register.php'], ['Member Portal', 'portal.php'], ['FAQs', 'membership.php#faq'],
    ]],
    ['Leadership', 'leadership.php', [
        ['General Assembly', 'leadership.php#assembly'], ['Board', 'leadership.php#board'],
        ['Executive Committee', 'leadership.php#executive'], ['Elections', 'leadership.php#elections'],
    ]],
    ['Programmes', 'programmes.php'],
    ['Chapters', 'chapters.php'],
    ['Events &amp; News', 'news.php', [
        ['News', 'news.php'], ['Events Calendar', 'events.php'], ['Notices', 'news.php?category=Notices'],
        ['Newsletter', 'news.php?category=Newsletter'], ['Gallery', 'gallery.php'],
    ]],
    ['Awards', 'awards.php'],
    ['Partnerships', 'partnerships.php'],
    ['Resources', 'resources.php'],
    ['Contact', 'contact.php'],
];
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | <?= e(config('short_name')) ?></title>
<meta name="description" content="<?= e($pageDesc) ?>">
<link rel="icon" type="image/webp" href="<?= url('assets/img/logo.webp') ?>">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<a class="skip" href="#main">Skip to main content</a>
<header class="site-header">
  <div class="container bar">
    <a class="brand" href="<?= url() ?>"><img src="<?= url('assets/img/logo.webp') ?>" alt="GUCO logo" width="56" height="56"><span>Gulu University Convocation</span></a>
    <div class="account">
      <?php if ($me): ?>
        <?php if (is_staff()): ?><a href="<?= url('admin/') ?>">Admin</a><?php endif; ?>
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
      <?php foreach ($nav as $item): ?>
        <div class="navitem<?= !empty($item[2]) ? ' has-sub' : '' ?>">
          <a href="<?= url($item[1]) ?>"<?= $here === strtok($item[1], '#?') && empty($item[2]) ? ' aria-current="page"' : '' ?>><?= $item[0] ?></a>
          <?php if (!empty($item[2])): ?>
            <div class="sub">
              <?php foreach ($item[2] as $s): ?><a href="<?= url($s[1]) ?>"><?= $s[0] ?></a><?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </nav>
  </div>
</header>
<main id="main" class="container">
<?php if ($flash): ?><div class="flash <?= e($flash[1]) ?>" role="status"><?= e($flash[0]) ?></div><?php endif; ?>
