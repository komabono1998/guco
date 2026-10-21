<?php
$config = require __DIR__ . '/../config.php';
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

const STAFF_ROLES = ['super_admin', 'secretariat', 'communications', 'newsletter_editor', 'chapter_coordinator'];

const ROLE_LABELS = [
    'member' => 'Member',
    'super_admin' => 'Super Administrator',
    'secretariat' => 'GUCO Secretariat',
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
    'members' => ['super_admin', 'secretariat'],
    'news' => ['super_admin', 'secretariat', 'communications', 'newsletter_editor'],
    'publish' => ['super_admin', 'secretariat', 'communications'],
    'events' => ['super_admin', 'secretariat', 'communications'],
    'chapters' => ['super_admin', 'secretariat', 'chapter_coordinator'],
    'chapters_all' => ['super_admin', 'secretariat'],
    'leaders' => ['super_admin', 'secretariat'],
    'programmes' => ['super_admin', 'secretariat', 'communications'],
    'awards' => ['super_admin', 'secretariat'],
    'resources' => ['super_admin', 'secretariat', 'communications'],
    'gallery' => ['super_admin', 'secretariat', 'communications'],
    'messages' => ['super_admin', 'secretariat'],
    'subscribers' => ['super_admin', 'secretariat', 'newsletter_editor'],
    'settings' => ['super_admin', 'secretariat'],
    'users' => ['super_admin'],
    'audit' => ['super_admin'],
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
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
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
        flash('Please log in first.', 'error');
        redirect('login.php');
    }
    return $u;
}

function is_member(): bool
{
    $u = current_user();
    return $u && $u['status'] === 'approved';
}

/** Full member features (directory, member-only documents) need an approved application. */
function require_member(): array
{
    $u = require_login();
    if ($u['status'] !== 'approved') {
        flash('This area opens once the Secretariat approves your membership application.', 'error');
        redirect('portal.php');
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
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $file['size'] > 20 * 1024 * 1024) {
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
    $from = setting('contact_email', 'no-reply@localhost');
    return @mail($to, $subject, $body, "From: GUCO <$from>\r\nContent-Type: text/plain; charset=utf-8");
}

function csv_download(string $filename, array $header, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header);
    foreach ($rows as $r) {
        fputcsv($out, array_values($r));
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
