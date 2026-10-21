<?php
require_once __DIR__ . '/../includes/portal.php';
$me = require_login();
if (!is_member()) {
    flash('Only approved members can propose projects.', 'error');
    redirect('member/');
}
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$proj = null;
if ($id) {
    $proj = q('SELECT * FROM projects WHERE id = ? AND user_id = ?', [$id, $me['id']])->fetch();
    if (!$proj || !in_array($proj['status'], PROJECT_EDITABLE, true)) {
        flash('This proposal can no longer be edited.', 'error');
        redirect($proj ? 'member/project.php?id=' . $id : 'member/projects.php');
    }
}
$chapters = ['' => 'Not tied to a chapter'] + q('SELECT id, name FROM chapters WHERE is_public = 1 ORDER BY sort_order, name')->fetchAll(PDO::FETCH_KEY_PAIR);
$v = fn($k) => e($_POST[$k] ?? ($proj[$k] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $submit = ($_POST['action'] ?? '') === 'submit';
    $d = [];
    foreach (['title', 'theme', 'summary', 'location', 'problem', 'objectives', 'beneficiaries', 'activities', 'outcomes', 'resource_plan', 'team', 'risks'] as $k) {
        $d[$k] = trim($_POST[$k] ?? '');
    }
    $d['budget'] = max(0, (float) ($_POST['budget'] ?? 0));
    $d['start_date'] = ($_POST['start_date'] ?? '') ?: null;
    $d['end_date'] = ($_POST['end_date'] ?? '') ?: null;
    $d['chapter_id'] = isset($chapters[(int) ($_POST['chapter_id'] ?? 0)]) && (int) $_POST['chapter_id'] ? (int) $_POST['chapter_id'] : null;
    if (!in_array($d['theme'], PROJECT_THEMES, true)) {
        $d['theme'] = '';
    }
    $error = null;
    if ($d['title'] === '') {
        $error = 'Give the project a title.';
    } elseif ($d['start_date'] && $d['end_date'] && $d['end_date'] < $d['start_date']) {
        $error = 'The end date must be after the start date.';
    } elseif ($submit) {
        foreach (['theme' => 'theme', 'summary' => 'summary', 'location' => 'location', 'problem' => 'problem or need', 'objectives' => 'objectives', 'beneficiaries' => 'beneficiaries', 'activities' => 'activities and workplan', 'outcomes' => 'expected outcomes', 'resource_plan' => 'resource mobilisation plan'] as $k => $label) {
            if ($d[$k] === '') {
                $error = "Complete the $label before submitting.";
                break;
            }
        }
        if (!$error && (!$d['start_date'] || !$d['end_date'])) {
            $error = 'Enter the planned start and end dates.';
        } elseif (!$error && $d['budget'] <= 0) {
            $error = 'Enter the total budget.';
        } elseif (!$error && empty($_POST['commit'])) {
            $error = 'Please confirm that you will lead implementation and resource mobilisation if approved.';
        }
    }
    $att = null;
    if (!$error && ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $att = save_private_file($_FILES['attachment'], 'proof');
        $error = $att ? null : 'The attachment must be a PDF or image under 5MB.';
    }
    if ($error) {
        flash($error, 'error');
    } else {
        $cols = array_keys($d);
        $vals = array_values($d);
        if ($att) {
            $cols = array_merge($cols, ['attachment', 'attachment_original']);
            $vals = array_merge($vals, [$att['name'], $att['original']]);
        }
        if ($submit) {
            $cols = array_merge($cols, ['status', 'submitted_at']);
            $vals = array_merge($vals, ['submitted', date('Y-m-d H:i:s')]);
        }
        if ($proj) {
            q('UPDATE projects SET ' . implode(', ', array_map(fn($c) => "`$c` = ?", $cols)) . ' WHERE id = ?', array_merge($vals, [$proj['id']]));
            $pid = (int) $proj['id'];
        } else {
            q('INSERT INTO projects (user_id, ' . implode(', ', array_map(fn($c) => "`$c`", $cols)) . ') VALUES (?, ' . implode(',', array_fill(0, count($cols), '?')) . ')', array_merge([$me['id']], $vals));
            $pid = (int) db()->lastInsertId();
        }
        if ($submit) {
            audit('submit_project', "project $pid: {$d['title']}");
            flash('Proposal submitted. The Executive Committee will review it and you will be notified of the decision.');
        } else {
            flash('Draft saved.');
        }
        redirect('member/project.php?id=' . $pid);
    }
}
portal_header($proj ? 'Edit proposal' : 'Propose a project');
?>
<?php if ($proj && $proj['status'] === 'changes_requested' && $proj['review_note']): ?>
  <div class="notice bad"><strong>The Executive Committee asked for changes:</strong><br><?= nl2br(e($proj['review_note'])) ?></div>
<?php endif; ?>
<p class="meta">Describe the project you would like to lead. The Executive Committee reviews every proposal. If it is approved, you lead implementation and mobilise the resources, with support from GUCO.</p>
<form method="post" enctype="multipart/form-data" class="stack wide">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>">
  <fieldset><legend>1. Overview</legend>
    <label>Project title * <input name="title" required value="<?= $v('title') ?>"></label>
    <div class="cols">
      <label>Theme * <select name="theme"><option value="">Select</option><?php foreach (PROJECT_THEMES as $t): ?><option<?= ($_POST['theme'] ?? ($proj['theme'] ?? '')) === $t ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select></label>
      <label>Chapter <select name="chapter_id"><?php foreach ($chapters as $k => $n): ?><option value="<?= e($k) ?>"<?= (string) ($_POST['chapter_id'] ?? ($proj['chapter_id'] ?? '')) === (string) $k ? ' selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></label>
    </div>
    <label>One-paragraph summary * <span class="hint">(up to 500 characters)</span><textarea name="summary" rows="3" maxlength="500"><?= $v('summary') ?></textarea></label>
    <label>Project location * <span class="hint">(district, community or "online")</span><input name="location" value="<?= $v('location') ?>"></label>
  </fieldset>
  <fieldset><legend>2. The case</legend>
    <label>Problem or need being addressed *<textarea name="problem" rows="4"><?= $v('problem') ?></textarea></label>
    <label>Objectives *<textarea name="objectives" rows="4"><?= $v('objectives') ?></textarea></label>
    <label>Who will benefit, and how many? *<textarea name="beneficiaries" rows="3"><?= $v('beneficiaries') ?></textarea></label>
  </fieldset>
  <fieldset><legend>3. Plan and timeline</legend>
    <label>Main activities and workplan *<textarea name="activities" rows="5"><?= $v('activities') ?></textarea></label>
    <label>Expected outcomes and how success will be measured *<textarea name="outcomes" rows="3"><?= $v('outcomes') ?></textarea></label>
    <div class="cols">
      <label>Planned start date * <input type="date" name="start_date" value="<?= $v('start_date') ?>"></label>
      <label>Planned end date * <input type="date" name="end_date" value="<?= $v('end_date') ?>"></label>
    </div>
    <label>Risks and how you will manage them<textarea name="risks" rows="3"><?= $v('risks') ?></textarea></label>
  </fieldset>
  <fieldset><legend>4. Budget and resource mobilisation</legend>
    <label>Total budget (UGX) * <input type="number" name="budget" min="0" step="any" value="<?= $v('budget') ?>"></label>
    <label>How will you mobilise the resources? * <span class="hint">(donors, member contributions, partners, in-kind support, timeline)</span><textarea name="resource_plan" rows="4"><?= $v('resource_plan') ?></textarea></label>
    <label>Project team <span class="hint">(other members or partners who will help you)</span><textarea name="team" rows="3"><?= $v('team') ?></textarea></label>
    <label>Supporting document <span class="hint">(optional full proposal or budget; PDF or image up to 5MB)</span>
      <?= $proj && $proj['attachment'] ? '<span class="hint">Current: ' . e($proj['attachment_original']) . ' (upload to replace)</span>' : '' ?>
      <input type="file" name="attachment" accept="application/pdf,image/jpeg,image/png,image/webp"></label>
  </fieldset>
  <fieldset><legend>5. Commitment</legend>
    <label class="check"><input type="checkbox" name="commit" <?= !empty($_POST['commit']) ? 'checked' : '' ?>> If this proposal is approved, I will take the lead in implementing the project and mobilising its resources, and I will post regular progress updates.</label>
  </fieldset>
  <div class="row-actions">
    <button class="secondary" name="action" value="draft">Save draft</button>
    <button name="action" value="submit">Submit for review</button>
    <a class="btn secondary" href="projects.php">Cancel</a>
  </div>
</form>
<?php portal_footer(); ?>
