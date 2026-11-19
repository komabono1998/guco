<?php
/**
 * Register of existing GUCO members. A member who already holds a GUCO number can look it up,
 * claim the record while registering, and so keep the same number in the portal.
 */
const LEGACY_DESIGNATIONS = ['alumnus' => 'Alumnus', 'staff' => 'Staff', 'alumnus_staff' => 'Alumnus and staff'];
const LEGACY_STATUS = ['unclaimed' => 'Not yet claimed', 'claimed' => 'Claimed (portal account)', 'disputed' => 'Needs the Secretariat', 'withdrawn' => 'Withdrawn'];
const LEGACY_CLAIM_MINUTES = 30;

/** "guco 12", "GUCO-00012" or "12" become "GUCO-00012"; anything else returns null. */
function legacy_norm_no(string $input): ?string
{
    $s = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input));
    if (str_starts_with($s, 'GUCO')) {
        $s = substr($s, 4);
    }
    return preg_match('/^\d{1,6}$/', $s) ? 'GUCO-' . str_pad(ltrim($s, '0') === '' ? '0' : ltrim($s, '0'), 5, '0', STR_PAD_LEFT) : null;
}

function legacy_number(array $row): int
{
    return (int) substr((string) $row['guco_no'], 5);
}

/** True when the typed name shares a whole name (surname or first name) with the register entry. */
function legacy_name_matches(array $row, string $input): bool
{
    $have = array_filter(preg_split('/[\s,.]+/u', mb_strtolower($row['full_name'])));
    foreach (array_filter(preg_split('/[\s,.]+/u', mb_strtolower($input))) as $t) {
        if (mb_strlen($t) >= 2 && in_array($t, $have, true)) {
            return true;
        }
    }
    return false;
}

function legacy_display_name(array $row): string
{
    return trim(($row['title'] ?? '') . ' ' . $row['full_name']);
}

/** The record a visitor has proved and is about to claim in this session, or null. */
function legacy_pending_claim(): ?array
{
    $c = $_SESSION['legacy_claim'] ?? null;
    if (!is_array($c) || time() - (int) ($c['at'] ?? 0) > LEGACY_CLAIM_MINUTES * 60) {
        unset($_SESSION['legacy_claim']);
        return null;
    }
    $row = q("SELECT * FROM legacy_members WHERE id = ? AND status = 'unclaimed' AND claimed_user_id IS NULL", [(int) $c['id']])->fetch();
    if (!$row) {
        unset($_SESSION['legacy_claim']);
        return null;
    }
    return $row;
}

/**
 * Imports a CSV with the columns guco_no, title, full_name, sex, designation, note.
 * Existing people (same number and name) are skipped; numbers shared by several people are marked disputed.
 * Returns [added, skipped, disputed, errors[]].
 */
function legacy_import_csv(string $path, string $source = 'Voters Register 2025'): array
{
    $fh = fopen($path, 'r');
    if (!$fh) {
        return [0, 0, 0, ['The file could not be opened.']];
    }
    $head = fgetcsv($fh);
    if ($head) {
        $head[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $head[0]);
    }
    $col = array_flip(array_map(fn($h) => strtolower(trim((string) $h)), $head ?: []));
    foreach (['guco_no', 'full_name'] as $need) {
        if (!isset($col[$need])) {
            fclose($fh);
            return [0, 0, 0, ["The file needs a \"$need\" column."]];
        }
    }
    $get = fn(array $r, string $k) => isset($col[$k]) ? trim((string) ($r[$col[$k]] ?? '')) : '';
    $add = $skip = 0;
    $errors = [];
    $line = 1;
    while (($r = fgetcsv($fh)) !== false) {
        $line++;
        if (!array_filter($r, fn($v) => trim((string) $v) !== '')) {
            continue;
        }
        $no = legacy_norm_no($get($r, 'guco_no'));
        $name = $get($r, 'full_name');
        $des = strtolower(str_replace([' ', '/', '-'], '_', $get($r, 'designation')));
        $des = isset(LEGACY_DESIGNATIONS[$des]) ? $des : ($des === 'alumni' ? 'alumnus' : 'alumnus');
        $sex = strtoupper(substr($get($r, 'sex'), 0, 1));
        if (!$no || $name === '') {
            $errors[] = "Line $line: a valid GUCO number and a name are required.";
            continue;
        }
        if (q('SELECT 1 FROM legacy_members WHERE guco_no = ? AND full_name = ?', [$no, $name])->fetch()) {
            $skip++;
            continue;
        }
        q('INSERT INTO legacy_members (guco_no, title, full_name, sex, designation, note, source) VALUES (?,?,?,?,?,?,?)',
            [$no, mb_substr($get($r, 'title'), 0, 30), mb_substr($name, 0, 200), in_array($sex, ['F', 'M'], true) ? $sex : null, $des, mb_substr($get($r, 'note'), 0, 300) ?: null, mb_substr($source, 0, 80)]);
        $add++;
    }
    fclose($fh);
    // A number held by more than one person cannot be claimed until the Secretariat decides who owns it.
    $dup = q("UPDATE legacy_members SET status = 'disputed' WHERE status = 'unclaimed' AND guco_no IN (SELECT guco_no FROM (SELECT guco_no FROM legacy_members GROUP BY guco_no HAVING COUNT(*) > 1) d)")->rowCount();
    return [$add, $skip, $dup, $errors];
}
