<?php
/**
 * Members database: one combined list of everyone on the register of existing members and every portal member,
 * with filters and exports (CSV, Excel, Word and PDF). Written without extra libraries.
 */
const REPORT_COLUMNS = [
    'guco_no' => 'GUCO No.', 'name' => 'Name', 'sex' => 'Sex', 'type' => 'Alumni / staff', 'course' => 'Course', 'faculty' => 'Faculty',
    'qualification' => 'Qualification level', 'grad_year' => 'Graduation year', 'grad_series' => 'Graduation series', 'student_reg_no' => 'Student reg. no.',
    'category' => 'Membership category', 'subscription' => 'Subscription', 'subscribed_year' => 'Subscribed year', 'paid_until' => 'Paid until',
    'status' => 'Membership status', 'membership_id' => 'Membership ID', 'portal' => 'Portal account', 'email' => 'Email', 'phone' => 'Phone',
    'chapter' => 'Chapter', 'employment' => 'Employment', 'employer' => 'Employer', 'profession' => 'Profession / position', 'country' => 'Country', 'source' => 'Source',
];
const REPORT_DEFAULT_COLUMNS = ['guco_no', 'name', 'sex', 'type', 'course', 'faculty', 'grad_series', 'category', 'subscription', 'status'];
const REPORT_TYPES = ['alumnus' => 'Alumnus', 'staff' => 'Staff', 'alumnus_staff' => 'Alumnus and staff'];

/** Every person on the register and every portal member, in one normalised list. */
function member_report_all(): array
{
    $rows = [];
    $typeOf = ['alumni' => 'alumnus', 'staff' => 'staff'];
    $sql = "SELECT l.guco_no, l.title, l.full_name AS lname, l.sex AS lsex, l.designation, l.course AS lcourse, l.faculty AS lfaculty, l.faculty_suggested, l.graduation_series AS lseries,
            l.student_reg_no AS lreg, l.phone AS lphone, l.subscribed_year, l.status AS lstatus, l.source,
            u.id AS uid, u.full_name AS uname, u.email, u.status AS ustatus,
            p.member_type, p.category, p.gender, p.college, p.programme, p.qualification_level, p.graduation_year, p.graduation_series AS pseries, p.student_reg_no AS preg,
            p.phone AS pphone, p.subscription_status, p.paid_until, p.membership_id, p.employment_status, p.employer, p.profession, p.country, c.name AS chapter, p.legacy_guco_no
        FROM legacy_members l LEFT JOIN users u ON u.id = l.claimed_user_id LEFT JOIN member_profiles p ON p.user_id = u.id LEFT JOIN chapters c ON c.id = p.chapter_id";
    foreach (q($sql)->fetchAll() as $r) {
        $rows[] = member_report_row($r, true);
    }
    $sql2 = "SELECT NULL AS guco_no, '' AS title, NULL AS lname, NULL AS lsex, NULL AS designation, NULL AS lcourse, NULL AS lfaculty, 0 AS faculty_suggested, NULL AS lseries, NULL AS lreg, NULL AS lphone,
            NULL AS subscribed_year, NULL AS lstatus, 'Member Portal' AS source,
            u.id AS uid, u.full_name AS uname, u.email, u.status AS ustatus,
            p.member_type, p.category, p.gender, p.college, p.programme, p.qualification_level, p.graduation_year, p.graduation_series AS pseries, p.student_reg_no AS preg,
            p.phone AS pphone, p.subscription_status, p.paid_until, p.membership_id, p.employment_status, p.employer, p.profession, p.country, c.name AS chapter, p.legacy_guco_no
        FROM users u JOIN member_profiles p ON p.user_id = u.id LEFT JOIN chapters c ON c.id = p.chapter_id
        WHERE u.role = 'member' AND NOT EXISTS (SELECT 1 FROM legacy_members l WHERE l.claimed_user_id = u.id)";
    foreach (q($sql2)->fetchAll() as $r) {
        $rows[] = member_report_row($r, false);
    }
    return $rows;
}

function member_report_row(array $r, bool $onRegister): array
{
    $hasPortal = !empty($r['uid']);
    $type = $onRegister && !empty($r['designation']) ? $r['designation'] : (($r['member_type'] ?? '') === 'staff' ? 'staff' : 'alumnus');
    if ($hasPortal && ($r['member_type'] ?? '') === 'staff' && $type === 'alumnus') {
        $type = 'staff';
    }
    $sub = '';
    if ($hasPortal && !empty($r['subscription_status'])) {
        $sub = ['active' => 'Active', 'arrears' => 'In arrears', 'unpaid' => 'Unpaid'][$r['subscription_status']] ?? $r['subscription_status'];
    } elseif (!empty($r['subscribed_year'])) {
        $sub = 'Subscribed ' . $r['subscribed_year'];
    }
    $status = $hasPortal ? ['pending' => 'Pending approval', 'approved' => 'Approved member', 'rejected' => 'Rejected'][$r['ustatus']] ?? $r['ustatus'] : 'On the register, no account yet';
    if (!$hasPortal && ($r['lstatus'] ?? '') === 'disputed') {
        $status = 'On the register, number to be confirmed';
    }
    $name = $onRegister ? trim(($r['title'] ?? '') . ' ' . $r['lname']) : $r['uname'];
    $sex = $onRegister ? ($r['lsex'] ?? '') : (['female' => 'F', 'male' => 'M'][$r['gender'] ?? ''] ?? '');
    $fac = trim((string) ($r['college'] ?? '')) !== '' ? $r['college'] : ($r['lfaculty'] ?? '');
    return [
        'guco_no' => $r['guco_no'] ?: ($r['legacy_guco_no'] ?? ''),
        'name' => $name, 'sex' => $sex, 'type' => REPORT_TYPES[$type] ?? 'Alumnus', 'type_key' => $type,
        'course' => trim((string) ($r['programme'] ?? '')) !== '' ? $r['programme'] : ($r['lcourse'] ?? ''),
        'faculty' => $fac, 'faculty_suggested' => (trim((string) ($r['college'] ?? '')) === '' && !empty($r['faculty_suggested'])) ? 1 : 0,
        'qualification' => $r['qualification_level'] ?? '', 'grad_year' => $r['graduation_year'] ?? '',
        'grad_series' => ($r['pseries'] ?? '') ?: ($r['lseries'] ?? ''), 'student_reg_no' => ($r['preg'] ?? '') ?: ($r['lreg'] ?? ''),
        'category' => isset($r['category']) ? (CATEGORY_LABELS[$r['category']] ?? '') : '', 'category_key' => $r['category'] ?? '',
        'subscription' => $sub, 'sub_key' => $hasPortal ? ($r['subscription_status'] ?? '') : (!empty($r['subscribed_year']) ? 'subscribed' : 'none'),
        'subscribed_year' => $r['subscribed_year'] ?? '', 'paid_until' => $r['paid_until'] ?? '',
        'status' => $status, 'app_key' => $hasPortal ? $r['ustatus'] : 'none', 'membership_id' => $r['membership_id'] ?? '',
        'portal' => $hasPortal ? 'Yes' : 'No', 'email' => $r['email'] ?? '', 'phone' => ($r['pphone'] ?? '') ?: ($r['lphone'] ?? ''),
        'chapter' => $r['chapter'] ?? '', 'employment' => isset($r['employment_status']) && $r['employment_status'] ? (EMPLOYMENT_LABELS[$r['employment_status']] ?? '') : '',
        'employer' => $r['employer'] ?? '', 'profession' => $r['profession'] ?? '', 'country' => $r['country'] ?? '', 'source' => $r['source'] ?? '',
    ];
}

/** Filters come from the query string. Returns [rows, active filter descriptions]. */
function member_report_filter(array $all, array $f): array
{
    $desc = [];
    $has = fn($k) => isset($f[$k]) && trim((string) $f[$k]) !== '';
    $contains = fn($hay, $needle) => mb_stripos((string) $hay, trim((string) $needle)) !== false;
    $out = array_values(array_filter($all, function ($r) use ($f, $has, $contains) {
        if ($has('q') && !$contains($r['name'] . ' ' . $r['guco_no'] . ' ' . $r['email'] . ' ' . $r['course'] . ' ' . $r['phone'], $f['q'])) return false;
        if ($has('type')) {
            if ($f['type'] === 'alumni_any' ? !in_array($r['type_key'], ['alumnus', 'alumnus_staff'], true) : ($f['type'] === 'staff_any' ? !in_array($r['type_key'], ['staff', 'alumnus_staff'], true) : $r['type_key'] !== $f['type'])) return false;
        }
        if ($has('course') && !$contains($r['course'], $f['course'])) return false;
        if ($has('faculty') && $r['faculty'] !== $f['faculty']) return false;
        if ($has('series') && (string) $r['grad_series'] !== (string) $f['series']) return false;
        if ($has('year') && (string) $r['grad_year'] !== (string) $f['year']) return false;
        if ($has('sex') && $r['sex'] !== $f['sex']) return false;
        if ($has('category') && $r['category_key'] !== $f['category']) return false;
        if ($has('subscription')) {
            $k = $f['subscription'];
            if ($k === 'subscribed_any' ? $r['subscribed_year'] === '' : ($k === 'none' ? ($r['subscription'] !== '') : ($r['sub_key'] !== $k && !($k === 'subscribed' && $r['sub_key'] === 'subscribed')))) return false;
        }
        if ($has('subscribed_year') && (string) $r['subscribed_year'] !== (string) $f['subscribed_year']) return false;
        if ($has('chapter') && $r['chapter'] !== $f['chapter']) return false;
        if ($has('portal') && $r['portal'] !== ($f['portal'] === 'yes' ? 'Yes' : 'No')) return false;
        if ($has('status') && $r['app_key'] !== $f['status']) return false;
        if ($has('source') && $r['source'] !== $f['source']) return false;
        if ($has('employment') && $r['employment'] !== $f['employment']) return false;
        return true;
    }));
    $labels = ['q' => 'Search', 'type' => 'Type', 'course' => 'Course', 'faculty' => 'Faculty', 'series' => 'Graduation series', 'year' => 'Graduation year', 'sex' => 'Sex', 'category' => 'Category',
        'subscription' => 'Subscription', 'subscribed_year' => 'Subscribed year', 'chapter' => 'Chapter', 'portal' => 'Portal account', 'status' => 'Membership status', 'source' => 'Source', 'employment' => 'Employment'];
    $pretty = [
        'type' => ['alumnus' => 'Alumni only', 'staff' => 'Staff only', 'alumnus_staff' => 'Both alumni and staff', 'alumni_any' => 'Alumni (including staff who are alumni)', 'staff_any' => 'Staff (including alumni who are staff)'],
        'sex' => ['F' => 'Female', 'M' => 'Male'],
        'subscription' => ['subscribed_any' => 'On a subscribed-members list', 'active' => 'Active (portal)', 'arrears' => 'In arrears (portal)', 'unpaid' => 'Unpaid (portal)', 'none' => 'No subscription recorded'],
        'portal' => ['yes' => 'Has an account', 'no' => 'No account yet'],
        'status' => ['approved' => 'Approved members', 'pending' => 'Pending approval', 'rejected' => 'Rejected', 'none' => 'On the register only'],
        'category' => CATEGORY_LABELS,
    ];
    foreach ($labels as $k => $l) {
        if ($has($k)) {
            $desc[] = $l . ': ' . ($pretty[$k][$f[$k]] ?? $f[$k]);
        }
    }
    $sort = $f['sort'] ?? 'guco';
    usort($out, function ($a, $b) use ($sort) {
        return match ($sort) {
            'name' => strcasecmp($a['name'], $b['name']),
            'course' => strcasecmp($a['course'], $b['course']) ?: strcasecmp($a['name'], $b['name']),
            'series' => ((int) $a['grad_series'] <=> (int) $b['grad_series']) ?: strcasecmp($a['name'], $b['name']),
            default => strcmp((string) $a['guco_no'] ?: 'zzz', (string) $b['guco_no'] ?: 'zzz'),
        };
    });
    return [$out, $desc];
}

/** Rows as plain arrays for the chosen columns. */
function member_report_table(array $rows, array $cols): array
{
    return array_map(fn($r) => array_map(fn($c) => (string) ($r[$c] ?? '') . ($c === 'faculty' && !empty($r['faculty_suggested']) ? ' (to confirm)' : ''), $cols), $rows);
}

/* ---------- a tiny ZIP writer (stored files) for Excel and Word ---------- */
function mr_zip(array $files): string
{
    $out = '';
    $central = '';
    $offset = 0;
    foreach ($files as $name => $data) {
        $crc = crc32($data);
        $len = strlen($data);
        $local = pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, 0, 0, 0x21, $crc, $len, $len, strlen($name), 0) . $name . $data;
        $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, 0, 0, 0x21, $crc, $len, $len, strlen($name), 0, 0, 0, 0, 0, $offset) . $name;
        $out .= $local;
        $offset += strlen($local);
    }
    return $out . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, count($files), count($files), strlen($central), $offset, 0);
}

function mr_x(string $s): string
{
    return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s), ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function mr_col(int $i): string
{
    $s = '';
    for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
        $s = chr(65 + ($i - 1) % 26) . $s;
    }
    return $s;
}

function member_report_xlsx(string $title, array $head, array $rows, string $subtitle): string
{
    $cells = function (array $row, int $r, int $style) {
        $x = '';
        foreach ($row as $i => $v) {
            $x .= '<c r="' . mr_col($i) . $r . '" t="inlineStr"' . ($style ? ' s="' . $style . '"' : '') . '><is><t xml:space="preserve">' . mr_x((string) $v) . '</t></is></c>';
        }
        return '<row r="' . $r . '">' . $x . '</row>';
    };
    $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="3" topLeftCell="A4" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>';
    foreach ($head as $i => $h) {
        $w = max(10, min(46, mb_strlen($h) + 4));
        foreach (array_slice($rows, 0, 200) as $r) {
            $w = max($w, min(46, mb_strlen((string) $r[$i]) + 3));
        }
        $sheet .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
    }
    $sheet .= '</cols><sheetData>' . $cells([$title], 1, 2) . $cells([$subtitle], 2, 0) . $cells($head, 3, 1);
    foreach ($rows as $n => $r) {
        $sheet .= $cells($r, $n + 4, 0);
    }
    $sheet .= '</sheetData><autoFilter ref="A3:' . mr_col(count($head) - 1) . (count($rows) + 3) . '"/></worksheet>';
    $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="14"/><color rgb="FF14532D"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF14532D"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs></styleSheet>';
    return mr_zip([
        '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
        '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
        'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Members" sheetId="1" r:id="rId1"/></sheets></workbook>',
        'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
        'xl/worksheets/sheet1.xml' => $sheet,
        'xl/styles.xml' => $styles,
    ]);
}

function member_report_docx(string $title, array $head, array $rows, string $subtitle): string
{
    $n = max(1, count($head));
    $total = 14400; // usable width in twentieths of a point on landscape A4 with narrow margins
    $w = (int) floor($total / $n);
    $cell = function (string $text, bool $header) use ($w) {
        $fill = $header ? '<w:shd w:val="clear" w:color="auto" w:fill="14532D"/>' : '';
        $run = '<w:r><w:rPr>' . ($header ? '<w:b/><w:color w:val="FFFFFF"/>' : '') . '<w:sz w:val="16"/></w:rPr><w:t xml:space="preserve">' . mr_x($text) . '</w:t></w:r>';
        return '<w:tc><w:tcPr><w:tcW w:w="' . $w . '" w:type="dxa"/>' . $fill . '</w:tcPr><w:p><w:pPr><w:spacing w:before="20" w:after="20"/></w:pPr>' . $run . '</w:p></w:tc>';
    };
    $tr = function (array $r, bool $header) use ($cell) {
        return '<w:tr>' . ($header ? '<w:trPr><w:tblHeader/></w:trPr>' : '') . implode('', array_map(fn($v) => $cell((string) $v, $header), $r)) . '</w:tr>';
    };
    $body = '<w:p><w:r><w:rPr><w:b/><w:color w:val="14532D"/><w:sz w:val="32"/></w:rPr><w:t>' . mr_x($title) . '</w:t></w:r></w:p>'
        . '<w:p><w:r><w:rPr><w:color w:val="555555"/><w:sz w:val="18"/></w:rPr><w:t xml:space="preserve">' . mr_x($subtitle) . '</w:t></w:r></w:p>'
        . '<w:tbl><w:tblPr><w:tblW w:w="' . ($w * $n) . '" w:type="dxa"/><w:tblBorders><w:top w:val="single" w:sz="4" w:color="BBBBBB"/><w:left w:val="single" w:sz="4" w:color="BBBBBB"/><w:bottom w:val="single" w:sz="4" w:color="BBBBBB"/><w:right w:val="single" w:sz="4" w:color="BBBBBB"/><w:insideH w:val="single" w:sz="4" w:color="DDDDDD"/><w:insideV w:val="single" w:sz="4" w:color="DDDDDD"/></w:tblBorders><w:tblLayout w:type="fixed"/></w:tblPr><w:tblGrid>'
        . str_repeat('<w:gridCol w:w="' . $w . '"/>', $n) . '</w:tblGrid>' . $tr($head, true);
    foreach ($rows as $r) {
        $body .= $tr($r, false);
    }
    $body .= '</w:tbl><w:sectPr><w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="400" w:footer="400" w:gutter="0"/></w:sectPr>';
    return mr_zip([
        '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>',
        '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
        'word/document.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . $body . '</w:body></w:document>',
    ]);
}

/* ---------- a small PDF writer: A4 landscape table with wrapped cells ---------- */
function member_report_pdf(string $title, array $head, array $rows, string $subtitle): string
{
    $W = 842.0; $H = 595.0; $mx = 28.0; $top = 62.0; $bottom = 34.0; $fs = 7.5; $lh = 9.5; $pad = 3.0;
    $enc = fn($s) => (string) iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);
    $esc = fn($s) => str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $enc($s));
    $cw = 0.5 * $fs; // average character width
    $n = count($head);
    $max = array_fill(0, $n, 0);
    foreach (array_merge([$head], array_slice($rows, 0, 300)) as $r) {
        foreach ($r as $i => $v) {
            $max[$i] = max($max[$i], min(mb_strlen((string) $v), 42));
        }
    }
    $avail = $W - 2 * $mx;
    $want = array_map(fn($m) => max(6, $m) * $cw + 2 * $pad, $max);
    $scale = $avail / array_sum($want);
    $cols = array_map(fn($w) => $w * $scale, $want);
    $wrap = function (string $text, float $width) use ($cw, $pad) {
        $per = max(3, (int) floor(($width - 2 * $pad) / $cw));
        $lines = [];
        foreach (preg_split('/\s+/u', trim($text)) ?: [''] as $word) {
            while (mb_strlen($word) > $per) {
                $lines[] = mb_substr($word, 0, $per);
                $word = mb_substr($word, $per);
            }
            $last = count($lines) - 1;
            if ($last >= 0 && mb_strlen($lines[$last]) + 1 + mb_strlen($word) <= $per && !str_ends_with($lines[$last], "\0")) {
                $lines[$last] .= ' ' . $word;
            } else {
                $lines[] = $word;
            }
        }
        return array_slice($lines ?: [''], 0, 4);
    };
    $pages = [];
    $cur = '';
    $y = $H - $top;
    $drawRow = function (array $r, bool $header, float &$y) use (&$cur, $cols, $wrap, $esc, $fs, $lh, $pad, $mx) {
        $cells = [];
        $lines = 1;
        foreach ($r as $i => $v) {
            $cells[$i] = $wrap((string) $v, $cols[$i]);
            $lines = max($lines, count($cells[$i]));
        }
        $h = $lines * $lh + 2 * $pad - 1;
        if ($header) {
            $cur .= sprintf("0.078 0.325 0.176 rg %.2f %.2f %.2f %.2f re f\n", $mx, $y - $h, array_sum($cols), $h);
        }
        $x = $mx;
        foreach ($cells as $i => $ls) {
            $cur .= $header ? "1 1 1 rg\nBT /F2 $fs Tf " : "0.1 0.1 0.1 rg\nBT /F1 $fs Tf ";
            foreach ($ls as $k => $line) {
                $cur .= sprintf("1 0 0 1 %.2f %.2f Tm (%s) Tj ", $x + $pad, $y - $pad - $lh * ($k + 1) + 2.5, $esc($line));
            }
            $cur .= "ET\n";
            $x += $cols[$i];
        }
        $y -= $h;
        $cur .= sprintf("0.82 0.86 0.83 RG 0.4 w %.2f %.2f m %.2f %.2f l S\n", $mx, $y, $mx + array_sum($cols), $y);
        return $h;
    };
    $newPage = function () use (&$pages, &$cur, &$y, $H, $top, $head, $drawRow) {
        if ($cur !== '') $pages[] = $cur;
        $cur = '';
        $y = $H - $top;
        $drawRow($head, true, $y);
    };
    $newPage();
    foreach ($rows as $r) {
        $est = 1;
        foreach ($r as $i => $v) {
            $est = max($est, count($wrap((string) $v, $cols[$i])));
        }
        if ($y - ($est * $lh + 5) < $bottom) {
            $newPage();
        }
        $drawRow($r, false, $y);
    }
    if (!$rows) {
        $cur .= "BT /F1 9 Tf 1 0 0 1 $mx " . ($y - 18) . " Tm (" . $esc('No members match these filters.') . ") Tj ET\n";
    }
    $pages[] = $cur;
    $count = count($pages);
    foreach ($pages as $p => &$content) {
        $content = sprintf("0.078 0.325 0.176 rg BT /F2 15 Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $mx, $H - 28, $esc($title))
            . sprintf("0.35 0.35 0.35 rg BT /F1 8 Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $mx, $H - 42, $esc(mb_substr($subtitle, 0, 190)))
            . $content
            . sprintf("0.4 0.4 0.4 rg BT /F1 8 Tf 1 0 0 1 %.2f 16 Tm (%s) Tj ET\n", $W - $mx - 70, $esc('Page ' . ($p + 1) . ' of ' . $count))
            . sprintf("0.4 0.4 0.4 rg BT /F1 8 Tf 1 0 0 1 %.2f 16 Tm (%s) Tj ET\n", $mx, $esc('Gulu University Convocation (GUCO). Confidential: contains personal data.'));
    }
    unset($content);
    $objs = [];
    $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
    $kids = [];
    for ($i = 0; $i < $count; $i++) {
        $kids[] = (5 + $i * 2) . ' 0 R';
    }
    $objs[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $count . ' >>';
    $objs[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
    $objs[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
    foreach ($pages as $i => $content) {
        $pg = 5 + $i * 2;
        $objs[$pg] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . ($pg + 1) . ' 0 R >>';
        $objs[$pg + 1] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream";
    }
    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objs as $num => $body) {
        $offsets[$num] = strlen($pdf);
        $pdf .= "$num 0 obj\n$body\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= 'xref' . "\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
    foreach ($offsets as $o) {
        $pdf .= sprintf("%010d 00000 n \n", $o);
    }
    return $pdf . 'trailer << /Size ' . (count($objs) + 1) . ' /Root 1 0 R >>' . "\nstartxref\n$xref\n%%EOF";
}

function member_report_send(string $format, string $title, array $head, array $rows, string $subtitle): void
{
    $base = 'guco-members-' . date('Ymd-His');
    if ($format === 'csv') {
        csv_download($base . '.csv', $head, $rows);
    }
    $types = [
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', fn() => member_report_xlsx($title, $head, $rows, $subtitle)],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', fn() => member_report_docx($title, $head, $rows, $subtitle)],
        'pdf' => ['application/pdf', fn() => member_report_pdf($title, $head, $rows, $subtitle)],
    ];
    if (!isset($types[$format])) {
        return;
    }
    $data = $types[$format][1]();
    header('Content-Type: ' . $types[$format][0]);
    header('Content-Disposition: attachment; filename="' . $base . '.' . $format . '"');
    header('Content-Length: ' . strlen($data));
    header('Cache-Control: private, no-store');
    echo $data;
    exit;
}
