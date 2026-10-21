<?php
require_once __DIR__ . '/../includes/portal.php';
$me = require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave'])) {
    csrf_check();
    $row = q('SELECT * FROM chapter_members WHERE id = ? AND user_id = ?', [(int) $_POST['leave'], $me['id']])->fetch();
    if ($row && in_array($row['status'], ['pending', 'approved'], true)) {
        q("UPDATE chapter_members SET status = 'left', role = 'member', decided_at = NOW() WHERE id = ?", [$row['id']]);
        chapter_refresh_primary((int) $me['id']);
        flash($row['status'] === 'pending' ? 'Registration withdrawn.' : 'You have left the chapter.');
    }
    redirect('member/chapters.php');
}
$mine = q('SELECT m.*, c.name, c.focus, c.contact_name, c.contact_email, c.meeting_info FROM chapter_members m JOIN chapters c ON c.id = m.chapter_id WHERE m.user_id = ? AND m.status <> "left" ORDER BY m.created_at DESC', [$me['id']])->fetchAll();
$joinedIds = array_column($mine, 'chapter_id');
$others = q('SELECT c.*, (SELECT COUNT(*) FROM chapter_members m WHERE m.chapter_id = c.id AND m.status = "approved") AS members FROM chapters c WHERE c.is_public = 1 AND c.accepting_members = 1 ORDER BY c.sort_order, c.name')->fetchAll();
$others = array_filter($others, fn($c) => !in_array($c['id'], $joinedIds));
$badge = ['pending' => ['Awaiting approval', 'warn'], 'approved' => ['Approved', ''], 'rejected' => ['Not approved', 'bad']];
portal_header('My chapters');
?>
<section class="panel">
  <h2>My chapter registrations</h2>
  <?php foreach ($mine as $m): [$bl, $bc] = $badge[$m['status']]; ?>
    <div class="ev-mini" style="align-items:flex-start">
      <div style="flex:1">
        <strong><?= e($m['name']) ?></strong> <span class="badge <?= $bc ?>"><?= e($bl) ?></span>
        <?= $m['role'] !== 'member' ? '<span class="badge">' . e(CHAPTER_ROLES[$m['role']]) . '</span>' : '' ?><br>
        <span class="meta"><?= e(trim($m['district'] . ($m['country'] ? ', ' . $m['country'] : ''), ', ')) ?> &middot; registered <?= e(fmt_date($m['created_at'])) ?></span>
        <?php if ($m['status'] === 'approved'): ?>
          <br><span class="meta"><?= $m['meeting_info'] ? e($m['meeting_info']) . ' &middot; ' : '' ?>Contact: <?= e($m['contact_name'] ?: 'the Secretariat') ?><?= $m['contact_email'] ? ' (' . e($m['contact_email']) . ')' : '' ?></span>
        <?php elseif ($m['status'] === 'rejected' && $m['decision_note']): ?><br><span class="meta">Note: <?= e($m['decision_note']) ?></span><?php endif; ?>
      </div>
      <?php if (in_array($m['status'], ['pending', 'approved'], true)): ?>
        <form method="post"><?= csrf_field() ?><button class="secondary" name="leave" value="<?= (int) $m['id'] ?>" onclick="return confirm('<?= $m['status'] === 'pending' ? 'Withdraw this registration?' : 'Leave this chapter?' ?>')"><?= $m['status'] === 'pending' ? 'Withdraw' : 'Leave' ?></button></form>
      <?php endif; ?>
    </div>
  <?php endforeach; if (!$mine): ?><p class="meta">You have not registered in a chapter yet.</p><?php endif; ?>
</section>

<section class="panel">
  <h2>Chapters you can join</h2>
  <?php if (!is_member()): ?><p class="notice">Chapter registration opens once your membership is approved.</p><?php endif; ?>
  <div class="grid">
    <?php foreach ($others as $c): ?>
      <div class="card">
        <h3 style="margin-top:0"><?= e($c['name']) ?></h3>
        <p class="meta"><?= e(excerpt((string) $c['description'], 130)) ?></p>
        <p class="meta"><?= (int) $c['members'] ?> member<?= (int) $c['members'] === 1 ? '' : 's' ?></p>
        <?php if (is_member()): ?><a class="btn" href="chapter_join.php?id=<?= (int) $c['id'] ?>">Register</a><?php endif; ?>
      </div>
    <?php endforeach; if (!$others): ?><p class="meta">No other chapters are open for registration.</p><?php endif; ?>
  </div>
</section>
<?php portal_footer(); ?>
