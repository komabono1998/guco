<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/careers.php';
$pageTitle = 'Scholarships, jobs & funding';
$pageDesc = 'Scholarships for undergraduate, master\'s and PhD study, fellowships, grants, jobs and internships, locally and abroad, with links to the official application pages.';
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
    $lv = job_level_labels($job['levels'] ?? '');
    ?>
<div class="page-head"><h1><?= e($job['title']) ?></h1><p><?= e($job['organisation']) ?><?= $job['location'] ? ' &middot; ' . e($job['location']) : '' ?></p></div>
<div class="grid two">
  <div>
    <p>
      <span class="badge"><?= e(JOB_KINDS[$job['kind']]) ?></span>
      <?php foreach ($lv as $l): ?><span class="badge"><?= e($l) ?></span><?php endforeach; ?>
      <span class="badge <?= $job['scope'] === 'local' ? 'warn' : '' ?>"><?= e(JOB_SCOPES[$job['scope']] ?? '') ?></span>
      <?= $job['member_only'] ? '<span class="badge warn">Members only</span>' : '' ?>
      <?= $job['deadline'] ? '<span class="meta">Deadline: ' . e(fmt_date($job['deadline'])) . '</span>' : '' ?>
    </p>
    <?= render_text((string) $job['description']) ?>
    <?php if ($job['requirements']): ?><h2>Before you apply</h2><?= render_text($job['requirements']) ?><?php endif; ?>
    <?php if ($job['verified_on']): ?><p class="meta">Official link checked on <?= e(fmt_date($job['verified_on'], 'j F Y')) ?>. Calls, deadlines and rules change every cycle, so always confirm them on the official page.</p><?php endif; ?>
  </div>
  <div>
    <div class="card accent">
      <h2 style="margin-top:0">How to apply</h2>
      <?php if ($job['apply_url']): ?><p><a class="btn" href="<?= e($job['apply_url']) ?>" target="_blank" rel="noopener nofollow">Open the official page &#8599;</a></p><?php endif; ?>
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
      <p class="meta" style="margin-bottom:0">Apply only on the official website. Be careful of anyone who asks you to pay to be considered.</p>
    </div>
    <p><a href="<?= url('careers.php') ?>">&larr; All opportunities</a></p>
  </div>
</div>
<?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$items = q("SELECT * FROM jobs WHERE $vis ORDER BY FIELD(kind,'scholarship','fellowship','grant','internship','job','volunteer','resource'), deadline IS NULL, deadline, title")->fetchAll();
$kindCount = array_count_values(array_column($items, 'kind'));
$initial = [
    'kind' => isset(JOB_KINDS[$_GET['kind'] ?? '']) ? $_GET['kind'] : '',
    'level' => isset(JOB_LEVELS[$_GET['level'] ?? '']) ? $_GET['level'] : '',
    'scope' => isset(JOB_SCOPES[$_GET['scope'] ?? '']) ? $_GET['scope'] : '',
    'q' => trim((string) ($_GET['q'] ?? '')),
];
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Scholarships, Jobs &amp; Funding</h1><p>Scholarships for undergraduate, master's and PhD study, fellowships, grants, jobs and internships, locally and abroad</p></div>

<section class="op" id="op">
  <div class="op-tabs" role="tablist" aria-label="Type of opportunity">
    <button type="button" role="tab" class="op-tab on" data-kind="" aria-selected="true"><?= icon('search', 18) ?> All <b><?= count($items) ?></b></button>
    <?php foreach (JOB_KINDS as $k => $label): if (empty($kindCount[$k])) continue; ?>
      <button type="button" role="tab" class="op-tab" data-kind="<?= $k ?>" aria-selected="false"><?= icon(JOB_KIND_ICONS[$k], 18) ?> <?= e(JOB_KIND_PLURAL[$k]) ?> <b><?= (int) $kindCount[$k] ?></b></button>
    <?php endforeach; ?>
  </div>

  <div class="op-filters">
    <div class="op-group" role="group" aria-label="Study or career level">
      <span class="op-glabel">Level</span>
      <button type="button" class="op-chip on" data-level="">Any</button>
      <?php foreach (JOB_LEVELS as $k => $label): ?><button type="button" class="op-chip" data-level="<?= $k ?>"><?= e($label) ?></button><?php endforeach; ?>
    </div>
    <div class="op-group" role="group" aria-label="Where">
      <span class="op-glabel">Where</span>
      <button type="button" class="op-chip on" data-scope="">Anywhere</button>
      <?php foreach (JOB_SCOPES as $k => $label): ?><button type="button" class="op-chip" data-scope="<?= $k ?>"><?= e($label) ?></button><?php endforeach; ?>
    </div>
    <label class="op-search"><?= icon('search', 18) ?><input type="search" id="opQ" placeholder="Search by name, country or organisation" value="<?= e($initial['q']) ?>" aria-label="Search opportunities"></label>
  </div>

  <p class="op-count" aria-live="polite"><b id="opN"><?= count($items) ?></b> <span id="opW">opportunities</span> <button type="button" class="op-reset" id="opReset" hidden>Clear filters</button></p>

  <div class="op-grid" id="opGrid">
    <?php foreach ($items as $j): $lv = array_filter(explode(',', (string) $j['levels'])); ?>
      <article class="op-card" data-kind="<?= e($j['kind']) ?>" data-levels="<?= e(implode(' ', $lv)) ?>" data-scope="<?= e($j['scope']) ?>"
        data-text="<?= e(mb_strtolower($j['title'] . ' ' . $j['organisation'] . ' ' . $j['location'] . ' ' . $j['description'])) ?>">
        <div class="op-top"><span class="op-ic"><?= icon(JOB_KIND_ICONS[$j['kind']] ?? 'briefcase', 24) ?></span>
          <div class="op-badges"><span class="badge"><?= e(JOB_KINDS[$j['kind']]) ?></span><span class="badge <?= $j['scope'] === 'local' ? 'warn' : '' ?>"><?= e($j['scope'] === 'local' ? 'Local' : 'International') ?></span><?= $j['member_only'] ? '<span class="badge warn">Members only</span>' : '' ?></div></div>
        <h2><a href="?id=<?= (int) $j['id'] ?>"><?= e($j['title']) ?></a></h2>
        <p class="op-org"><?= e($j['organisation']) ?></p>
        <p class="op-meta"><?= icon('map-pin', 14) ?> <?= e($j['location'] ?: 'Not specified') ?></p>
        <?php if ($lv): ?><p class="op-levels"><?php foreach (job_level_labels($j['levels']) as $l): ?><span><?= e($l) ?></span><?php endforeach; ?></p><?php endif; ?>
        <p class="op-desc"><?= e(excerpt((string) $j['description'], 150)) ?></p>
        <div class="op-actions">
          <a class="btn secondary" href="?id=<?= (int) $j['id'] ?>">Details</a>
          <?php if ($j['apply_url']): ?><a class="btn" href="<?= e($j['apply_url']) ?>" target="_blank" rel="noopener nofollow">Official site &#8599;</a><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <div class="empty-state" id="opEmpty" hidden><?= icon('search', 36) ?><strong>Nothing matches those filters</strong><span>Try another level or place, or clear the filters.</span></div>
  <?php if (!$items): ?><div class="empty-state"><?= icon('briefcase', 36) ?><strong>No opportunities yet</strong><span>Check back soon<?= $member ? '' : ', or log in to see member-only listings' ?>.</span></div><?php endif; ?>
  <p class="meta op-note">Entries link to the official websites. Calls, funding and deadlines change every cycle, so confirm them on the official page and apply only there. GUCO lists these opportunities as a guide and is not the funder or employer.</p>
</section>

<script>
(function () {
  var box = document.getElementById('op'); if (!box) return;
  var cards = [].slice.call(box.querySelectorAll('.op-card')), tabs = [].slice.call(box.querySelectorAll('.op-tab'));
  var lvl = [].slice.call(box.querySelectorAll('[data-level]')), scp = [].slice.call(box.querySelectorAll('[data-scope].op-chip'));
  var q = document.getElementById('opQ'), n = document.getElementById('opN'), w = document.getElementById('opW'), empty = document.getElementById('opEmpty'), reset = document.getElementById('opReset');
  var st = <?= json_encode($initial) ?>;
  function mark(list, attr, val) { list.forEach(function (b) { var on = b.getAttribute(attr) === val; b.classList.toggle('on', on); if (b.hasAttribute('aria-selected')) b.setAttribute('aria-selected', on ? 'true' : 'false'); }); }
  function apply() {
    var text = st.q.toLowerCase().trim(), shown = 0;
    cards.forEach(function (c) {
      var ok = (!st.kind || c.dataset.kind === st.kind) &&
        (!st.level || c.dataset.levels === '' || (' ' + c.dataset.levels + ' ').indexOf(' ' + st.level + ' ') > -1) &&
        (!st.scope || c.dataset.scope === st.scope) &&
        (!text || c.dataset.text.indexOf(text) > -1);
      c.hidden = !ok; if (ok) shown++;
    });
    n.textContent = shown; w.textContent = shown === 1 ? 'opportunity' : 'opportunities';
    empty.hidden = shown !== 0 || !cards.length;
    reset.hidden = !(st.kind || st.level || st.scope || text);
    mark(tabs, 'data-kind', st.kind); mark(lvl, 'data-level', st.level); mark(scp, 'data-scope', st.scope);
    var p = new URLSearchParams(); ['kind', 'level', 'scope', 'q'].forEach(function (k) { if (st[k]) p.set(k, st[k]); });
    history.replaceState(null, '', p.toString() ? '?' + p.toString() : location.pathname);
  }
  tabs.forEach(function (b) { b.addEventListener('click', function () { st.kind = b.getAttribute('data-kind'); apply(); }); });
  lvl.forEach(function (b) { b.addEventListener('click', function () { st.level = b.getAttribute('data-level'); apply(); }); });
  scp.forEach(function (b) { b.addEventListener('click', function () { st.scope = b.getAttribute('data-scope'); apply(); }); });
  q.addEventListener('input', function () { st.q = q.value; apply(); });
  reset.addEventListener('click', function () { st = { kind: '', level: '', scope: '', q: '' }; q.value = ''; apply(); });
  apply();
})();
</script>
<?php related_links('Keep going', 'More for your career and studies', [
    ['member/mentorship.php', 'target', 'Mentorship Hub', 'Find a mentor or guide a student (members)'],
    ['member/directory.php', 'users', 'Alumni Connect', 'Network with fellow alumni (members)'],
    ['donate.php', 'heart', 'Give', 'Support students through GUCO'],
    ['contact.php', 'mail', 'Suggest an opportunity', 'Tell the Secretariat about a call we should list'],
]); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
