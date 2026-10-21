<?php
require_once __DIR__ . '/../includes/portal.php';
$me = require_login();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$p = q('SELECT p.*, c.name AS chapter FROM projects p LEFT JOIN chapters c ON c.id = p.chapter_id WHERE p.id = ? AND p.user_id = ?', [$id, $me['id']])->fetch();
if (!$p) {
    flash('Project not found.', 'error');
    redirect('member/projects.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $status = $p['status'];
    if ($action === 'comment') {
        $body = trim($_POST['body'] ?? '');
        if ($body !== '') {
            q('INSERT INTO project_comments (project_id, user_id, body, internal) VALUES (?,?,?,0)', [$id, $me['id'], $body]);
            flash('Message sent to the Executive Committee.');
        }
    } elseif ($action === 'update' && in_array($status, array_merge(PROJECT_ACTIVE, ['completed']), true)) {
        $body = trim($_POST['body'] ?? '');
        $kind = ($_POST['kind'] ?? '') === 'mobilisation' ? 'mobilisation' : 'progress';
        $amount = $kind === 'mobilisation' ? (float) ($_POST['amount'] ?? 0) : null;
        if ($body === '' || ($kind === 'mobilisation' && $amount <= 0)) {
            flash($kind === 'mobilisation' ? 'Describe the contribution and enter the amount (UGX).' : 'Write your progress update.', 'error');
        } else {
            q('INSERT INTO project_updates (project_id, user_id, kind, body, amount) VALUES (?,?,?,?,?)', [$id, $me['id'], $kind, $body, $amount]);
            flash($kind === 'mobilisation' ? 'Contribution recorded. It counts towards the total once the Executive Committee verifies it.' : 'Progress update posted.');
        }
    } elseif ($action === 'start' && $status === 'approved') {
        q("UPDATE projects SET status = 'in_progress' WHERE id = ?", [$id]);
        audit('start_project', "project $id");
        flash('Implementation started. Post progress updates as you go.');
    } elseif ($action === 'complete' && $status === 'in_progress') {
        q("UPDATE projects SET status = 'completed' WHERE id = ?", [$id]);
        audit('complete_project', "project $id");
        flash('Project marked as completed. Thank you for leading it.');
    } elseif ($action === 'withdraw' && in_array($status, ['draft', 'submitted', 'under_review', 'changes_requested'], true)) {
        q("UPDATE projects SET status = 'cancelled' WHERE id = ?", [$id]);
        flash('Proposal withdrawn.');
    } elseif ($action === 'delete' && $status === 'draft') {
        q('DELETE FROM projects WHERE id = ?', [$id]);
        flash('Draft deleted.');
        redirect('member/projects.php');
    }
    redirect('member/project.php?id=' . $id);
}

$comments = q('SELECT c.*, u.full_name, u.role FROM project_comments c JOIN users u ON u.id = c.user_id WHERE c.project_id = ? AND c.internal = 0 ORDER BY c.created_at', [$id])->fetchAll();
$updates = q('SELECT * FROM project_updates WHERE project_id = ? ORDER BY created_at DESC', [$id])->fetchAll();
$raised = project_raised($id);
$pending = (float) q("SELECT COALESCE(SUM(amount),0) FROM project_updates WHERE project_id = ? AND kind = 'mobilisation' AND verified = 0", [$id])->fetchColumn();
$pct = $p['budget'] > 0 ? min(100, round($raised / $p['budget'] * 100)) : 0;
$lead = in_array($p['status'], array_merge(PROJECT_ACTIVE, ['completed']), true);
portal_header($p['title']);
?>
<p><a href="projects.php">&larr; All my projects</a></p>
<div class="welcome" style="padding:18px 24px">
  <div><h2 style="font-size:1.25rem"><?= e($p['title']) ?></h2><p><?= e($p['theme']) ?><?= $p['chapter'] ? ' &middot; ' . e($p['chapter']) : '' ?><?= $p['location'] ? ' &middot; ' . e($p['location']) : '' ?></p></div>
  <div class="actions"><span class="badge" style="background:#fff;color:var(--green)"><?= e(PROJECT_STATUS[$p['status']][0]) ?></span>
    <?php if (in_array($p['status'], PROJECT_EDITABLE, true)): ?><a class="btn gold" href="project_form.php?id=<?= $id ?>">Edit proposal</a><?php endif; ?></div>
</div>

<?php if ($p['status'] === 'changes_requested'): ?><div class="notice bad"><strong>Changes requested.</strong> <?= nl2br(e($p['review_note'])) ?></div><?php endif; ?>
<?php if ($p['status'] === 'rejected'): ?><div class="notice bad"><strong>Not approved.</strong> <?= nl2br(e($p['review_note'])) ?></div><?php endif; ?>
<?php if (in_array($p['status'], ['approved', 'in_progress'], true)): ?><div class="notice"><strong>Approved by the Executive Committee.</strong> You are the project lead: implement the project, mobilise resources and keep posting updates. <?= $p['review_note'] ? '<br>' . nl2br(e($p['review_note'])) : '' ?></div><?php endif; ?>
<?php if (in_array($p['status'], ['submitted', 'under_review'], true)): ?><div class="notice">Your proposal is <?= $p['status'] === 'submitted' ? 'waiting for' : 'being reviewed by' ?> the Executive Committee.</div><?php endif; ?>

<?php if ($lead): ?>
<section class="panel" id="updates">
  <h2>Resource mobilisation and progress</h2>
  <div class="meter" aria-label="Funds mobilised"><i style="width:<?= $pct ?>%"></i></div>
  <p class="meta" style="margin-top:0">UGX <?= e(number_format($raised)) ?> verified of UGX <?= e(number_format((float) $p['budget'])) ?> budget (<?= $pct ?>%)<?= $pending > 0 ? ' &middot; UGX ' . e(number_format($pending)) . ' awaiting verification' : '' ?></p>
  <?php if (in_array($p['status'], PROJECT_ACTIVE, true)): ?>
  <form method="post" class="stack wide">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="update">
    <div class="cols">
      <label>Update type <select name="kind"><option value="progress">Progress update</option><option value="mobilisation">Resources mobilised (funds or in-kind)</option></select></label>
      <label>Amount (UGX) <span class="hint">(for resources mobilised)</span><input type="number" name="amount" min="0" step="any"></label>
    </div>
    <label>Details <textarea name="body" rows="3" required></textarea></label>
    <div class="row-actions"><button>Post update</button>
      <?php if ($p['status'] === 'approved'): ?><button class="secondary" name="action" value="start" formnovalidate>Start implementation</button><?php endif; ?>
      <?php if ($p['status'] === 'in_progress'): ?><button class="secondary" name="action" value="complete" formnovalidate onclick="return confirm('Mark this project as completed?')">Mark completed</button><?php endif; ?></div>
  </form>
  <?php endif; ?>
  <h3>Timeline</h3>
  <?php foreach ($updates as $u): ?>
    <div class="ev-mini" style="align-items:flex-start"><div style="flex:1"><span class="badge <?= $u['kind'] === 'mobilisation' ? 'warn' : '' ?>"><?= $u['kind'] === 'mobilisation' ? 'Resources' : 'Progress' ?></span>
      <?= $u['kind'] === 'mobilisation' ? '<strong>UGX ' . e(number_format((float) $u['amount'])) . '</strong> ' . ($u['verified'] ? '<span class="badge">Verified</span>' : '<span class="badge warn">Awaiting verification</span>') : '' ?><br>
      <?= nl2br(e($u['body'])) ?><br><span class="meta"><?= e(fmt_date($u['created_at'], 'j M Y')) ?></span></div></div>
  <?php endforeach; if (!$updates): ?><p class="meta">No updates yet.</p><?php endif; ?>
</section>
<?php endif; ?>

<div class="dash-grid">
  <div>
    <section class="panel"><h2>Proposal</h2>
      <?php foreach (['summary' => 'Summary', 'problem' => 'Problem or need', 'objectives' => 'Objectives', 'beneficiaries' => 'Beneficiaries', 'activities' => 'Activities and workplan', 'outcomes' => 'Expected outcomes', 'risks' => 'Risks', 'resource_plan' => 'Resource mobilisation plan', 'team' => 'Team'] as $k => $label):
          if (trim((string) $p[$k]) !== ''): ?><h3 style="margin:.9em 0 .2em;font-size:.95rem"><?= e($label) ?></h3><p style="margin:0"><?= nl2br(e($p[$k])) ?></p><?php endif; endforeach; ?>
      <h3 style="margin:.9em 0 .2em;font-size:.95rem">Timeline and budget</h3>
      <p style="margin:0"><?= $p['start_date'] ? e(fmt_date($p['start_date'])) . ' to ' . e(fmt_date($p['end_date'])) : 'Dates not set' ?> &middot; Budget UGX <?= e(number_format((float) $p['budget'])) ?></p>
      <?php if ($p['attachment']): ?><p><a href="<?= e(member_file_url('project', $id)) ?>" target="_blank" rel="noopener"><?= icon('file', 16) ?> <?= e($p['attachment_original']) ?></a></p><?php endif; ?>
    </section>
  </div>
  <div>
    <section class="panel"><h2>Conversation with the Executive</h2>
      <?php foreach ($comments as $c): ?>
        <p style="margin:.6em 0;padding:8px 12px;border-radius:10px;background:<?= (int) $c['user_id'] === (int) $me['id'] ? '#eef6ef' : '#fff8e1' ?>"><strong><?= (int) $c['user_id'] === (int) $me['id'] ? 'You' : e($c['full_name']) ?></strong> <span class="meta"><?= e(fmt_date($c['created_at'], 'j M, g:ia')) ?></span><br><?= nl2br(e($c['body'])) ?></p>
      <?php endforeach; if (!$comments): ?><p class="meta">No messages yet.</p><?php endif; ?>
      <form method="post" class="stack"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="comment">
        <label>Message <textarea name="body" rows="3" required></textarea></label><button>Send</button></form>
    </section>
    <?php if (in_array($p['status'], ['draft', 'submitted', 'under_review', 'changes_requested'], true)): ?>
    <section class="panel"><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <div class="row-actions">
        <button class="secondary" name="action" value="withdraw" onclick="return confirm('Withdraw this proposal?')">Withdraw proposal</button>
        <?php if ($p['status'] === 'draft'): ?><button class="danger" name="action" value="delete" onclick="return confirm('Delete this draft?')">Delete draft</button><?php endif; ?>
      </div></form></section>
    <?php endif; ?>
  </div>
</div>
<?php portal_footer(); ?>
