<?php
/** Shared two-column layout for login, password reset and similar pages. */
function auth_open(string $title, string $sub = ''): void
{
    ?>
<div class="auth">
  <aside class="auth-side">
    <img src="<?= e(logo_url()) ?>" alt="" width="84" height="84">
    <h2><?= e(setting('site_name', 'Gulu University Convocation')) ?></h2>
    <p><?= e(setting('site_tagline', 'Community Transformation Actualized')) ?></p>
    <ul>
      <li><?= icon('id-card', 20) ?> Your membership card and ID</li>
      <li><?= icon('map-pin', 20) ?> Your chapter and its activities</li>
      <li><?= icon('calendar', 20) ?> Events, registration and payments</li>
      <li><?= icon('target', 20) ?> Propose and lead community projects</li>
    </ul>
  </aside>
  <div class="auth-main">
    <h1><?= e($title) ?></h1>
    <?php if ($sub): ?><p class="sub"><?= e($sub) ?></p><?php endif; ?>
<?php
}

function auth_close(): void
{
    echo "  </div>\n</div>\n";
}

/**
 * Checks an email and password with a simple per-session throttle.
 * Returns [user row or null, error message or null].
 */
function attempt_login(string $email, string $password): array
{
    $email = strtolower(trim($email));
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
    // Throttle by address and by account, so neither a cleared session nor many addresses defeat it.
    q('DELETE FROM login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    $byIp = (int) q('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)', [$ip])->fetchColumn();
    $byMail = (int) q('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)', [$email])->fetchColumn();
    if ($byIp >= 15 || $byMail >= 6) {
        return [null, 'Too many failed attempts. Please wait 15 minutes and try again.'];
    }
    $u = q('SELECT * FROM users WHERE email = ?', [$email])->fetch();
    if (!$u || !password_verify($password, $u['password_hash'])) {
        q('INSERT INTO login_attempts (ip, email) VALUES (?,?)', [$ip, mb_substr($email, 0, 190)]);
        return [null, 'Wrong email or password.'];
    }
    if ($u['status'] === 'rejected') {
        return [null, 'Your application was not approved. Please contact the Secretariat.'];
    }
    session_regenerate_id(true);
    $_SESSION['uid'] = $u['id'];
    q('DELETE FROM login_attempts WHERE email = ?', [$email]);
    return [$u, null];
}

/**
 * Opens a standalone sign-in page with no website header or footer.
 * $variant: 'admin' (dark console sign-in) or 'member' (portal sign-in with feature showcase).
 */
function auth_page_open(string $variant, string $title): void
{
    $siteName = setting('site_name', 'Gulu University Convocation');
    $flash = flash();
    $brandCss = '';
    foreach (['color_primary' => ['--green', '--green-2'], 'color_accent' => ['--gold']] as $key => $vars) {
        if (preg_match('/^#[0-9a-fA-F]{6}$/', setting($key))) {
            foreach ($vars as $v) {
                $brandCss .= "$v:" . setting($key) . ';';
            }
        }
    }
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> | <?= e(config('short_name')) ?></title>
<link rel="icon" href="<?= e(logo_url()) ?>">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/auth.css') ?>">
<?php if ($brandCss): ?><style>:root{<?= $brandCss ?>}</style><?php endif; ?>
</head>
<body class="authpage <?= e($variant) ?>">
<?php if ($flash): ?><div class="auth-flash <?= e($flash[1]) ?>" role="status"><?= e($flash[0]) ?></div><?php endif; ?>
<?php
}

function auth_page_close(): void
{
    echo "</body>\n</html>\n";
}
