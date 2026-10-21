<?php
require_once __DIR__ . '/../includes/portal.php';
$me = require_login();
$mine = q('SELECT event_id FROM event_registrations WHERE user_id = ? OR email = ?', [$me['id'], $me['email']])->fetchAll(PDO::FETCH_COLUMN);
$upcoming = q("SELECT * FROM events WHERE is_public = 1 AND (starts_at >= NOW() OR starts_at IS NULL) ORDER BY starts_at IS NULL, starts_at")->fetchAll();
$past = q("SELECT e.* FROM events e JOIN event_registrations r ON r.event_id = e.id WHERE (r.user_id = ? OR r.email = ?) AND e.starts_at < NOW() ORDER BY e.starts_at DESC LIMIT 10", [$me['id'], $me['email']])->fetchAll();
portal_header('Events');
?>
<section class="panel">
  <h2>Upcoming events</h2>
  <?php foreach ($upcoming as $ev): ?>
    <div class="ev-mini">
      <div class="d"><b><?= $ev['starts_at'] ? e(fmt_date($ev['starts_at'], 'j')) : 'TBA' ?></b><span><?= $ev['starts_at'] ? e(fmt_date($ev['starts_at'], 'M')) : '' ?></span></div>
      <div><a href="<?= e(url('event_view.php?id=' . (int) $ev['id'])) ?>"><?= e($ev['title']) ?></a> <span class="badge"><?= e($ev['category']) ?></span> <?= event_status_badge($ev) ?><br>
        <span class="meta"><?= e(event_when($ev, 'l j M Y, g:ia')) ?><?= $ev['location'] ? ' &middot; ' . e($ev['location']) : '' ?></span><?= event_countdown($ev, 'small') ?></div>
      <?php if (in_array($ev['id'], $mine)): ?><span class="badge" style="margin-left:auto">You are registered</span>
      <?php elseif ($ev['registration_open'] && $ev['status'] === 'scheduled' && $ev['starts_at']): ?><a class="btn" href="<?= e(url('event_view.php?id=' . (int) $ev['id'] . '#register')) ?>">Register</a>
      <?php else: ?><a class="btn secondary" href="<?= e(url('event_view.php?id=' . (int) $ev['id'])) ?>">Details</a><?php endif; ?>
    </div>
  <?php endforeach; if (!$upcoming): ?><p class="meta">No upcoming events yet.</p><?php endif; ?>
</section>
<section class="panel">
  <h2>Events I attended or registered for</h2>
  <?php foreach ($past as $ev): ?><p style="margin:.4em 0"><a href="<?= e(url('event_view.php?id=' . (int) $ev['id'])) ?>"><?= e($ev['title']) ?></a> <span class="meta">&middot; <?= e(fmt_date($ev['starts_at'])) ?></span></p><?php endforeach; if (!$past): ?><p class="meta">None yet.</p><?php endif; ?>
</section>
<?php portal_footer(); ?>
