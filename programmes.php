<?php
$pageTitle = 'Programmes & Impact';
$pageDesc = 'GUCO programme areas: alumni networking, mentorship, university development, community service, student support, awards and partnerships.';
require __DIR__ . '/includes/header.php';
$reports = q('SELECT * FROM programmes WHERE is_public = 1 ORDER BY id DESC')->fetchAll();
$areas = [
    'Alumni Networking and Engagement' => 'Create opportunities for alumni to reconnect, share professional experience, build relationships and participate in the life of Gulu University.',
    'Mentorship and Career Connections' => 'Facilitate opportunities for alumni to share knowledge and professional experience with students and fellow graduates.',
    'University Development and Academic Support' => 'Mobilise alumni expertise, networks and resources in support of the University\'s academic interests, research, teaching and institutional development.',
    'Community Service' => 'Promote alumni participation in community service and initiatives that connect University knowledge and experience with community needs.',
    'Student Support and Scholarships' => 'Where approved and funded, coordinate alumni support for student activities, scholarships, research and faculty or college awards.',
    'Awards and Recognition' => 'Support approved recognition of outstanding achievements and distinguished service by alumni.',
    'Partnerships and Fundraising' => 'Build relationships with local and international partners and mobilise support for approved GUCO and University priorities.',
];
?>
<div class="page-head"><h1>Programmes &amp; Impact</h1><p>Our constitutional objectives in action</p></div>
<p class="lead">GUCO's constitutional objectives provide the foundation for its programme areas. Activities listed under impact reports below have been approved and are active.</p>
<div class="grid">
  <?php foreach ($areas as $t => $d): ?><div class="card accent"><h3><?= e($t) ?></h3><p><?= e($d) ?></p></div><?php endforeach; ?>
</div>

<section class="section">
  <h2>Impact reports</h2>
  <?php foreach ($reports as $p): ?>
    <article class="card" style="margin-bottom:16px">
      <h3><?= e($p['title']) ?></h3>
      <?php if ($p['area']): ?><span class="badge"><?= e($p['area']) ?></span><?php endif; ?>
      <dl>
        <?php foreach (['date_location' => 'Date and location', 'objective' => 'Objective', 'target_group' => 'Target group', 'reach' => 'Number reached', 'partners' => 'Partners and contributors', 'outcomes' => 'Outputs and outcomes', 'next_steps' => 'Next steps'] as $k => $label):
            if ($p[$k]): ?><dt><strong><?= e($label) ?></strong></dt><dd><?= nl2br(e($p[$k])) ?></dd><?php endif; endforeach; ?>
      </dl>
    </article>
  <?php endforeach; if (!$reports): ?><p>Approved programme reports will be published here.</p><?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
