<?php
require_once __DIR__ . '/../includes/portal.php';
$me = require_member();
$mid = (int) $me['id'];
$mine = q('SELECT * FROM mentorship_profiles WHERE user_id = ?', [$mid])->fetch() ?: null;
if ($mine && $mine['status'] === 'removed') {
    $blocked = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if (!empty($blocked) && $action !== '') {
        flash('Your mentorship profile was removed by the Secretariat. Please contact support.', 'error');
        redirect('member/mentorship.php');
    }
    if ($action === 'save') {
        $role = $_POST['role'] ?? '';
        $areas = trim($_POST['areas'] ?? '');
        if (!isset(MENTOR_ROLES[$role]) || $areas === '') {
            flash('Choose your role and list the areas you can mentor in, or want guidance on.', 'error');
        } else {
            $status = ($_POST['status'] ?? '') === 'paused' ? 'paused' : 'open';
            q('INSERT INTO mentorship_profiles (user_id, role, areas, about, availability, status) VALUES (?,?,?,?,?,?)
               ON DUPLICATE KEY UPDATE role = VALUES(role), areas = VALUES(areas), about = VALUES(about), availability = VALUES(availability), status = VALUES(status)',
                [$mid, $role, mb_substr($areas, 0, 300), mb_substr(trim($_POST['about'] ?? ''), 0, 600), mb_substr(trim($_POST['availability'] ?? ''), 0, 200), $status]);
            flash('Your mentorship profile is saved.');
        }
    } elseif ($action === 'leave') {
        q("DELETE FROM mentorship_profiles WHERE user_id = ? AND status <> 'removed'", [$mid]);
        flash('You have left the Mentorship Hub. Existing mentoring relationships stay until ended.');
    } elseif ($action === 'request' && $mine && in_array($mine['role'], ['mentee', 'both'], true)) {
        $mentor = (int) ($_POST['mentor'] ?? 0);
        $mp = q("SELECT * FROM mentorship_profiles WHERE user_id = ? AND status = 'open' AND role IN ('mentor','both')", [$mentor])->fetch();
        if ($mentor === $mid || !$mp || !discoverable_member($mentor) && !q("SELECT 1 FROM users WHERE id = ? AND status = 'approved'", [$mentor])->fetch()) {
            flash('That mentor is not available.', 'error');
        } elseif (q('SELECT 1 FROM mentorship_requests WHERE mentee_id = ? AND mentor_id = ?', [$mid, $mentor])->fetch()) {
            flash('You have already asked this mentor.', 'error');
        } elseif ((int) q("SELECT COUNT(*) FROM mentorship_requests WHERE mentee_id = ? AND status = 'pending'", [$mid])->fetchColumn() >= 5) {
            flash('You have 5 requests waiting. Please wait for replies first.', 'error');
        } else {
            q('INSERT INTO mentorship_requests (mentee_id, mentor_id, message) VALUES (?,?,?)', [$mid, $mentor, mb_substr(trim($_POST['message'] ?? ''), 0, 800)]);
            notify($mentor, 'Mentorship request', $me['full_name'] . ' is asking you to be their mentor.', 'member/mentorship.php');
            flash('Request sent. The mentor will see it in the Mentorship Hub.');
        }
    } elseif (in_array($action, ['accept', 'decline', 'end'], true)) {
        $r = q('SELECT * FROM mentorship_requests WHERE id = ? AND (mentor_id = ? OR (mentee_id = ? AND ? = "end"))', [(int) ($_POST['id'] ?? 0), $mid, $mid, $action])->fetch();
        if ($r && $action === 'end' && $r['status'] === 'accepted') {
            q("UPDATE mentorship_requests SET status = 'ended', responded_at = NOW() WHERE id = ?", [$r['id']]);
            notify((int) ($r['mentor_id'] == $mid ? $r['mentee_id'] : $r['mentor_id']), 'Mentoring ended', $me['full_name'] . ' ended your mentoring relationship.', 'member/mentorship.php');
            flash('Mentoring relationship ended.');
        } elseif ($r && $r['status'] === 'pending' && (int) $r['mentor_id'] === $mid && $action !== 'end') {
            q('UPDATE mentorship_requests SET status = ?, responded_at = NOW() WHERE id = ?', [$action === 'accept' ? 'accepted' : 'declined', $r['id']]);
            notify((int) $r['mentee_id'], $action === 'accept' ? 'Mentorship accepted' : 'Mentorship request declined', $action === 'accept' ? $me['full_name'] . ' agreed to mentor you. Contact details are now visible in the hub.' : $me['full_name'] . ' cannot mentor you right now.', 'member/mentorship.php');
            flash($action === 'accept' ? 'Accepted. Your contact details are now visible to your mentee.' : 'Request declined.');
        }
    }
    redirect('member/mentorship.php');
}

$q = trim($_GET['q'] ?? '');
$mentors = [];
if ($mine && in_array($mine['role'], ['mentee', 'both'], true) && $mine['status'] !== 'removed') {
    $sql = "SELECT m.*, u.full_name, p.programme, p.graduation_year, p.profession,
        (SELECT status FROM mentorship_requests r WHERE r.mentee_id = ? AND r.mentor_id = m.user_id) AS req
        FROM mentorship_profiles m JOIN users u ON u.id = m.user_id JOIN member_profiles p ON p.user_id = u.id
        WHERE m.status = 'open' AND m.role IN ('mentor','both') AND u.status = 'approved' AND p.card_status <> 'revoked' AND m.user_id <> ?";
    $args = [$mid, $mid];
    if ($q !== '') {
        $sql .= ' AND (m.areas LIKE ? OR m.about LIKE ? OR p.profession LIKE ? OR p.programme LIKE ? OR u.full_name LIKE ?)';
        array_push($args, ...array_fill(0, 5, "%$q%"));
    }
    $mentors = q($sql . ' ORDER BY u.full_name LIMIT 100', $args)->fetchAll();
}
$incoming = q("SELECT r.*, u.full_name, p.programme, p.graduation_year, p.profession FROM mentorship_requests r JOIN users u ON u.id = r.mentee_id JOIN member_profiles p ON p.user_id = u.id WHERE r.mentor_id = ? AND r.status = 'pending' ORDER BY r.created_at", [$mid])->fetchAll();
$pairs = q("SELECT r.id, r.mentor_id, r.mentee_id, u.full_name, u.email, p.phone, p.profession FROM mentorship_requests r
    JOIN users u ON u.id = IF(r.mentor_id = ?, r.mentee_id, r.mentor_id) JOIN member_profiles p ON p.user_id = u.id
    WHERE (r.mentor_id = ? OR r.mentee_id = ?) AND r.status = 'accepted'", [$mid, $mid, $mid])->fetchAll();
$out = q("SELECT r.status, u.full_name FROM mentorship_requests r JOIN users u ON u.id = r.mentor_id WHERE r.mentee_id = ? AND r.status IN ('pending','declined') ORDER BY r.created_at DESC", [$mid])->fetchAll();
portal_header('Mentorship Hub');
?>
<p class="meta">Experienced graduates guide students and recent graduates in careers and professional growth. Taking part is optional; your name, programme and the areas you list are visible to approved members, and contact details are shared only when a mentor accepts.</p>
<?php if (!empty($blocked)): ?><div class="notice bad">Your mentorship profile was removed by the Secretariat. Please <a href="support.php">contact support</a>.</div><?php else: ?>
<section class="panel">
  <h2>My mentorship profile</h2>
  <form method="post" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="save">
    <label>I am joining as <select name="role" required><option value="">Select</option><?php foreach (MENTOR_ROLES as $k => $l): ?><option value="<?= $k ?>"<?= ($mine['role'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label>Areas of expertise or guidance wanted * <span class="hint">(for example: nursing careers, agribusiness, public health research, ICT)</span><input name="areas" required maxlength="300" value="<?= e($mine['areas'] ?? '') ?>"></label>
    <label>About me <textarea name="about" rows="3" maxlength="600"><?= e($mine['about'] ?? '') ?></textarea></label>
    <label>Availability <input name="availability" maxlength="200" placeholder="e.g. one call a month, weekends" value="<?= e($mine['availability'] ?? '') ?>"></label>
    <label>Status <select name="status"><option value="open">Open to requests</option><option value="paused"<?= ($mine['status'] ?? '') === 'paused' ? ' selected' : '' ?>>Paused (hidden from search)</option></select></label>
    <div><button><?= $mine ? 'Update profile' : 'Join the Mentorship Hub' ?></button>
    <?php if ($mine): ?><button class="secondary" name="action" value="leave" onclick="return confirm('Leave the Mentorship Hub?')" formnovalidate>Leave the hub</button><?php endif; ?></div>
  </form>
</section>

<?php if ($incoming): ?>
<section class="panel"><h2>Requests for you to mentor</h2>
  <?php foreach ($incoming as $r): ?>
    <form method="post" class="ev-mini"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
      <div><strong><?= e($r['full_name']) ?></strong> <span class="meta"><?= e($r['programme']) ?> <?= $r['graduation_year'] ? '(' . (int) $r['graduation_year'] . ')' : '' ?> &middot; <?= e($r['profession']) ?></span><br><?= e($r['message']) ?></div>
      <span style="margin-left:auto;display:flex;gap:6px"><button name="action" value="accept">Accept</button><button class="secondary" name="action" value="decline">Decline</button></span></form>
  <?php endforeach; ?></section>
<?php endif; ?>

<?php if ($pairs): ?>
<section class="panel"><h2>My mentoring relationships</h2>
  <?php foreach ($pairs as $r): ?>
    <form method="post" class="ev-mini"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="end">
      <div><strong><?= e($r['full_name']) ?></strong> <span class="badge"><?= (int) $r['mentor_id'] === $mid ? 'My mentee' : 'My mentor' ?></span><br><span class="meta"><?= e($r['profession']) ?></span><br><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a><?= $r['phone'] ? ' &middot; ' . e($r['phone']) : '' ?></div>
      <button class="secondary" style="margin-left:auto" onclick="return confirm('End this mentoring relationship?')">End</button></form>
  <?php endforeach; ?></section>
<?php endif; ?>

<?php if ($mine && in_array($mine['role'], ['mentee', 'both'], true) && $mine['status'] !== 'removed'): ?>
<section class="panel"><h2>Find a mentor</h2>
  <form method="get" class="filters"><label>Search <input type="search" name="q" value="<?= e($q) ?>" placeholder="Area, profession, programme..."></label><button>Search</button></form>
  <?php foreach ($mentors as $m): ?>
    <form method="post" class="ev-mini"><?= csrf_field() ?><input type="hidden" name="action" value="request"><input type="hidden" name="mentor" value="<?= (int) $m['user_id'] ?>">
      <div><strong><?= e($m['full_name']) ?></strong> <span class="meta"><?= e($m['profession']) ?> &middot; <?= e($m['programme']) ?> <?= $m['graduation_year'] ? '(' . (int) $m['graduation_year'] . ')' : '' ?></span><br>
        <span class="badge"><?= e($m['areas']) ?></span><?= $m['about'] ? '<br>' . e($m['about']) : '' ?><?= $m['availability'] ? '<br><span class="meta">Availability: ' . e($m['availability']) . '</span>' : '' ?></div>
      <span style="margin-left:auto;min-width:210px">
        <?php if ($m['req']): ?><span class="badge <?= $m['req'] === 'accepted' ? '' : 'warn' ?>"><?= e(ucfirst($m['req'])) ?></span>
        <?php else: ?><input name="message" placeholder="Why you are asking (short)" maxlength="800" style="width:100%;margin-bottom:6px"><button class="secondary">Request mentorship</button><?php endif; ?></span></form>
  <?php endforeach; if (!$mentors): ?><p class="meta">No open mentors match yet.</p><?php endif; ?>
  <?php foreach ($out as $o): ?><p class="meta" style="margin:.3em 0">Your request to <?= e($o['full_name']) ?>: <?= e($o['status']) ?></p><?php endforeach; ?>
</section>
<?php endif; ?>
<?php endif; ?>
<?php portal_footer(); ?>
