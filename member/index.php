<?php
require_once __DIR__ . '/../includes/portal.php';
$me = require_login();
$p = q('SELECT p.*, c.name AS chapter_name FROM member_profiles p LEFT JOIN chapters c ON c.id = p.chapter_id WHERE p.user_id = ?', [$me['id']])->fetch() ?: [];
$card = card_state($p + ['status' => $me['status']]);
$member = is_member();
$approved = $me['status'] === 'approved';
$payments = q('SELECT * FROM member_payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 4', [$me['id']])->fetchAll();
$hasVerified = (int) q("SELECT COUNT(*) FROM member_payments WHERE user_id = ? AND status = 'verified'", [$me['id']])->fetchColumn() > 0;
$myEvents = q('SELECT e.id FROM event_registrations r JOIN events e ON e.id = r.event_id WHERE r.user_id = ? AND e.starts_at >= NOW()', [$me['id']])->fetchAll(PDO::FETCH_COLUMN);
$events = q("SELECT * FROM events WHERE is_public = 1 AND status = 'scheduled' AND starts_at >= NOW() ORDER BY starts_at LIMIT 4")->fetchAll();
$news = $member ? q("SELECT title, slug, category, published_at FROM news WHERE status='published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 4")->fetchAll() : [];

// Profile completeness
$fields = ['phone', 'address', 'location', 'nationality', 'college', 'programme', 'graduation_year', 'employment_status', 'profession', 'bio', 'photo', 'chapter_id'];
$filled = count(array_filter($fields, fn($f) => !empty($p[$f])));
$pct = (int) round($filled / count($fields) * 100);
$renewDue = $card['state'] === 'expired' || ($card['until'] && $card['until'] <= date('Y-m-d', strtotime('+30 days')));
$subLabel = ['unpaid' => 'Awaiting confirmation', 'active' => 'Active', 'arrears' => 'In arrears'][$p['subscription_status'] ?? 'unpaid'];
$payBadge = ['pending' => ['Pending', 'warn'], 'verified' => ['Verified', ''], 'rejected' => ['Rejected', 'bad']];

$next = [
    ['Application submitted', true, ''],
    ['Proof of payment verified', $hasVerified, 'payments.php'],
    ['Membership approved and ID issued', $approved && !empty($p['membership_id']), ''],
    ['Add a card photo', !empty($p['photo']), 'profile.php'],
    ['Complete your profile (' . $pct . '%)', $pct >= 80, 'profile.php'],
    ['Join a chapter', !empty($p['chapter_id']), 'profile.php'],
];
portal_header('Dashboard');
?>
<?php if ($card['state'] === 'revoked'): ?>
  <div class="notice bad"><strong>Your membership card has been revoked.</strong> <?= e($p['card_revoke_reason'] ?? '') ?> Member-only features are unavailable. <a href="support.php">Contact the Secretariat</a>.</div>
<?php elseif (!$approved): ?>
  <div class="notice"><strong>Your application is <?= e($me['status']) ?>.</strong> The Secretariat is verifying your details and proof of payment. Your membership ID and card are issued on approval.</div>
<?php elseif ($renewDue): ?>
  <div class="notice"><strong>Subscription renewal <?= $card['state'] === 'expired' ? 'overdue' : 'due soon' ?>.</strong> <a href="payments.php#renew">Submit your renewal payment</a> to keep your card valid.</div>
<?php endif; ?>

<section class="welcome">
  <div>
    <h2>Welcome, <?= e(strtok($me['full_name'], ' ')) ?></h2>
    <p><?= e(CATEGORY_LABELS[$p['category'] ?? 'ordinary']) ?><?= !empty($p['membership_id']) ? ' &middot; ' . e($p['membership_id']) : '' ?></p>
  </div>
  <div class="actions">
    <?php if ($member && $card['state'] !== 'revoked'): ?><a class="btn gold" href="card.php">View my card</a><?php endif; ?>
    <a class="btn outline" href="payments.php#renew">Renew / pay</a>
    <a class="btn outline" href="profile.php">Update profile</a>
  </div>
</section>

<div class="tiles">
  <div class="tile"><span class="ic"><?= icon('shield', 24) ?></span><div><small>Membership</small><b><?= $approved ? 'Approved' : ucfirst($me['status']) ?></b></div></div>
  <div class="tile"><span class="ic"><?= icon('wallet', 24) ?></span><div><small>Subscription</small><b><?= e($subLabel) ?></b><?= !empty($p['paid_until']) ? '<small>to ' . e(fmt_date($p['paid_until'])) . '</small>' : '' ?></div></div>
  <div class="tile"><span class="ic"><?= icon('id-card', 24) ?></span><div><small>Membership card</small><b><?= e($card['label']) ?></b></div></div>
  <div class="tile"><span class="ic"><?= icon('user', 24) ?></span><div><small>Member no.</small><b><?= e(member_no_label($p['member_no'] ?? 0) ?: '-') ?></b></div></div>
</div>

<div class="dash-grid">
  <div>
    <section class="panel">
      <h2>Upcoming events <a href="events.php">All events &rarr;</a></h2>
      <?php foreach ($events as $ev): ?>
        <div class="ev-mini">
          <div class="d"><b><?= e(fmt_date($ev['starts_at'], 'j')) ?></b><span><?= e(fmt_date($ev['starts_at'], 'M')) ?></span></div>
          <div><a href="<?= e(url('event_view.php?id=' . (int) $ev['id'])) ?>"><?= e($ev['title']) ?></a><br><span class="meta"><?= e(event_when($ev, 'D g:ia')) ?><?= $ev['location'] ? ' &middot; ' . e($ev['location']) : '' ?></span></div>
          <?php if (in_array($ev['id'], $myEvents)): ?><span class="badge" style="margin-left:auto">Registered</span>
          <?php elseif ($ev['registration_open']): ?><a class="btn" href="<?= e(url('event_view.php?id=' . (int) $ev['id'] . '#register')) ?>">Register</a><?php endif; ?>
        </div>
      <?php endforeach; if (!$events): ?><p class="meta">No dated events yet. <a href="events.php">See what is planned</a>.</p><?php endif; ?>
    </section>

    <section class="panel">
      <h2>Recent payments <a href="payments.php">All payments &rarr;</a></h2>
      <?php foreach ($payments as $x): [$pl, $pc] = $payBadge[$x['status']]; ?>
        <div class="ev-mini"><div><strong><?= e(ucfirst($x['purpose'])) ?></strong> &middot; UGX <?= e(number_format((float) $x['amount'])) ?><br><span class="meta"><?= e(fmt_date($x['paid_on'] ?: $x['created_at'])) ?> &middot; <?= e($x['reference']) ?></span></div><span class="badge <?= $pc ?>" style="margin-left:auto"><?= e($pl) ?></span></div>
      <?php endforeach; if (!$payments): ?><p class="meta">No payments recorded yet.</p><?php endif; ?>
    </section>
  </div>

  <div>
    <section class="panel">
      <h2>Your progress</h2>
      <div class="meter" aria-label="Profile completeness"><i style="width:<?= $pct ?>%"></i></div>
      <p class="meta" style="margin-top:0">Profile <?= $pct ?>% complete</p>
      <ul class="steps-list">
        <?php foreach ($next as [$label, $done, $href]): ?>
          <li class="<?= $done ? 'done' : '' ?>"><span class="tick"><?= $done ? icon('check', 13) : '' ?></span><span><?= !$done && $href ? '<a href="' . e($href) . '">' . e($label) . '</a>' : e($label) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </section>

    <?php if ($member): ?>
    <section class="panel">
      <h2>Latest news <a href="<?= e(url('news.php')) ?>">All news &rarr;</a></h2>
      <?php foreach ($news as $n): ?><p style="margin:.4em 0"><a href="<?= e(url('news_view.php?slug=' . urlencode($n['slug']))) ?>"><?= e($n['title']) ?></a><br><span class="meta"><?= e($n['category']) ?> &middot; <?= e(fmt_date($n['published_at'])) ?></span></p><?php endforeach; if (!$news): ?><p class="meta">Nothing published yet.</p><?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="panel">
      <h2>My details</h2>
      <p style="margin:.3em 0"><strong>Chapter:</strong> <?= e($p['chapter_name'] ?? 'Not set') ?></p>
      <p style="margin:.3em 0"><strong>Directory:</strong> <?= !empty($p['directory_consent']) ? 'Visible to members' : 'Hidden' ?></p>
      <p style="margin:.3em 0"><strong>Programme:</strong> <?= e($p['programme'] ?? '') ?> <?= !empty($p['graduation_year']) ? '(' . (int) $p['graduation_year'] . ')' : '' ?></p>
    </section>
  </div>
</div>

<section class="panel" style="margin-top:6px">
  <h2>Everything you can do in your portal</h2>
  <div class="explore">
    <?php
    // [label, link, icon, description, needs approved membership]
    $tools = [
        ['My profile', 'profile.php', 'user', 'Update your details, photo and directory visibility.', false],
        ['Membership card', 'card.php', 'id-card', 'View, print and share your card and QR code.', true],
        ['Payments and renewal', 'payments.php', 'wallet', 'Upload proof of payment and renew your subscription.', false],
        ['Events', 'events.php', 'calendar', 'See upcoming events and register in one click.', false],
        ['Chapters', 'chapters.php', 'map-pin', 'Register in your regional chapter.', true],
        ['Community projects', 'projects.php', 'target', 'Propose a project, then lead it once approved.', true],
        ['Documents', 'documents.php', 'file', 'Open reports and member-only documents.', true],
        ["Members' directory", 'directory.php', 'users', 'Find fellow alumni who chose to be listed.', true],
        ['Support', 'support.php', 'life-buoy', 'Ask for help or a correction to your details.', false],
    ];
    foreach ($tools as [$tl, $th, $ti, $td, $tn]):
        $locked = $tn && !$member; ?>
      <?php if ($locked): ?>
        <div class="ex locked" title="Available once your membership is approved"><span class="ex-ic"><?= icon($ti, 22) ?></span><div><b><?= e($tl) ?> <?= icon('lock', 13) ?></b><small>Opens when your membership is approved.</small></div></div>
      <?php else: ?>
        <a class="ex" href="<?= e($th) ?>"><span class="ex-ic"><?= icon($ti, 22) ?></span><div><b><?= e($tl) ?></b><small><?= e($td) ?></small></div></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</section>
<?php portal_footer(); ?>
