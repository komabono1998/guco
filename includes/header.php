<?php
require_once __DIR__ . '/bootstrap.php';
$pageTitle = $pageTitle ?? config('site_name');
$me = current_user();
$flash = flash();
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | <?= e(config('short_name')) ?></title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
  <div class="container bar">
    <a class="brand" href="<?= url() ?>"><strong><?= e(config('short_name')) ?></strong> <span>Gulu University Convocation</span></a>
    <nav>
      <a href="<?= url('about.php') ?>">About</a>
      <a href="<?= url('news.php') ?>">News</a>
      <a href="<?= url('events.php') ?>">Events</a>
      <a href="<?= url('gallery.php') ?>">Gallery</a>
      <a href="<?= url('directory.php') ?>">Directory</a>
      <a href="<?= url('donate.php') ?>">Give</a>
      <a href="<?= url('contact.php') ?>">Contact</a>
      <?php if ($me): ?>
        <?php if ($me['role'] === 'admin'): ?><a href="<?= url('admin/') ?>">Admin</a><?php endif; ?>
        <a href="<?= url('profile.php') ?>">My profile</a>
        <a href="<?= url('logout.php') ?>">Log out</a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>">Log in</a>
        <a class="btn" href="<?= url('register.php') ?>">Join</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
<?php if ($flash): ?><div class="flash <?= e($flash[1]) ?>"><?= e($flash[0]) ?></div><?php endif; ?>
