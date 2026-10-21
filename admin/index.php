<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pageTitle = 'Admin';
$count = fn($sql) => (int) db()->query($sql)->fetchColumn();
require __DIR__ . '/../includes/header.php';
?>
<h1>Admin dashboard</h1>
<div class="grid">
  <a class="card" href="members.php"><h3>Members</h3><p><?= $count("SELECT COUNT(*) FROM users WHERE status='pending'") ?> pending approval</p></a>
  <a class="card" href="news.php"><h3>News</h3><p><?= $count('SELECT COUNT(*) FROM news') ?> posts</p></a>
  <a class="card" href="events.php"><h3>Events</h3><p><?= $count('SELECT COUNT(*) FROM events') ?> events</p></a>
  <a class="card" href="gallery.php"><h3>Gallery</h3><p><?= $count('SELECT COUNT(*) FROM gallery') ?> photos</p></a>
  <a class="card" href="inbox.php"><h3>Inbox &amp; pledges</h3><p><?= $count('SELECT COUNT(*) FROM messages') ?> messages, <?= $count('SELECT COUNT(*) FROM donation_pledges') ?> pledges</p></a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
