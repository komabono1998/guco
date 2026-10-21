<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Member Portal';
$me = require_login();
$p = q('SELECT p.*, c.name AS chapter_name FROM member_profiles p LEFT JOIN chapters c ON c.id = p.chapter_id WHERE p.user_id = ?', [$me['id']])->fetch() ?: [];
$regs = q('SELECT e.id, e.title, e.starts_at FROM event_registrations r JOIN events e ON e.id = r.event_id WHERE r.user_id = ? AND e.starts_at >= NOW() ORDER BY e.starts_at', [$me['id']])->fetchAll();
$approved = $me['status'] === 'approved';
$announcements = $approved ? q("SELECT title, slug, published_at FROM news WHERE status='published' AND category IN ('GUCO Announcements','Notices') AND published_at <= NOW() ORDER BY published_at DESC LIMIT 4")->fetchAll() : [];
$subLabel = ['unpaid' => ['Awaiting payment confirmation', 'warn'], 'active' => ['Active', ''], 'arrears' => ['In arrears', 'bad']];
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Member Portal</h1><p>Welcome, <?= e($me['full_name']) ?></p></div>

<?php if (!$approved): ?>
  <div class="notice"><strong>Application status: pending.</strong> The Secretariat is verifying your application. The Constitution requires registration and full payment of the applicable fees; the Secretariat will confirm the fees and approved payment procedure. Member-only features unlock once your membership is approved.</div>
<?php endif; ?>

<div class="grid">
  <div class="card">
    <h3>Membership</h3>
    <p><strong><?= e(CATEGORY_LABELS[$p['category'] ?? 'ordinary']) ?></strong><br>
      <span class="badge <?= $approved ? '' : 'warn' ?>"><?= $approved ? 'Approved' : 'Application pending' ?></span></p>
    <?php [$l, $cls] = $subLabel[$p['subscription_status'] ?? 'unpaid']; ?>
    <p>Subscription: <span class="badge <?= $cls ?>"><?= e($l) ?></span><?= !empty($p['paid_until']) ? '<br><span class="meta">Paid up to ' . e(fmt_date($p['paid_until'])) . '</span>' : '' ?></p>
    <?php if (setting('payment_instructions')): ?>
      <p><strong>Payment instructions</strong><br><?= nl2br(e(setting('payment_instructions'))) ?></p>
    <?php endif; ?>
  </div>
  <div class="card">
    <h3>My profile</h3>
    <p><?= e($p['programme'] ?? '') ?> <?= !empty($p['graduation_year']) ? '(' . (int) $p['graduation_year'] . ')' : '' ?><br><span class="meta"><?= e($p['college'] ?? '') ?></span></p>
    <p>Chapter: <?= e($p['chapter_name'] ?? 'Not set') ?></p>
    <p>Directory: <?= !empty($p['directory_consent']) ? 'visible' : 'hidden' ?></p>
    <a class="btn" href="<?= url('profile.php') ?>">Update my profile</a>
  </div>
  <?php if ($approved): ?>
  <div class="card">
    <h3>Member tools</h3>
    <ul class="plain">
      <li><a href="<?= url('directory.php') ?>">Members' directory</a></li>
      <li><a href="<?= url('resources.php') ?>">Documents &amp; newsletters</a></li>
      <li><a href="<?= url('events.php') ?>">Events &amp; registration</a></li>
      <li><a href="<?= url('chapters.php') ?>">Chapters</a></li>
    </ul>
  </div>
  <?php endif; ?>
</div>

<section class="section grid two">
  <div>
    <h2>My event registrations</h2>
    <?php foreach ($regs as $r): ?><p><a href="<?= url('event_view.php?id=' . (int) $r['id']) ?>"><?= e($r['title']) ?></a> <span class="meta">&middot; <?= e(fmt_date($r['starts_at'], 'j M Y, g:ia')) ?></span></p><?php endforeach; if (!$regs): ?><p>No upcoming registrations.</p><?php endif; ?>
  </div>
  <?php if ($approved): ?>
  <div>
    <h2>Member announcements</h2>
    <?php foreach ($announcements as $a): ?><p><a href="<?= url('news_view.php?slug=' . urlencode($a['slug'])) ?>"><?= e($a['title']) ?></a> <span class="meta">&middot; <?= e(fmt_date($a['published_at'])) ?></span></p><?php endforeach; if (!$announcements): ?><p>No announcements.</p><?php endif; ?>
  </div>
  <?php endif; ?>
</section>

<p><a href="<?= url('contact.php?category=Profile+correction') ?>">Need a correction or help? Send a support request &rarr;</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>
