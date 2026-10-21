<?php
$pageTitle = 'Alumni Chapters';
$pageDesc = 'Join a GUCO regional chapter: Northern Uganda, West Nile, Eastern, Central and Western Uganda, or the Diaspora.';
require __DIR__ . '/includes/header.php';
$chapters = q("SELECT c.*, (SELECT COUNT(*) FROM chapter_members m WHERE m.chapter_id = c.id AND m.status = 'approved') AS members
               FROM chapters c WHERE c.is_public = 1 ORDER BY c.sort_order, c.name")->fetchAll();
$leaders = [];
foreach (q("SELECT m.chapter_id, m.role, u.full_name FROM chapter_members m JOIN users u ON u.id = m.user_id WHERE m.status = 'approved' AND m.role <> 'member' ORDER BY FIELD(m.role,'chairperson','vice_chairperson','secretary','treasurer','organising_secretary','committee_member')")->fetchAll() as $l) {
    $leaders[$l['chapter_id']][] = $l;
}
$me = current_user();
?>
<div class="page-head"><h1>Alumni Chapters</h1><p>Stay connected through shared disciplines, regions and interests</p></div>
<div class="lead"><?= cr('chapters.intro') ?></div>

<section class="section">
  <h2>Our regional chapters</h2>
  <div class="grid chapter-grid">
    <?php foreach ($chapters as $c): ?>
      <article class="card chapter-card">
        <?php if ($c['image']): ?><img src="<?= e(url('uploads/' . rawurlencode($c['image']))) ?>" alt="" loading="lazy" style="width:100%;height:150px;object-fit:cover;border-radius:10px"><?php endif; ?>
        <div style="display:flex;gap:10px;align-items:center;margin-top:6px"><span class="ic-circle"><?= icon($c['focus'] === 'Diaspora chapter' ? 'globe' : 'map-pin', 22) ?></span><div>
          <h3 style="margin:0"><?= e($c['name']) ?></h3>
          <?php if ($c['focus']): ?><span class="badge"><?= e($c['focus']) ?></span><?php endif; ?></div></div>
        <p><?= nl2br(e($c['description'])) ?></p>
        <?php if (trim((string) $c['districts']) !== ''): ?><p class="meta"><strong>Covers:</strong> <?= e(preg_replace('/\s+/', ' ', $c['districts'])) ?></p><?php endif; ?>
        <?php if ($c['meeting_info']): ?><p class="meta"><strong>Meetings:</strong> <?= e($c['meeting_info']) ?></p><?php endif; ?>
        <?php if (!empty($leaders[$c['id']])): ?>
          <p class="meta"><strong>Leadership:</strong> <?= e(implode('; ', array_map(fn($l) => $l['full_name'] . ' (' . CHAPTER_ROLES[$l['role']] . ')', $leaders[$c['id']]))) ?></p>
        <?php endif; ?>
        <?php if ($c['contact_name'] || $c['contact_email']): ?>
          <p class="meta">Contact: <?= e($c['contact_name']) ?><?= $c['contact_email'] ? ' &middot; <a href="mailto:' . e($c['contact_email']) . '">' . e($c['contact_email']) . '</a>' : '' ?></p>
        <?php endif; ?>
        <p style="margin-top:auto;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <span class="meta"><?= (int) $c['members'] ?> member<?= (int) $c['members'] === 1 ? '' : 's' ?></span>
          <?php if ($c['accepting_members']): ?>
            <a class="btn" style="margin-left:auto" href="<?= url('member/chapter_join.php?id=' . (int) $c['id']) ?>"><?= $me ? 'Register in this chapter' : 'Log in to register' ?></a>
          <?php else: ?><span class="badge warn" style="margin-left:auto">Not accepting members</span><?php endif; ?>
        </p>
      </article>
    <?php endforeach; if (!$chapters): ?>
      <p>The list of recognised chapters will be published here. Contact the Secretariat for the current list.</p>
    <?php endif; ?>
  </div>
  <p class="meta">Chapter registration is open to approved GUCO members. Not a member yet? <a href="<?= url('register.php') ?>">Join GUCO</a> first.</p>
</section>

<section class="section grid two">
  <div class="card"><h2>What chapters do</h2><ul class="checks"><?php foreach (c_lines('chapters.activities') as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul></div>
  <div class="card"><h2>Chapter leadership</h2><?= cr('chapters.leadership') ?></div>
</section>

<section class="cta">
  <h2 style="margin-top:0">Find or establish a chapter</h2>
  <?= cr('chapters.cta') ?>
  <a class="btn" href="<?= url('contact.php?category=Alumni+Chapters') ?>">ENQUIRE ABOUT ALUMNI CHAPTERS</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
