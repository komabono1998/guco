<?php
require_once __DIR__ . '/includes/bootstrap.php';
$ev = q('SELECT * FROM events WHERE id = ? AND is_public = 1', [(int) ($_GET['id'] ?? 0)])->fetch();
if (!$ev) {
    http_response_code(404);
}
$canRegister = $ev && $ev['registration_open'] && $ev['status'] === 'scheduled' && $ev['starts_at'] && strtotime($ev['starts_at']) > time();
if ($ev && $canRegister && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (is_spam() || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('Please enter your name and a valid email.', 'error');
    } else {
        $me = current_user();
        $n = q('INSERT IGNORE INTO event_registrations (event_id, user_id, name, email, phone) VALUES (?,?,?,?,?)',
            [$ev['id'], $me['id'] ?? null, $name, $email, trim($_POST['phone'] ?? '')])->rowCount();
        flash($n ? 'You are registered for this event.' : 'This email is already registered for the event.', $n ? 'success' : 'error');
        redirect('event_view.php?id=' . $ev['id']);
    }
}
$pageTitle = $ev['title'] ?? 'Event not found';
$pageDesc = $ev ? excerpt((string) $ev['description'], 150) : null;
require __DIR__ . '/includes/header.php';
if (!$ev): ?>
  <h1>Event not found</h1>
<?php else: $me = current_user(); $fl = event_flyer_url($ev); ?>
  <div class="page-head"><span class="badge"><?= e($ev['category']) ?></span> <?= event_status_badge($ev) ?><h1><?= e($ev['title']) ?></h1><p><?= e(event_when($ev, 'l j F Y, g:ia')) ?><?= $ev['location'] ? ' &middot; ' . e($ev['location']) : '' ?></p></div>
  <p><a href="<?= url('events.php') ?>">&larr; Events calendar</a></p>
  <div class="event-page" style="<?= $fl ? '' : 'grid-template-columns:1fr' ?>">
    <?php if ($fl): ?><div><a href="<?= e($fl) ?>" target="_blank" rel="noopener"><img class="flyer" src="<?= e($fl) ?>" alt="Flyer for <?= e($ev['title']) ?>"></a></div><?php endif; ?>
    <div>
      <?php if ($ev['status'] !== 'scheduled'): ?><p class="notice"><strong>This event has been <?= e($ev['status']) ?>.</strong> Please check <a href="<?= url('news.php?category=Notices') ?>">Notices</a> for updates.</p><?php endif; ?>
      <?= event_countdown($ev) ?>
      <div class="event-facts">
        <p><strong>When:</strong> <?= e(event_when($ev, 'l j M Y, g:ia')) ?><?php
            if ($ev['starts_at'] && $ev['ends_at']) {
                $sameDay = fmt_date($ev['ends_at'], 'Ymd') === fmt_date($ev['starts_at'], 'Ymd');
                echo ' to ' . e(fmt_date($ev['ends_at'], $sameDay ? 'g:ia' : 'D j M Y, g:ia'));
            } ?></p>
        <?php if ($ev['location']): ?><p><strong>Venue:</strong> <?= e($ev['location']) ?></p><?php endif; ?>
        <?php if ($ev['online_link']): ?><p><strong>Online:</strong> <a href="<?= e($ev['online_link']) ?>" rel="noopener"><?= e($ev['online_link']) ?></a></p><?php endif; ?>
        <?php if ($ev['audience']): ?><p><strong>Audience:</strong> <?= e($ev['audience']) ?></p><?php endif; ?>
        <?php if ($ev['accessibility_info']): ?><p><strong>Accessibility:</strong> <?= e($ev['accessibility_info']) ?></p><?php endif; ?>
        <?php if ($ev['contact']): ?><p><strong>Contact:</strong> <?= e($ev['contact']) ?></p><?php endif; ?>
      </div>
      <?php if ($ev['description']): ?><p><?= nl2br(e($ev['description'])) ?></p><?php endif; ?>
      <?php if ($ev['extra_details']): ?><h2>More details</h2><p><?= nl2br(e($ev['extra_details'])) ?></p><?php endif; ?>
      <?php if ($ev['starts_at']): ?><p><a class="btn secondary" href="<?= url('event_ics.php?id=' . (int) $ev['id']) ?>">Add to calendar</a></p><?php endif; ?>
    </div>
  </div>
  <?php if ($canRegister): ?>
    <section id="register" class="section">
      <h2>Register</h2>
      <form method="post" class="stack">
        <?= csrf_field() ?><?= spam_fields() ?>
        <label>Full name <input name="name" required value="<?= e($me['full_name'] ?? '') ?>"></label>
        <label>Email <input type="email" name="email" required value="<?= e($me['email'] ?? '') ?>"></label>
        <label>Telephone <span class="hint">(optional)</span> <input name="phone"></label>
        <button>Register</button>
      </form>
    </section>
  <?php endif; ?>
<?php endif;
require __DIR__ . '/includes/footer.php'; ?>
