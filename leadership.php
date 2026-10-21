<?php
$pageTitle = 'Leadership & Governance';
$pageDesc = 'The governance structure of GUCO: General Assembly, Board, Executive Committee, Disciplinary Committee and elections.';
require __DIR__ . '/includes/header.php';
$leaders = q('SELECT * FROM leaders WHERE is_public = 1 ORDER BY sort_order, id')->fetchAll();
$byOrgan = [];
foreach ($leaders as $l) {
    $byOrgan[$l['organ']][] = $l;
}
$people = function (string $organ) use ($byOrgan) {
    if (empty($byOrgan[$organ])) {
        return;
    }
    echo '<div class="people">';
    foreach ($byOrgan[$organ] as $l) {
        echo '<div class="card person">';
        if ($l['photo']) {
            echo '<img src="' . e(url('uploads/' . rawurlencode($l['photo']))) . '" alt="' . e($l['name']) . '">';
        }
        echo '<strong>' . e($l['name']) . '</strong><br><span class="meta">' . e($l['title']) . '</span>';
        if ($l['term']) {
            echo '<br><span class="meta">' . e($l['term']) . '</span>';
        }
        echo '</div>';
    }
    echo '</div>';
};
?>
<div class="page-head"><h1>Leadership &amp; Governance</h1><p>How GUCO is governed</p></div>

<section class="section">
  <h2>Governance structure</h2>
  <p>Article 10 identifies four leadership organs: the General Assembly, the Board, the Executive Committee and the Disciplinary Committee.</p>
</section>

<section id="assembly" class="section">
  <h2>General Assembly</h2>
  <p>The General Assembly is the supreme organ of GUCO. Its responsibilities include considering annual and financial reports, approving development plans and budgets, electing Executive Committee members, addressing constitutional matters and carrying out other functions that support the Association's progress. The Constitution identifies the Chancellor of Gulu University as Patron of the Association.</p>
  <?php $people('patron'); ?>
</section>

<section id="board" class="section">
  <h2>The Board</h2>
  <p>The Board provides advisory services to the Executive Committee. Article 12 describes its composition as an elected prominent Gulu University alumnus as Board Chairperson, the Vice Chancellor and four members nominated by and approved by the General Assembly, two of whom shall be from outside Gulu University.</p>
  <?php $people('board'); ?>
</section>

<section id="executive" class="section">
  <h2>Executive Committee</h2>
  <p>Article 14 specifies nine officials:</p>
  <ul class="checks">
    <li>Chairperson</li><li>Vice Chairperson</li>
    <li>General Secretary, who the Constitution identifies as the Academic Registrar of Gulu University, without voting or standing for election</li>
    <li>Treasurer</li><li>Publicity Secretary</li><li>Female Representative</li><li>Staff Representative</li><li>Newsletter Editor</li>
    <li>One other member elected at the General Assembly</li>
  </ul>
  <p>The Constitution states that the Chairperson and Vice Chairperson shall be of opposite gender. The Executive Committee's term is four years, with eligibility for re-election for one further term in the same office.</p>
  <?php $people('executive'); ?>
  <?php if (empty($byOrgan['executive']) && empty($byOrgan['board'])): ?>
    <p class="notice">Names of current office bearers will be published after formal confirmation by the Secretariat.</p>
  <?php endif; ?>
  <h3>Executive responsibilities</h3>
  <div class="grid">
    <?php foreach ([
        'Chairperson' => 'General supervision, representation, committee coordination, policy implementation, strategic planning, fundraising and alumni outreach.',
        'Vice Chairperson' => 'Performs the Chairperson\'s duties in their absence and other assigned duties.',
        'General Secretary' => 'Meeting notices and agendas, minutes, attendance records, correspondence and Association records.',
        'Treasurer' => 'Funds, accounts, membership dues, budgets and quarterly and annual financial reporting.',
        'Publicity Secretary' => 'Member mobilisation, information dissemination, social media and website management, and liaison with other university associations.',
        'Female Representative' => 'Encourages participation by female alumni and staff.',
        'Staff Representative' => 'Represents University staff and supports staff mobilisation.',
        'Newsletter Editor' => 'Provides timely information and publicises Association activities.',
    ] as $role => $duty): ?>
      <div class="card"><strong><?= e($role) ?></strong><p><?= e($duty) ?></p></div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section">
  <h2>Disciplinary Committee</h2>
  <p>The Disciplinary Committee is responsible for the general disciplinary conduct of members. The Constitution provides for a five-member committee elected from active members at the AGM, with disciplinary measures subject to the procedures and approvals described in the Constitution.</p>
  <?php $people('disciplinary'); ?>
</section>

<section id="elections" class="section">
  <h2>Elections and accountability</h2>
  <p>The Constitution provides for elections at the General Assembly, secret-ballot voting by eligible fully paid-up members physically present, a four-year term for Executive Committee office bearers and an elections appeals process. The website publishes only officially approved election notices, candidate information, results and appeal guidance.</p>
  <p><a href="<?= url('news.php?category=Notices') ?>">View official notices &rarr;</a></p>
</section>

<p class="notice"><strong>Website governance notice:</strong> The website summarises selected provisions for public information. The Constitution remains the authoritative source. Where a summary and the Constitution differ, the Constitution and applicable University rules govern.</p>
<?php require __DIR__ . '/includes/footer.php'; ?>
