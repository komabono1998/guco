<?php
require_once __DIR__ . '/../includes/admin.php';
require_perm('events');
$ev = q('SELECT * FROM events WHERE id = ?', [(int) ($_GET['event'] ?? $_POST['event'] ?? 0)])->fetch();
if (!$ev) {
    http_response_code(404);
    exit('Event not found.');
}
$chapters = q('SELECT id, name FROM chapters ORDER BY sort_order, name')->fetchAll(PDO::FETCH_KEY_PAIR);
$count = fn($sql, $a = []) => (int) q($sql, $a)->fetchColumn();
$members = q("SELECT email FROM users WHERE role = 'member' AND status = 'approved'")->fetchAll(PDO::FETCH_COLUMN);
$subs = q('SELECT email FROM subscribers')->fetchAll(PDO::FETCH_COLUMN);
$regs = q('SELECT email FROM event_registrations WHERE event_id = ?', [$ev['id']])->fetchAll(PDO::FETCH_COLUMN);
$link = absolute_url('event_view.php?id=' . (int) $ev['id']);
$when = $ev['starts_at'] ? fmt_date($ev['starts_at'], 'l j F Y, g:ia') : 'Date to be announced';
$defaultMsg = "Dear friend,\n\nYou are invited to: {$ev['title']}\n\nWhen: $when\n" . ($ev['location'] ? "Venue: {$ev['location']}\n" : '') . ($ev['online_link'] ? "Online: {$ev['online_link']}\n" : '')
    . ($ev['description'] ? "\n" . trim($ev['description']) . "\n" : '') . "\n" . ($ev['registration_open'] ? 'Register or read more' : 'More details') . ": $link\n\nWe look forward to seeing you.";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $to = [];
    if (!empty($_POST['members'])) {
        $to = array_merge($to, $members);
    }
    if (!empty($_POST['subscribers'])) {
        $to = array_merge($to, $subs);
    }
    if (!empty($_POST['registrants'])) {
        $to = array_merge($to, $regs);
    }
    $cid = (int) ($_POST['chapter'] ?? 0);
    if ($cid && isset($chapters[$cid])) {
        $to = array_merge($to, q("SELECT u.email FROM chapter_members m JOIN users u ON u.id = m.user_id WHERE m.chapter_id = ? AND m.status = 'approved'", [$cid])->fetchAll(PDO::FETCH_COLUMN));
    }
    $to = valid_emails(array_merge($to, preg_split('/[\s,;]+/', (string) ($_POST['extra'] ?? ''))));
    $subject = trim($_POST['subject'] ?? '') ?: 'Invitation: ' . $ev['title'];
    $message = trim($_POST['message'] ?? '');
    if (!$to) {
        flash('Choose at least one audience or add an email address.', 'error');
    } elseif ($message === '') {
        flash('Write the invitation message.', 'error');
    } else {
        $ics = $ev['starts_at'] && $ev['status'] !== 'cancelled' ? event_ics_text($ev, 'REQUEST') : null;
        $html = '<p>' . nl2br(e($message)) . '</p>';
        $sent = 0;
        foreach ($to as $addr) {
            $sent += deliver_mail($addr, $subject, $message, ['html' => $html, 'ics' => $ics, 'context' => 'invite:' . $ev['id']]) ? 1 : 0;
        }
        audit('send_event_invites', "event {$ev['id']}: " . count($to) . " recipients, $sent delivered");
        flash(count($to) . " invitation(s) processed: $sent delivered" . ($sent < count($to) ? ', ' . (count($to) - $sent) . ' failed. See the Mail log for details.' : '.'), $sent === count($to) ? 'success' : 'error');
    }
    redirect('admin/event_invite.php?event=' . $ev['id']);
}
$log = q("SELECT status, COUNT(*) n, MAX(created_at) last FROM mail_log WHERE context = ? GROUP BY status", ['invite:' . $ev['id']])->fetchAll();
admin_header('Send invitations: ' . $ev['title']);
?>
<p><a href="<?= url('admin/events.php') ?>">&larr; Events</a></p>
<?php if (!$ev['starts_at']): ?><p class="notice">This event has no confirmed date yet. You can still send an invitation, but it will not include a calendar entry.</p><?php endif; ?>
<form method="post" class="stack wide card">
  <?= csrf_field() ?><input type="hidden" name="event" value="<?= (int) $ev['id'] ?>">
  <h3 style="margin:0">Who should be invited?</h3>
  <label class="check"><input type="checkbox" name="members"> All approved members (<?= count($members) ?>)</label>
  <label class="check"><input type="checkbox" name="subscribers"> Newsletter subscribers (<?= count($subs) ?>)</label>
  <label class="check"><input type="checkbox" name="registrants"> People already registered for this event (<?= count($regs) ?>)</label>
  <label>Members of a chapter
    <select name="chapter"><option value="">None</option><?php foreach ($chapters as $k => $n): ?><option value="<?= (int) $k ?>"><?= e($n) ?> (<?= $count("SELECT COUNT(*) FROM chapter_members WHERE chapter_id = ? AND status = 'approved'", [$k]) ?>)</option><?php endforeach; ?></select></label>
  <label>Other email addresses <span class="hint">(guests, partners; separated by commas or new lines)</span><textarea name="extra" rows="3"></textarea></label>
  <h3 style="margin:8px 0 0">Invitation</h3>
  <label>Subject <input name="subject" value="<?= e('Invitation: ' . $ev['title']) ?>"></label>
  <label>Message <textarea name="message" rows="12"><?= e($defaultMsg) ?></textarea></label>
  <p class="meta">Each email carries a calendar invitation (.ics) that opens in Outlook, Google Calendar and Apple Calendar. Delivery results are recorded in the Mail log.</p>
  <button onclick="return confirm('Send this invitation now?')">Send invitations</button>
</form>
<?php if ($log): ?><p class="meta">Previously for this event: <?php foreach ($log as $l): ?><?= (int) $l['n'] ?> <?= e($l['status']) ?> (last <?= e(fmt_date($l['last'], 'j M, g:ia')) ?>) &nbsp; <?php endforeach; ?></p><?php endif; ?>
<?php admin_footer(); ?>
