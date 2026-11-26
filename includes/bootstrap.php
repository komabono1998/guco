<?php
$config = require __DIR__ . '/../config.php';
// --- Transport and session hardening ---------------------------------------------------------
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('display_errors', !empty($config['dev_mode']) ? '1' : '0');
ini_set('log_errors', '1');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $isHttps]);
session_start();
date_default_timezone_set('Africa/Kampala');

// Sign out sessions that have been idle for two hours.
if (!empty($_SESSION['uid']) && time() - ($_SESSION['last_seen'] ?? time()) > 7200) {
    $_SESSION = [];
    session_regenerate_id(true);
}
$_SESSION['last_seen'] = time();

if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(self), payment=()');
    header("Content-Security-Policy: frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'");
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=15552000');
    }
    if (preg_match('#/(admin|member)/#', $_SERVER['SCRIPT_NAME'] ?? '') || in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), ['login.php', 'register.php', 'forgot.php', 'reset.php'], true)) {
        header('Cache-Control: no-store');
    }
}

// Friendly error page instead of leaking stack traces and database details.
set_exception_handler(function (Throwable $ex) {
    error_log('GUCO: ' . $ex);
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (!empty(config('dev_mode'))) {
        echo '<pre>' . htmlspecialchars((string) $ex) . '</pre>';
        return;
    }
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Something went wrong</title>'
        . '<body style="font-family:system-ui,sans-serif;max-width:520px;margin:12vh auto;padding:0 20px;color:#16251c">'
        . '<h1>Something went wrong</h1><p>We could not complete your request. Please try again in a moment, or contact the Secretariat if it keeps happening.</p>'
        . '<p><a href="' . htmlspecialchars(rtrim(config('base_url'), '/') . '/', ENT_QUOTES) . '">Back to the website</a></p></body>';
});

/** Public origin used in emailed links: config 'public_url' wins; otherwise a validated Host header. */
function site_origin(): string
{
    $fixed = (string) config('public_url');
    if ($fixed !== '') {
        return rtrim($fixed, '/');
    }
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (!preg_match('/^[A-Za-z0-9.\-]+(:\d{1,5})?$/', $host)) {
        $host = 'localhost';
    }
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $host;
}

const STAFF_ROLES = ['super_admin', 'system_admin', 'secretariat', 'executive', 'membership_officer', 'finance_officer', 'communications', 'newsletter_editor', 'events_officer', 'career_officer', 'chapter_coordinator'];

const ROLE_LABELS = [
    'member' => 'Member',
    'super_admin' => 'Super Administrator',
    'system_admin' => 'System Administrator',
    'secretariat' => 'GUCO Secretariat',
    'executive' => 'Convocation Executive',
    'membership_officer' => 'Membership Officer',
    'finance_officer' => 'Finance Officer',
    'events_officer' => 'Events Officer',
    'career_officer' => 'Career Officer',
    'communications' => 'Publicity Secretary / Communications Editor',
    'newsletter_editor' => 'Newsletter Editor',
    'chapter_coordinator' => 'Chapter Coordinator',
];

const CATEGORY_LABELS = [
    'founder' => 'Founder Member',
    'ordinary' => 'Ordinary / Full Member',
    'honorary' => 'Honorary Member',
    'life' => 'Life Member',
];

const NEWS_CATEGORIES = [
    'GUCO Announcements', 'Alumni Achievements', 'University and Research', 'Chapter Updates',
    'Community Service', 'Awards and Recognition', 'Partnerships', 'Newsletter', 'Notices',
];

const PERMS = [
    'members' => ['super_admin', 'secretariat', 'membership_officer'],
    'payments' => ['super_admin', 'secretariat', 'finance_officer'],
    'donations' => ['super_admin', 'secretariat', 'finance_officer'],
    'careers' => ['super_admin', 'secretariat', 'career_officer'],
    'halloffame' => ['super_admin', 'secretariat', 'executive', 'communications'],
    'mentorship' => ['super_admin', 'secretariat', 'career_officer', 'membership_officer'],
    'checkin' => ['super_admin', 'secretariat', 'events_officer', 'communications'],
    'backup' => ['super_admin', 'system_admin'],
    'analytics' => ['super_admin', 'system_admin', 'secretariat', 'executive', 'communications'],
    'content' => ['super_admin', 'secretariat', 'communications'],
    'pages' => ['super_admin', 'secretariat', 'communications'],
    'menu' => ['super_admin', 'secretariat'],
    'partners' => ['super_admin', 'secretariat', 'communications'],
    'projects' => ['super_admin', 'secretariat', 'executive'],
    'newsletters' => ['super_admin', 'secretariat', 'communications', 'newsletter_editor'],
    'campus' => ['super_admin', 'secretariat', 'communications'],
    'mail' => ['super_admin', 'secretariat', 'system_admin'],
    'reports' => ['super_admin', 'secretariat', 'executive', 'membership_officer', 'finance_officer'],
    'branding' => ['super_admin', 'secretariat'],
    'news' => ['super_admin', 'secretariat', 'communications', 'newsletter_editor'],
    'publish' => ['super_admin', 'secretariat', 'communications'],
    'events' => ['super_admin', 'secretariat', 'communications', 'events_officer'],
    'chapters' => ['super_admin', 'secretariat', 'chapter_coordinator'],
    'chapters_all' => ['super_admin', 'secretariat'],
    'leaders' => ['super_admin', 'secretariat'],
    'programmes' => ['super_admin', 'secretariat', 'communications'],
    'awards' => ['super_admin', 'secretariat'],
    'resources' => ['super_admin', 'secretariat', 'communications'],
    'gallery' => ['super_admin', 'secretariat', 'communications'],
    'messages' => ['super_admin', 'secretariat'],
    'subscribers' => ['super_admin', 'secretariat', 'newsletter_editor'],
    'settings' => ['super_admin', 'secretariat', 'system_admin'],
    'users' => ['super_admin', 'system_admin'],
    'audit' => ['super_admin', 'system_admin'],
];

function config(string $key = null)
{
    global $config;
    return $key === null ? $config : ($config[$key] ?? null);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config('db');
        $pdo = new PDO(
            "mysql:host={$c['host']};dbname={$c['name']};charset=utf8mb4",
            $c['user'],
            $c['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+03:00'",
            ]
        );
    }
    return $pdo;
}

function q(string $sql, array $args = []): PDOStatement
{
    $s = db()->prepare($sql);
    $s->execute($args);
    return $s;
}

/** Logo URL: the uploaded logo from Branding settings, or the bundled default. */
function logo_url(): string
{
    $l = setting('logo');
    return $l !== '' ? url('uploads/' . rawurlencode($l)) : asset('assets/img/logo-sm.webp');
}

function setting(string $key, string $default = ''): string
{
    static $all = null;
    if ($all === null) {
        $all = db()->query('SELECT k, v FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    $v = $all[$key] ?? '';
    return $v !== '' ? $v : $default;
}

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/** URL of a static file with its modification time, so browsers load updated CSS and JS at once. */
function asset(string $path): string
{
    $file = __DIR__ . '/../' . ltrim($path, '/');
    return url($path) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

function url(string $path = ''): string
{
    return rtrim(config('base_url'), '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function flash(?string $msg = null, string $type = 'success')
{
    if ($msg !== null) {
        $_SESSION['flash'] = [$msg, $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf_field(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || $sent === '' || !hash_equals((string) ($_SESSION['csrf'] ?? ''), $sent)) {
        http_response_code(403);
        exit('Invalid or expired form. Go back and try again.');
    }
}

/** Hidden fields for spam protection: honeypot plus a minimum fill time. */
function spam_fields(): string
{
    $_SESSION['form_started'] = time();
    return '<div style="position:absolute;left:-9999px" aria-hidden="true"><label>Leave this empty'
        . '<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>';
}

function is_spam(): bool
{
    return !empty($_POST['website']) || time() - ($_SESSION['form_started'] ?? 0) < 3;
}

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $user = q('SELECT * FROM users WHERE id = ? AND status IN ("pending","approved")', [$_SESSION['uid']])->fetch() ?: null;
        }
    }
    return $user;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $area = $_SERVER['SCRIPT_NAME'] ?? '';
        flash('Please log in to continue.', 'error');
        redirect(str_contains($area, '/admin/') ? 'admin/login.php' : 'member/login.php');
    }
    return $u;
}

function is_member(): bool
{
    $u = current_user();
    if (!$u || $u['status'] !== 'approved') {
        return false;
    }
    // A revoked membership card removes member-only access.
    return q('SELECT card_status FROM member_profiles WHERE user_id = ?', [$u['id']])->fetchColumn() !== 'revoked';
}

/** Full member features (directory, member-only documents) need an approved application. */
function require_member(): array
{
    $u = require_login();
    if (!is_member()) {
        flash('This area is open to members with an approved application and a valid membership card.', 'error');
        redirect('member/');
    }
    return $u;
}

function can(string $perm): bool
{
    $u = current_user();
    return $u && $u['status'] === 'approved' && in_array($u['role'], PERMS[$perm] ?? [], true);
}

function require_perm(string $perm): array
{
    $u = require_login();
    if (!can($perm)) {
        http_response_code(403);
        exit('You do not have access to this section.');
    }
    return $u;
}

function is_staff(): bool
{
    $u = current_user();
    return $u && $u['status'] === 'approved' && in_array($u['role'], STAFF_ROLES, true);
}

function audit(string $action, string $detail = ''): void
{
    $u = current_user();
    q('INSERT INTO audit_log (user_id, action, detail, ip) VALUES (?,?,?,?)',
        [$u['id'] ?? null, $action, mb_substr($detail, 0, 400), $_SERVER['REMOTE_ADDR'] ?? null]);
}

function slugify(string $s): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
    return $s !== '' ? $s : 'post';
}

function excerpt(string $s, int $len = 160): string
{
    return mb_strimwidth(trim(preg_replace('/\s+/', ' ', strip_tags($s))), 0, $len, '…');
}

function fmt_date(?string $d, string $f = 'j M Y'): string
{
    return $d ? date($f, strtotime($d)) : '';
}

function fmt_size(?int $b): string
{
    if (!$b) {
        return '';
    }
    return $b >= 1048576 ? round($b / 1048576, 1) . ' MB' : max(1, round($b / 1024)) . ' KB';
}

function save_upload(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($ext[$mime]) || $file['size'] > 5 * 1024 * 1024) {
        return null;
    }
    $dir = config('upload_dir');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = bin2hex(random_bytes(8)) . '.' . $ext[$mime];
    return move_uploaded_file($file['tmp_name'], "$dir/$name") ? $name : null;
}

/** Documents are stored outside direct web access and served through download.php. */
function save_document(array $file): ?array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $file["size"] > 40 * 1024 * 1024) {
        return null;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true)) {
        return null;
    }
    $dir = config('storage_dir');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
        return null;
    }
    return ['filename' => $name, 'original_name' => basename($file['name']), 'file_size' => (int) $file['size']];
}

function send_mail(string $to, string $subject, string $body): bool
{
    return deliver_mail($to, $subject, $body, ['context' => 'system']);
}

function csv_download(string $filename, array $header, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header);
    // Neutralise spreadsheet formulas in exported text (CSV injection).
    $safe = fn($v) => is_string($v) && $v !== '' && strpbrk($v[0], "=+-@	
") !== false && !is_numeric($v) ? "'" . $v : $v;
    foreach ($rows as $r) {
        fputcsv($out, array_map($safe, array_values($r)));
    }
    exit;
}

function pagination(int $total, int $per, int $page, string $baseQuery = ''): string
{
    $pages = (int) ceil($total / $per);
    if ($pages < 2) {
        return '';
    }
    $html = '<nav class="pager" aria-label="Pagination">';
    for ($i = 1; $i <= $pages; $i++) {
        $html .= $i === $page
            ? '<span aria-current="page">' . $i . '</span>'
            : '<a href="?' . e($baseQuery . ($baseQuery ? '&' : '') . 'page=' . $i) . '">' . $i . '</a>';
    }
    return $html . '</nav>';
}

require_once __DIR__ . '/events.php';
require_once __DIR__ . '/membership.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/projects.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/photos.php';
require_once __DIR__ . '/donations.php';
require_once __DIR__ . '/legacy.php';
require_once __DIR__ . '/dedupe.php';
require_once __DIR__ . '/member_report.php';
require_once __DIR__ . '/network.php';
require_once __DIR__ . '/related.php';
require_once __DIR__ . '/portal_mail.php';
require_once __DIR__ . '/twofactor.php';
require_once __DIR__ . '/analytics.php';
require_once __DIR__ . '/maintenance.php';
maintenance_gate();
