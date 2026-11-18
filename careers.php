<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/careers.php';
$pageTitle = 'Careers & opportunities';
$pageDesc = 'Jobs, internships, scholarships, fellowships and volunteering opportunities shared with the Gulu University alumni community.';
$me = current_user();
$member = is_member();
$vis = 'is_public = 1' . ($member ? '' : ' AND member_only = 0') . ' AND (deadline IS NULL OR deadline >= CURDATE())';

$id = (int) ($_GET['id'] ?? 0);
if ($id) {
    $job = q("SELECT * FROM jobs WHERE id = ? AND $vis", [$id])->fetch();
    if (!$job) {
        http_response_code(404);
        require __DIR__ . '/404.php';
        exit;
    }
    $applied = $me ? q('SELECT status FROM job_applications WHERE job_id = ? AND user_id = ?', [$id, $me['id']])->fetchColumn() : false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        if (!$member) {
            flash('Log in with an approved membership to apply.', 'error');
        } elseif (!$job['allow_applications']) {
            flash('This opportunity does not take applications here. Use the link or email provided.', 'error');
        } elseif ($applied !== false) {
            flash('You have already applied.', 'error');
        } else {
            q('INSERT INTO job_applications (job_id, user_id, cover_note) VALUES (?,?,?)', [$id, $me['id'], mb_substr(trim($_POST['cover_note'] ?? ''), 0, 3000)]);
            notify((int) $me['id'], 'Application received', $job['title'] . ' at ' . $job['organisation'], 'member/applications.php');
            flash('Your application has been submitted. Track it under Careers in your portal.');
        }
        redirect('careers.php?id=' . $id);
    }
    $pageTitle = $job['title'];
    require __DIR__ . '/includes/header.php';
    ?>
<div class="page-head"><h1><?= e($job['title']) ?></h1><p><?= e($job['organisation']) ?><?= $job['location'] ? ' &middot; ' . e($job['location']) : '' ?></p></div>
<div class="grid two">
  <div>
    <p><span class="badge"><?= e(JOB_KINDS[$job['kind']]) ?></span> <?= $job['member_only'] ? '<span class="badge warn">Members only</span>' : '' ?> <?= $job['deadline'] ? '<span class="meta">Deadline: ' . e(fmt_date($job['deadline'])) . '</span>' : '' ?></p>
    <?= render_text((string) $job['description']) ?>
    <?php if ($job['requirements']): ?><h2>Requirements</h2><?= render_text($job['requirements']) ?><?php endif; ?>
  </div>
  <div>
    <div class="card accent">
      <h2 style="margin-top:0">How to apply</h2>
      <?php if ($job['apply_url']): ?><p><a class="btn" href="<?= e($job['apply_url']) ?>" target="_blank" rel="noopener nofollow">Apply on the employer's site &#8599;</a></p><?php endif; ?>
      <?php if ($job['apply_email']): ?><p>Email: <a href="mailto:<?= e($job['apply_email']) ?>"><?= e($job['apply_email']) ?></a></p><?php endif; ?>
      <?php if ($job['allow_applications']): ?>
        <?php if ($applied !== false): ?><p><span class="badge"><?= e(ucfirst((string) $applied)) ?></span> You applied for this opportunity.</p>
        <?php elseif ($member): ?>
          <form method="post" class="stack"><?= csrf_field() ?>
            <label>Why are you a good fit? <span class="hint">(shared with the GUCO Career Officer; your portal profile is attached)</span><textarea name="cover_note" rows="5" maxlength="3000"></textarea></label>
            <button>Submit application through GUCO</button>
          </form>
        <?php else: ?><p class="meta">Members can apply through GUCO. <a href="<?= url('member/login.php') ?>">Log in</a> or <a href="<?= url('register.php') ?>">join GUCO</a>.</p><?php endif; ?>
      <?php endif; ?>
    </div>
    <p><a href="<?= url('careers.php') ?>">&larr; All opportunities</a></p>
  </div>
</div>
<?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$kind = $_GET['kind'] ?? '';
$search = trim($_GET['q'] ?? '');
$where = $vis;
$args = [];
if (isset(JOB_KINDS[$kind])) {
    $where .= ' AND kind = ?';
    $args[] = $kind;
}
if ($search !== '') {
    $where .= ' AND (title LIKE ? OR organisation LIKE ? OR location LIKE ?)';
    array_push($args, "%$search%", "%$search%", "%$search%");
}
$jobs = q("SELECT * FROM jobs WHERE $where ORDER BY deadline IS NULL, deadline, id DESC", $args)->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Careers &amp; Opportunities</h1><p>Jobs, internships, scholarships and fellowships shared with the GUCO community</p></div>
<form method="get" class="filters">
  <label>Search <input type="search" name="q" value="<?= e($search) ?>" placeholder="Title, employer or place"></label>
  <label>Type <select name="kind"><option value="">All</option><?php foreach (JOB_KINDS as $k => $l): ?><option value="<?= $k ?>"<?= $k === $kind ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
  <button>Search</button>
</form>
<div class="grid two">
<?php foreach ($jobs as $j): ?>
  <article class="card accent">
    <span class="badge"><?= e(JOB_KINDS[$j['kind']]) ?></span> <?= $j['member_only'] ? '<span class="badge warn">Members only</span>' : '' ?>
    <h2 style="margin:6px 0"><a href="?id=<?= (int) $j['id'] ?>" style="text-decoration:none"><?= e($j['title']) ?></a></h2>
    <p class="meta"><?= e($j['organisation']) ?><?= $j['location'] ? ' &middot; ' . e($j['location']) : '' ?><?= $j['deadline'] ? ' &middot; closes ' . e(fmt_date($j['deadline'])) : '' ?></p>
    <p><?= e(excerpt((string) $j['description'], 160)) ?></p>
    <a class="btn" href="?id=<?= (int) $j['id'] ?>">View details</a>
  </article>
<?php endforeach; if (!$jobs): ?><p>No opportunities match right now. Check back soon<?= $member ? '' : ', or log in to see member-only listings' ?>.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
