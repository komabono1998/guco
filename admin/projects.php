<?php
require_once __DIR__ . '/../includes/admin.php';
$me = require_perm('projects');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $p = q('SELECT p.*, u.email, u.full_name FROM projects p JOIN users u ON u.id = p.user_id WHERE p.id = ?', [$id])->fetch();
    if ($p) {
        $action = $_POST['action'] ?? '';
        $note = trim($_POST['note'] ?? '');
        $notify = function (string $subject, string $line) use ($p, $note) {
            send_mail($p['email'], $subject, "Dear {$p['full_name']},\n\n$line" . ($note !== '' ? "\n\nNote from the Executive Committee:\n$note" : '') . "\n\nOpen the Member Portal > Projects to see the details.\n\nGulu University Convocation");
        };
        $decide = function (string $status, bool $public = false) use ($id, $me, $note) {
            q('UPDATE projects SET status = ?, review_note = ?, reviewed_by = ?, reviewed_at = NOW(), is_public = ? WHERE id = ?', [$status, $note ?: null, $me['id'], $public ? 1 : 0, $id]);
        };
        if ($action === 'review' && $p['status'] === 'submitted') {
            q("UPDATE projects SET status = 'under_review', reviewed_by = ? WHERE id = ?", [$me['id'], $id]);
            audit('review_project', "project $id");
            flash('Marked as under review.');
        } elseif ($action === 'approve' && in_array($p['status'], ['submitted', 'under_review', 'changes_requested'], true)) {
            $decide('approved', !empty($_POST['public']));
            $notify('Your GUCO project proposal is approved', "Your project proposal \"{$p['title']}\" has been approved. You are now the project lead: please begin implementation and resource mobilisation and post regular updates.");
            audit('approve_project', "project $id: {$p['title']}");
            flash('Project approved. The proposer now leads implementation and resource mobilisation.');
        } elseif ($action === 'changes' && in_array($p['status'], ['submitted', 'under_review'], true)) {
            if ($note === '') {
                flash('Explain what needs to change.', 'error');
            } else {
                $decide('changes_requested');
                $notify('Changes requested on your GUCO project proposal', "The Executive Committee has asked for changes to \"{$p['title']}\".");
                audit('project_changes_requested', "project $id");
                flash('Changes requested. The proposer can edit and resubmit.');
            }
        } elseif ($action === 'reject' && in_array($p['status'], ['submitted', 'under_review', 'changes_requested'], true)) {
            if ($note === '') {
                flash('Give a reason for not approving the proposal.', 'error');
            } else {
                $decide('rejected');
                $notify('Update on your GUCO project proposal', "The Executive Committee was unable to approve \"{$p['title']}\".");
                audit('reject_project', "project $id");
                flash('Proposal declined.');
            }
        } elseif ($action === 'status' && in_array($_POST['status'] ?? '', ['in_progress', 'completed', 'cancelled', 'approved'], true) && in_array($p['status'], ['approved', 'in_progress', 'completed', 'cancelled'], true)) {
            q('UPDATE projects SET status = ? WHERE id = ?', [$_POST['status'], $id]);
            audit('set_project_status', "project $id -> {$_POST['status']}");
            flash('Status updated.');
        } elseif ($action === 'public') {
            q('UPDATE projects SET is_public = ? WHERE id = ?', [empty($_POST['public']) ? 0 : 1, $id]);
            audit('project_visibility', "project $id");
            flash('Visibility updated.');
        } elseif ($action === 'comment') {
            if (trim($_POST['body'] ?? '') !== '') {
                q('INSERT INTO project_comments (project_id, user_id, body, internal) VALUES (?,?,?,?)', [$id, $me['id'], trim($_POST['body']), empty($_POST['internal']) ? 0 : 1]);
                flash(empty($_POST['internal']) ? 'Message sent to the proposer.' : 'Internal note saved.');
            }
        } elseif ($action === 'verify' || $action === 'unverify') {
            q('UPDATE project_updates SET verified = ? WHERE id = ? AND project_id = ? AND kind = "mobilisation"', [$action === 'verify' ? 1 : 0, (int) $_POST['update_id'], $id]);
            audit($action . '_contribution', "project $id, update " . (int) $_POST['update_id']);
            flash($action === 'verify' ? 'Contribution verified.' : 'Verification removed.');
        }
    }
    redirect('admin/projects.php' . ($id ? "?id=$id" : ''));
}

if (isset($_GET['id'])) {
    $p = q('SELECT p.*, u.full_name, u.email, c.name AS chapter, mp.phone, mp.membership_id FROM projects p JOIN users u ON u.id = p.user_id LEFT JOIN member_profiles mp ON mp.user_id = u.id LEFT JOIN chapters c ON c.id = p.chapter_id WHERE p.id = ?', [(int) $_GET['id']])->fetch();
    if (!$p) {
        http_response_code(404);
        exit('Project not found.');
    }
    $id = (int) $p['id'];
    $comments = q('SELECT c.*, u.full_name FROM project_comments c JOIN users u ON u.id = c.user_id WHERE c.project_id = ? ORDER BY c.created_at', [$id])->fetchAll();
    $updates = q('SELECT * FROM project_updates WHERE project_id = ? ORDER BY created_at DESC', [$id])->fetchAll();
    $raised = project_raised($id);
    $open = in_array($p['status'], ['submitted', 'under_review', 'changes_requested'], true);
    admin_header('Project: ' . $p['title']);
    ?>
<p><a href="<?= url('admin/projects.php') ?>">&larr; All projects</a></p>
<div class="grid two">
  <div class="card">
    <p><?= project_badge($p['status']) ?> <span class="badge"><?= e($p['theme']) ?></span> <?= $p['chapter'] ? '<span class="badge">' . e($p['chapter']) . '</span>' : '' ?></p>
    <p><strong>Proposer and lead:</strong> <?= e($p['full_name']) ?> (<?= e($p['membership_id']) ?>)<br><span class="meta"><?= e($p['email']) ?><?= $p['phone'] ? ' &middot; ' . e($p['phone']) : '' ?></span></p>
    <p><strong>Location:</strong> <?= e($p['location']) ?><br><strong>Timeline:</strong> <?= $p['start_date'] ? e(fmt_date($p['start_date'])) . ' to ' . e(fmt_date($p['end_date'])) : 'not set' ?><br>
      <strong>Budget:</strong> UGX <?= e(number_format((float) $p['budget'])) ?> &middot; <strong>Verified mobilised:</strong> UGX <?= e(number_format($raised)) ?></p>
    <?php foreach (['summary' => 'Summary', 'problem' => 'Problem or need', 'objectives' => 'Objectives', 'beneficiaries' => 'Beneficiaries', 'activities' => 'Activities and workplan', 'outcomes' => 'Expected outcomes', 'risks' => 'Risks', 'resource_plan' => 'Resource mobilisation plan', 'team' => 'Team'] as $k => $label):
        if (trim((string) $p[$k]) !== ''): ?><h3 style="margin:.9em 0 .2em;font-size:.95rem"><?= e($label) ?></h3><p style="margin:0"><?= nl2br(e($p[$k])) ?></p><?php endif; endforeach; ?>
    <?php if ($p['attachment']): ?><p><a href="<?= e(member_file_url('project', $id)) ?>" target="_blank" rel="noopener">Open attachment: <?= e($p['attachment_original']) ?></a></p><?php endif; ?>
    <p class="meta">Submitted <?= e(fmt_date($p['submitted_at'], 'j M Y, g:ia')) ?></p>
  </div>

  <div>
    <form method="post" class="stack card">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <h3 style="margin:0">Executive decision</h3>
      <?php if ($p['review_note']): ?><p class="meta">Last note: <?= nl2br(e($p['review_note'])) ?></p><?php endif; ?>
      <?php if ($open): ?>
        <label>Note to the proposer <span class="hint">(required to request changes or decline)</span><textarea name="note" rows="3"></textarea></label>
        <label class="check"><input type="checkbox" name="public" checked> Publish on the Community Projects page when approved</label>
        <div class="row-actions">
          <?php if ($p['status'] === 'submitted'): ?><button class="secondary" name="action" value="review">Start review</button><?php endif; ?>
          <button name="action" value="approve">Approve</button>
          <?php if ($p['status'] !== 'changes_requested'): ?><button class="secondary" name="action" value="changes">Request changes</button><?php endif; ?>
          <button class="danger" name="action" value="reject" onclick="return confirm('Decline this proposal?')">Decline</button>
        </div>
      <?php else: ?>
        <p>This proposal is <strong><?= e(PROJECT_STATUS[$p['status']][0]) ?></strong>.</p>
        <?php if (in_array($p['status'], ['approved', 'in_progress', 'completed', 'cancelled'], true)): ?>
          <label>Change status <select name="status"><?php foreach (['approved' => 'Approved (not started)', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $k => $l): ?><option value="<?= $k ?>"<?= $p['status'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
          <div class="row-actions"><button class="secondary" name="action" value="status">Update status</button></div>
        <?php endif; ?>
      <?php endif; ?>
    </form>
    <?php if (in_array($p['status'], ['approved', 'in_progress', 'completed'], true)): ?>
    <form method="post" class="card" style="margin-top:14px"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="public">
      <label class="check"><input type="checkbox" name="public" <?= $p['is_public'] ? 'checked' : '' ?>> Show on the public Community Projects page</label>
      <button class="secondary" style="margin-top:8px">Save visibility</button></form>
    <?php endif; ?>

    <section class="card" style="margin-top:14px">
      <h3 style="margin-top:0">Messages and notes</h3>
      <?php foreach ($comments as $c): ?>
        <p style="margin:.5em 0;padding:8px 12px;border-radius:10px;background:<?= $c['internal'] ? '#fff3cd' : ($c['user_id'] == $p['user_id'] ? '#eef6ef' : '#e8eefc') ?>"><strong><?= e($c['full_name']) ?></strong>
          <?= $c['internal'] ? '<span class="badge warn">Internal</span>' : '' ?> <span class="meta"><?= e(fmt_date($c['created_at'], 'j M, g:ia')) ?></span><br><?= nl2br(e($c['body'])) ?></p>
      <?php endforeach; if (!$comments): ?><p class="meta">None yet.</p><?php endif; ?>
      <form method="post" class="stack"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="comment">
        <label>Message <textarea name="body" rows="3" required></textarea></label>
        <label class="check"><input type="checkbox" name="internal"> Internal note (not shown to the proposer)</label>
        <button>Post</button></form>
    </section>
  </div>
</div>

<?php if ($updates): ?>
<h2 style="margin-top:28px">Implementation updates and contributions</h2>
<div class="table-wrap"><table>
  <tr><th>Date</th><th>Type</th><th>Details</th><th>Amount (UGX)</th><th>Verification</th></tr>
  <?php foreach ($updates as $u): ?>
    <tr><td><?= e(fmt_date($u['created_at'])) ?></td><td><?= $u['kind'] === 'mobilisation' ? 'Resources' : 'Progress' ?></td><td><?= nl2br(e($u['body'])) ?></td>
      <td><?= $u['amount'] !== null ? e(number_format((float) $u['amount'])) : '' ?></td>
      <td><?php if ($u['kind'] === 'mobilisation'): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="update_id" value="<?= (int) $u['id'] ?>">
          <?= $u['verified'] ? '<span class="badge">Verified</span> <button class="secondary" name="action" value="unverify">Undo</button>' : '<button name="action" value="verify">Verify</button>' ?></form>
      <?php endif; ?></td></tr>
  <?php endforeach; ?>
</table></div>
<?php endif; ?>
    <?php
    admin_footer();
    exit;
}

$statusF = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$where = "p.status <> 'draft'";
$args = [];
if (isset(PROJECT_STATUS[$statusF]) && $statusF !== 'draft') {
    $where .= ' AND p.status = ?';
    $args[] = $statusF;
}
if ($search !== '') {
    $where .= ' AND (p.title LIKE ? OR u.full_name LIKE ?)';
    array_push($args, "%$search%", "%$search%");
}
$rows = q("SELECT p.id, p.title, p.theme, p.status, p.budget, p.submitted_at, p.is_public, u.full_name, c.name AS chapter,
    (SELECT COALESCE(SUM(amount),0) FROM project_updates x WHERE x.project_id = p.id AND x.kind = 'mobilisation' AND x.verified = 1) AS raised,
    (SELECT COUNT(*) FROM project_updates x WHERE x.project_id = p.id AND x.kind = 'mobilisation' AND x.verified = 0) AS unverified
    FROM projects p JOIN users u ON u.id = p.user_id LEFT JOIN chapters c ON c.id = p.chapter_id WHERE $where
    ORDER BY FIELD(p.status,'submitted','under_review','changes_requested','approved','in_progress','completed','rejected','cancelled'), p.submitted_at DESC", $args)->fetchAll();
admin_header('Projects');
?>
<form method="get" class="filters">
  <label>Status <select name="status"><option value="">All submitted</option><?php foreach (PROJECT_STATUS as $k => [$l]): if ($k === 'draft') continue; ?><option value="<?= $k ?>"<?= $k === $statusF ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
  <label>Search <input type="search" name="q" value="<?= e($search) ?>" placeholder="Title or proposer"></label>
  <button>Filter</button>
</form>
<div class="table-wrap"><table>
  <tr><th>Project</th><th>Proposer</th><th>Budget / raised (UGX)</th><th>Status</th><th>Submitted</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
    <tr><td><strong><?= e($r['title']) ?></strong><br><span class="meta"><?= e($r['theme']) ?><?= $r['chapter'] ? ' &middot; ' . e($r['chapter']) : '' ?></span></td>
      <td><?= e($r['full_name']) ?></td>
      <td><?= e(number_format((float) $r['budget'])) ?><br><span class="meta"><?= e(number_format((float) $r['raised'])) ?> verified<?= $r['unverified'] ? ' &middot; <span class="badge warn">' . (int) $r['unverified'] . ' to verify</span>' : '' ?></span></td>
      <td><?= project_badge($r['status']) ?><?= $r['is_public'] ? '<br><span class="meta">public</span>' : '' ?></td>
      <td><?= e(fmt_date($r['submitted_at'])) ?></td>
      <td><a class="btn secondary" href="?id=<?= (int) $r['id'] ?>">Review</a></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="6">No project proposals yet.</td></tr><?php endif; ?>
</table></div>
<?php admin_footer(); ?>
