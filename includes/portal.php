<?php
require_once __DIR__ . '/bootstrap.php';

/**
 * Member Portal shell: a separate dashboard (own top bar and sidebar) for the signed-in member.
 * Call portal_header() after require_login(); close with portal_footer().
 */
function portal_header(string $title): void
{
    $me = current_user();
    $p = q('SELECT * FROM member_profiles WHERE user_id = ?', [$me['id']])->fetch() ?: [];
    $card = card_state($p + ['status' => $me['status']]);
    $member = is_member();
    $flash = flash();
    $current = basename($_SERVER['SCRIPT_NAME']);
    $siteName = setting('site_name', 'Gulu University Convocation');
    $brandCss = '';
    foreach (['color_primary' => ['--green', '--green-2'], 'color_accent' => ['--gold']] as $key => $vars) {
        if (preg_match('/^#[0-9a-fA-F]{6}$/', setting($key))) {
            foreach ($vars as $v) {
                $brandCss .= "$v:" . setting($key) . ';';
            }
        }
    }
    // [label, file, icon, needs approved membership]
    $items = [
        ['Dashboard', 'index.php', 'home', false],
        ['My profile', 'profile.php', 'user', false],
        ['Membership card', 'card.php', 'id-card', true],
        ['Payments & renewal', 'payments.php', 'wallet', false],
        ['Events', 'events.php', 'calendar', false],
        ['Chapters', 'chapters.php', 'map-pin', true, ['chapter_join.php']],
        ['Projects', 'projects.php', 'target', true, ['project.php', 'project_form.php']],
        ['Documents', 'documents.php', 'file', true],
        ["Members' directory", 'directory.php', 'users', true],
        ['Support', 'support.php', 'life-buoy', false],
    ];
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> | Member Portal | <?= e(config('short_name')) ?></title>
<link rel="icon" href="<?= e(logo_url()) ?>">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/portal.css') ?>">
<?php if ($brandCss): ?><style>:root{<?= $brandCss ?>}</style><?php endif; ?>
</head>
<body class="portal">
<a class="skip" href="#main">Skip to main content</a>
<header class="portal-top noprint">
  <a class="portal-brand" href="<?= url('member/') ?>"><img src="<?= e(logo_url()) ?>" alt="" width="38" height="38"><span><strong>Member Portal</strong><small><?= e($siteName) ?></small></span></a>
  <button class="portal-toggle" type="button" aria-expanded="false" aria-controls="portalside" onclick="var n=document.getElementById('portalside');var o=n.classList.toggle('open');this.setAttribute('aria-expanded',o)">Menu</button>
  <div class="portal-user">
    <a href="<?= url() ?>"><?= icon('globe', 16) ?> Website</a>
    <?php if (is_staff()): ?><a href="<?= url('admin/') ?>">Admin</a><?php endif; ?>
    <a href="<?= url('logout.php') ?>"><?= icon('log-out', 16) ?> Log out</a>
  </div>
</header>
<div class="portal-layout">
  <aside id="portalside" class="portal-side noprint" aria-label="Portal navigation">
    <div class="me">
      <?= member_avatar($p + ['user_id' => $me['id'], 'full_name' => $me['full_name']], 'avatar') ?>
      <strong><?= e($me['full_name']) ?></strong>
      <?php if (!empty($p['member_no'])): ?><span class="meta"><?= e(member_no_label($p['member_no'])) ?></span><?php endif; ?>
      <span class="badge <?= $card['state'] === 'valid' ? '' : ($card['state'] === 'none' ? 'warn' : 'bad') ?>"><?= $card['state'] === 'none' ? ($me['status'] === 'pending' ? 'Application pending' : ucfirst($me['status'])) : e($card['label']) ?></span>
    </div>
    <nav>
      <?php foreach ($items as $it): [$label, $file, $ic, $locked] = $it; $aliases = $it[4] ?? []; ?>
        <?php if ($locked && !$member): ?>
          <span class="locked" title="Available once your membership is approved"><?= icon($ic, 18) ?><?= e($label) ?><?= icon('lock', 14) ?></span>
        <?php else: ?>
          <a href="<?= e(url('member/' . $file)) ?>"<?= $current === $file || in_array($current, $aliases, true) ? ' aria-current="page"' : '' ?>><?= icon($ic, 18) ?><?= e($label) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
  </aside>
  <main id="main" class="portal-main">
    <?php if ($flash): ?><div class="flash <?= e($flash[1]) ?>" role="status"><?= e($flash[0]) ?></div><?php endif; ?>
    <h1 class="noprint"><?= e($title) ?></h1>
<?php
}

function portal_footer(): void
{
    echo "  </main>\n</div>\n</body>\n</html>\n";
}
