<?php
require_once __DIR__ . '/../includes/portal.php';
$me = require_member();
$mid = (int) $me['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $back = 'member/connections.php';
    if ($action === 'request') {
        $to = (int) ($_POST['to'] ?? 0);
        if ($to === $mid || !discoverable_member($to)) {
            flash('That member is not available to connect.', 'error');
        } elseif (connection_between($mid, $to)) {
            flash('You already have a connection or a pending request with this member.', 'error');
        } elseif ((int) q("SELECT COUNT(*) FROM connections WHERE requester_id = ? AND status = 'pending'", [$mid])->fetchColumn() >= 20) {
            flash('You have 20 requests waiting. Please wait for replies before sending more.', 'error');
        } else {
            q('INSERT INTO connections (requester_id, addressee_id, message) VALUES (?,?,?)', [$mid, $to, mb_substr(trim($_POST['message'] ?? ''), 0, 400)]);
            notify($to, 'New connection request', $me['full_name'] . ' wants to connect with you.', 'member/connections.php');
            flash('Connection request sent.');
            $back = 'member/directory.php' . (($_POST['q'] ?? '') !== '' ? '?q=' . urlencode((string) $_POST['q']) : '');
        }
    } elseif (in_array($action, ['accept', 'decline'], true)) {
        $c = q("SELECT * FROM connections WHERE id = ? AND addressee_id = ? AND status = 'pending'", [(int) ($_POST['id'] ?? 0), $mid])->fetch();
        if ($c) {
            q('UPDATE connections SET status = ?, responded_at = NOW() WHERE id = ?', [$action === 'accept' ? 'accepted' : 'declined', $c['id']]);
            if ($action === 'accept') {
                notify((int) $c['requester_id'], 'Connection accepted', $me['full_name'] . ' accepted your request. You can now see each other\'s contact details.', 'member/connections.php');
            }
            flash($action === 'accept' ? 'Connected. Your contact details are now shared with this member.' : 'Request declined.');
        }
    } elseif ($action === 'remove') {
        $c = q("SELECT * FROM connections WHERE id = ? AND (requester_id = ? OR addressee_id = ?)", [(int) ($_POST['id'] ?? 0), $mid, $mid])->fetch();
        if ($c) {
            q('DELETE FROM connections WHERE id = ?', [$c['id']]);
            flash('Connection removed.');
        }
    }
    redirect($back);
}

$incoming = q("SELECT c.*, u.full_name, p.programme, p.graduation_year, p.profession FROM connections c JOIN users u ON u.id = c.requester_id JOIN member_profiles p ON p.user_id = u.id WHERE c.addressee_id = ? AND c.status = 'pending' ORDER BY c.created_at DESC", [$mid])->fetchAll();
$mine = q("SELECT c.id, u.full_name, u.email, p.phone, p.programme, p.graduation_year, p.profession, p.location FROM connections c
    JOIN users u ON u.id = IF(c.requester_id = ?, c.addressee_id, c.requester_id) JOIN member_profiles p ON p.user_id = u.id
    WHERE (c.requester_id = ? OR c.addressee_id = ?) AND c.status = 'accepted' ORDER BY u.full_name", [$mid, $mid, $mid])->fetchAll();
$sent = q("SELECT c.id, u.full_name FROM connections c JOIN users u ON u.id = c.addressee_id WHERE c.requester_id = ? AND c.status = 'pending' ORDER BY c.created_at DESC", [$mid])->fetchAll();
portal_header('My connections');
?>
<p class="meta">Connecting shares your email and phone with the other member, and theirs with you. You can remove a connection at any time. <a href="directory.php">Find alumni in the directory</a>.</p>
<?php if ($incoming): ?>
<section class="panel">
  <h2>Requests waiting for you</h2>
  <?php foreach ($incoming as $c): ?>
    <form method="post" class="ev-mini"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
      <div><strong><?= e($c['full_name']) ?></strong><br><span class="meta"><?= e($c['programme']) ?> <?= $c['graduation_year'] ? '(' . (int) $c['graduation_year'] . ')' : '' ?> &middot; <?= e($c['profession']) ?></span><?= $c['message'] ? '<br>' . e($c['message']) : '' ?></div>
      <span style="margin-left:auto;display:flex;gap:6px"><button name="action" value="accept">Accept</button><button class="secondary" name="action" value="decline">Decline</button></span></form>
  <?php endforeach; ?>
</section>
<?php endif; ?>
<section class="panel">
  <h2>My connections (<?= count($mine) ?>)</h2>
  <?php foreach ($mine as $c): ?>
    <form method="post" class="ev-mini"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
      <div><strong><?= e($c['full_name']) ?></strong><br><span class="meta"><?= e($c['programme']) ?> <?= $c['graduation_year'] ? '(' . (int) $c['graduation_year'] . ')' : '' ?> &middot; <?= e($c['profession']) ?> &middot; <?= e($c['location']) ?></span><br>
        <a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a><?= $c['phone'] ? ' &middot; ' . e($c['phone']) : '' ?></div>
      <button class="secondary" name="action" value="remove" style="margin-left:auto" onclick="return confirm('Remove this connection?')">Remove</button></form>
  <?php endforeach; if (!$mine): ?><p class="meta">No connections yet.</p><?php endif; ?>
</section>
<?php if ($sent): ?>
<section class="panel">
  <h2>Requests you sent</h2>
  <?php foreach ($sent as $c): ?>
    <form method="post" class="ev-mini"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><div><?= e($c['full_name']) ?> <span class="badge warn">waiting</span></div>
      <button class="secondary" name="action" value="remove" style="margin-left:auto">Cancel</button></form>
  <?php endforeach; ?>
</section>
<?php endif; ?>
<?php portal_footer(); ?>
