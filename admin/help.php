<?php
require_once __DIR__ . '/../includes/admin.php';
$me = require_login();
if (!is_staff()) {
    http_response_code(403);
    exit('Staff only.');
}
// [section, icon, [[task, link, permission]]]
$guide = [
    ['Members and money', 'id-card', [
        ['Approve a new member', 'members.php?status=pending', 'members'],
        ['Verify a proof of payment', 'payments.php', 'payments'],
        ['Revoke or reinstate a membership card', 'members.php', 'members'],
        ['Change a member\'s category or subscription date', 'members.php', 'members'],
        ['Export the members list', 'members.php', 'members'],
    ]],
    ['News, newsletters and events', 'newspaper', [
        ['Publish a news story (write, share a link, or upload a document)', 'news.php?new=1', 'news'],
        ['Write or upload a newsletter and email it to subscribers', 'newsletters.php?new=1', 'newsletters'],
        ['Add an event with date, venue and flyer, and turn on the countdown', 'events.php?new=1', 'events'],
        ['Send event invitations by email', 'events.php', 'events'],
        ['Create a photo album and upload photos', 'gallery.php?new=1', 'gallery'],
        ['Publish a report or document for the public', 'resources.php?new=1', 'resources'],
    ]],
    ['Change what the website says', 'edit', [
        ['Edit the wording on any page', 'content.php', 'content'],
        ['Add the university anthem or campus places to the home page', 'content.php?page=home', 'content'],
        ['Create a new page and put it in the menu', 'pages.php?new=1', 'pages'],
        ['Rename, reorder or hide menu items', 'menu.php', 'menu'],
        ['Change the logo, colours or home page sections', 'branding.php', 'branding'],
        ['Update contact details, social links and fees', 'settings.php', 'settings'],
    ]],
    ['Chapters, projects and leadership', 'map-pin', [
        ['Set up a chapter and approve its members', 'chapters.php', 'chapters'],
        ['Assign chapter roles such as Chairperson or Secretary', 'chapter_members.php', 'chapters'],
        ['Review a project proposal', 'projects.php?status=submitted', 'projects'],
        ['Verify funds a project lead has reported', 'projects.php', 'projects'],
        ['Add office bearers, photos and profiles', 'leaders.php', 'leaders'],
    ]],
    ['Staff and system', 'shield', [
        ['Create a staff account and choose its role', 'users.php', 'users'],
        ['See who changed what', 'audit.php', 'audit'],
        ['Check whether emails were delivered', 'mail_log.php', 'mail'],
        ['Run membership and finance reports', 'reports.php', 'reports'],
        ['Change your own password', 'account.php', null],
    ]],
];
// what each role can open, from the permission map
$permNames = [
    'members' => 'Members', 'payments' => 'Payments', 'content' => 'Site content', 'pages' => 'Pages', 'menu' => 'Menu', 'news' => 'News', 'publish' => 'Publish news',
    'newsletters' => 'Newsletters', 'events' => 'Events', 'chapters' => 'Chapters', 'chapters_all' => 'All chapters', 'leaders' => 'Leadership', 'programmes' => 'Programmes',
    'awards' => 'Awards', 'resources' => 'Reports & documents', 'gallery' => 'Gallery', 'campus' => 'Campus', 'partners' => 'Partners', 'messages' => 'Messages',
    'subscribers' => 'Subscribers', 'settings' => 'Settings', 'branding' => 'Branding', 'users' => 'Users & roles', 'audit' => 'Audit log', 'mail' => 'Mail log',
    'projects' => 'Projects', 'reports' => 'Reports',
];
admin_header('Help & guide', 'How to do the common jobs, and what each role can do');
?>
<div class="help-grid2">
  <?php foreach ($guide as [$section, $ic, $tasks]):
      $tasks = array_filter($tasks, fn($t) => $t[2] === null || can($t[2]));
      if (!$tasks) continue; ?>
    <section class="wid">
      <h3><span><?= icon($ic, 18) ?> <?= e($section) ?></span></h3>
      <ul><?php foreach ($tasks as [$task, $link]): ?><li><a href="<?= e($link) ?>"><?= e($task) ?></a></li><?php endforeach; ?></ul>
    </section>
  <?php endforeach; ?>
</div>

<div class="dgrid" style="margin-top:18px">
  <section class="wid w6">
    <h3>Writing text on the website</h3>
    <p>In every text box that says <em>Article</em>, <em>Page content</em> or similar you can use:</p>
    <ul>
      <li>A blank line to start a new paragraph.</li>
      <li><code>## Heading</code> for a heading.</li>
      <li><code>- item</code> at the start of lines for a bullet list.</li>
      <li><code>**bold**</code> for bold text.</li>
      <li><code>[link text](https://example.com)</code> or <code>[link text](about.php)</code> for links.</li>
    </ul>
    <p class="meta">Pictures: use the upload buttons. The anthem, newsletter PDF and documents are all added by upload or link.</p>
  </section>
  <section class="wid w6">
    <h3>Good to know</h3>
    <ul>
      <li><strong>Publishing is instant.</strong> A saved change is live straight away unless the item is a draft.</li>
      <li><strong>Dates are East Africa Time.</strong> Countdowns and "upcoming" lists use it.</li>
      <li><strong>Emails</strong> need a mail server. If the Mail log shows "failed", ask your web administrator to set up SMTP.</li>
      <li><strong>Nothing is deleted by accident.</strong> Deleting asks for confirmation, and important actions are written to the Audit log.</li>
      <li><strong>Backups:</strong> export the <code>guco</code> database from phpMyAdmin and copy the <code>uploads</code> and <code>storage</code> folders regularly.</li>
    </ul>
  </section>
</div>

<?php if (can('users')): ?>
<section class="wid" style="margin-top:18px">
  <h3>What each role can do</h3>
  <div class="table-wrap" style="box-shadow:none"><table>
    <tr><th>Role</th><th>Sections they can open</th></tr>
    <?php foreach (ROLE_LABELS as $role => $roleLabel): if ($role === 'member') continue;
        $can = [];
        foreach (PERMS as $perm => $roles) {
            if (in_array($role, $roles, true) && isset($permNames[$perm])) {
                $can[] = $permNames[$perm];
            }
        } ?>
      <tr><td><strong><?= e($roleLabel) ?></strong></td><td><?= e(implode(', ', $can)) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
</section>
<?php endif; ?>
<?php admin_footer(); ?>
