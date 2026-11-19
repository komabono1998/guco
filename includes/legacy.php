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

/** Lower-case letters-only name parts, so "OJOK Onono James" and "Ojok James Onono" compare equal. */
function legacy_name_tokens(string $name): array
{
    $t = array_filter(preg_split('/[^\p{L}]+/u', mb_strtolower($name)), fn($x) => $x !== '');
    return array_values(array_unique($t));
}

/**
 * Decides whether two register entries are the same person. With the same GUCO number a shared name part is enough
 * (spelling differs between lists); with different numbers every name part of the shorter name must match.
 */
function legacy_same_person(string $a, string $b, bool $sameNumber): bool
{
    $ta = legacy_name_tokens($a);
    $tb = legacy_name_tokens($b);
    // "Dropee" is "Dr. Opee" typed together.
    $fix = function (array $x, array $other) {
        return array_map(fn($t) => (strlen($t) > 5 && str_starts_with($t, 'dr') && in_array(substr($t, 2), $other, true)) ? substr($t, 2) : $t, $x);
    };
    $ta = $fix($ta, $tb);
    $tb = $fix($tb, $ta);
    $eq = fn($x, $y) => $x === $y || (strlen($x) >= 5 && strlen($y) >= 5 && levenshtein($x, $y) <= 1);
    $matched = 0;
    foreach ($ta as $x) {
        foreach ($tb as $y) {
            if ($eq($x, $y)) {
                $matched++;
                break;
            }
        }
    }
    if ($sameNumber) {
        return $matched >= 1;
    }
    return $matched >= 2 && $matched === min(count($ta), count($tb));
}

/**
 * Imports a CSV with the columns guco_no, title, full_name, sex, designation, note and, optionally, course, faculty,
 * faculty_suggested, graduation_series, student_reg_no, phone and subscribed_year.
 * People already on the register are not added again: details from the file update their record (the same person under a
 * different number also gets a note). A number held by more than one person is marked as needing the Secretariat.
 * Returns [added, skipped, disputed, errors[], matchedWithOtherNumber, updated].
 */
function legacy_import_csv(string $path, string $source = 'Voters Register 2025'): array
{
    $fh = fopen($path, 'r');
    if (!$fh) {
        return [0, 0, 0, ['The file could not be opened.'], 0, 0];
    }
    $head = fgetcsv($fh);
    if ($head) {
        $head[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $head[0]);
    }
    $col = array_flip(array_map(fn($h) => strtolower(trim((string) $h)), $head ?: []));
    foreach (['guco_no', 'full_name'] as $need) {
        if (!isset($col[$need])) {
            fclose($fh);
            return [0, 0, 0, ["The file needs a \"$need\" column."], 0, 0];
        }
    }
    $get = fn(array $r, string $k) => isset($col[$k]) ? trim((string) ($r[$col[$k]] ?? '')) : '';
    $existing = q('SELECT id, guco_no, full_name, note FROM legacy_members')->fetchAll();
    $add = $skip = $other = $upd = 0;
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
        $des = isset(LEGACY_DESIGNATIONS[$des]) ? $des : 'alumnus';
        $sex = strtoupper(substr($get($r, 'sex'), 0, 1));
        $series = (int) $get($r, 'graduation_series') ?: null;
        $year = (int) $get($r, 'subscribed_year') ?: null;
        $extra = [
            'course' => mb_substr($get($r, 'course'), 0, 200) ?: null,
            'faculty' => mb_substr($get($r, 'faculty'), 0, 150) ?: null,
            'faculty_suggested' => $get($r, 'faculty') !== '' && $get($r, 'faculty_suggested') !== '0' ? 1 : 0,
            'graduation_series' => $series,
            'student_reg_no' => mb_substr($get($r, 'student_reg_no'), 0, 60) ?: null,
            'phone' => mb_substr($get($r, 'phone'), 0, 40) ?: null,
            'subscribed_year' => $year,
        ];
        if (!$no || $name === '') {
            $errors[] = "Line $line: a valid GUCO number and a name are required.";
            continue;
        }
        $same = null;
        $differentNumber = false;
        foreach ($existing as $e) {
            if (legacy_same_person($name, $e['full_name'], $e['guco_no'] === $no)) {
                $same = $e;
                $differentNumber = $e['guco_no'] !== $no;
                if (!$differentNumber) {
                    break;
                }
            }
        }
        if ($same) {
            if ($differentNumber) {
                $msg = "$source lists this person as $no";
                if (!str_contains((string) $same['note'], $msg)) {
                    q('UPDATE legacy_members SET note = ? WHERE id = ?', [mb_substr(trim(($same['note'] ? $same['note'] . '; ' : '') . $msg), 0, 300), $same['id']]);
                }
                $other++;
            } else {
                $skip++;
            }
            // Newer details from the file: fill gaps, and take the latest subscription year, course and designation.
            $changed = q('UPDATE legacy_members SET
                    course = COALESCE(?, course),
                    faculty_suggested = CASE WHEN ? IS NOT NULL AND (faculty IS NULL OR faculty_suggested = 1) THEN ? ELSE faculty_suggested END,
                    faculty = CASE WHEN ? IS NOT NULL AND (faculty IS NULL OR faculty_suggested = 1) THEN ? ELSE faculty END,
                    graduation_series = COALESCE(?, graduation_series),
                    student_reg_no = COALESCE(?, student_reg_no),
                    phone = COALESCE(?, phone),
                    subscribed_year = GREATEST(COALESCE(?, 0), COALESCE(subscribed_year, 0)),
                    designation = ?
                WHERE id = ?', [
                $extra['course'], $extra['faculty'], $extra['faculty_suggested'], $extra['faculty'], $extra['faculty'], $series, $extra['student_reg_no'],
                $extra['phone'], $year, $des, $same['id'],
            ])->rowCount();
            $upd += $changed ? 1 : 0;
            continue;
        }
        q('INSERT INTO legacy_members (guco_no, title, full_name, sex, designation, note, source, course, faculty, faculty_suggested, graduation_series, student_reg_no, phone, subscribed_year) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$no, mb_substr($get($r, 'title'), 0, 30), mb_substr($name, 0, 200), in_array($sex, ['F', 'M'], true) ? $sex : null, $des, mb_substr($get($r, 'note'), 0, 300) ?: null, mb_substr($source, 0, 80),
                $extra['course'], $extra['faculty'], $extra['faculty_suggested'], $series, $extra['student_reg_no'], $extra['phone'], $year]);
        $existing[] = ['id' => (int) db()->lastInsertId(), 'guco_no' => $no, 'full_name' => $name, 'note' => $get($r, 'note')];
        $add++;
    }
    fclose($fh);
    // A number held by more than one person cannot be claimed until the Secretariat decides who owns it.
    $dup = q("UPDATE legacy_members SET status = 'disputed',
            note = CASE WHEN note IS NULL OR note = '' THEN 'Number shared by more than one person across the registers'
                        WHEN note LIKE '%shared by more than one person%' THEN note
                        ELSE LEFT(CONCAT(note, '; Number shared by more than one person across the registers'), 300) END
        WHERE status = 'unclaimed' AND guco_no IN (SELECT guco_no FROM (SELECT guco_no FROM legacy_members GROUP BY guco_no HAVING COUNT(*) > 1) d)")->rowCount();
    return [$add, $skip, $dup, $errors, $other, $upd];
}
