<?php
/**
 * New GUCO numbers and duplicate checks for registration.
 *  - Every new member gets the next GUCO number after the highest one already used (register, members, imports).
 *  - Before a new record is created, the name is compared with the register of existing members and with member accounts.
 */

/** "GUCO-00123" for 123. */
function guco_number_label(int $n): string
{
    return 'GUCO-' . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
}

/** Last four digits are enough to compare phone numbers written differently (+256 7.., 07..). */
function dedupe_phone_key(string $phone): string
{
    $d = preg_replace('/\D+/', '', $phone);
    return strlen($d) >= 9 ? substr($d, -9) : '';
}

function dedupe_mask_email(string $email): string
{
    [$u, $d] = array_pad(explode('@', $email, 2), 2, '');
    if ($d === '') {
        return '';
    }
    $dot = strrpos($d, '.');
    $host = $dot === false ? $d : substr($d, 0, $dot);
    $tld = $dot === false ? '' : substr($d, $dot);
    return mb_substr($u, 0, 1) . '***@' . mb_substr($host, 0, 1) . '***' . $tld;
}

/**
 * People who may already be on record under this name (or phone number). Each match is:
 * [kind => register|account, id, guco_no, name, detail, status, reason, email_hint].
 * Only what the person needs to recognise themselves is returned: no phone numbers, birth dates or full emails.
 */
function dedupe_candidates(string $name, string $phone = '', int $ignoreUserId = 0): array
{
    $out = [];
    $tokens = array_values(array_filter(legacy_name_tokens($name), fn($t) => mb_strlen($t) >= 3));
    $seenUsers = [];

    if ($tokens) {
        $sql = "SELECT * FROM legacy_members WHERE status <> 'withdrawn' AND (";
        $args = [];
        $sql .= implode(' OR ', array_map(function ($t) use (&$args) {
            $args[] = '%' . addcslashes($t, '%_\\') . '%';
            return 'full_name LIKE ?';
        }, $tokens)) . ') LIMIT 400';
        foreach (q($sql, $args)->fetchAll() as $r) {
            if (!legacy_same_person($name, $r['full_name'], false)) {
                continue;
            }
            if ($ignoreUserId && (int) $r['claimed_user_id'] === $ignoreUserId) {
                continue;
            }
            $bits = array_filter([
                LEGACY_DESIGNATIONS[$r['designation']] ?? '',
                $r['course'] ?: '',
                $r['graduation_series'] ? 'Graduation series ' . $r['graduation_series'] : '',
            ]);
            $row = ['kind' => 'register', 'id' => (int) $r['id'], 'guco_no' => $r['guco_no'], 'name' => legacy_display_name($r),
                'detail' => implode(' · ', $bits), 'status' => $r['status'], 'reason' => 'name', 'email_hint' => ''];
            if ($r['claimed_user_id']) {
                $u = q('SELECT email FROM users WHERE id = ?', [(int) $r['claimed_user_id']])->fetchColumn();
                $row['kind'] = 'account';
                $row['email_hint'] = $u ? dedupe_mask_email((string) $u) : '';
                $seenUsers[(int) $r['claimed_user_id']] = true;
            }
            $out[] = $row;
        }
    }

    // Member accounts that are not tied to a register entry.
    if ($tokens) {
        $sql = "SELECT u.id, u.full_name, u.email, p.legacy_guco_no, p.member_no, p.member_type, p.programme, p.graduation_year
                FROM users u JOIN member_profiles p ON p.user_id = u.id WHERE u.role = 'member' AND u.status <> 'rejected' AND (";
        $args = [];
        $sql .= implode(' OR ', array_map(function ($t) use (&$args) {
            $args[] = '%' . addcslashes($t, '%_\\') . '%';
            return 'u.full_name LIKE ?';
        }, $tokens)) . ') LIMIT 400';
        foreach (q($sql, $args)->fetchAll() as $u) {
            if (isset($seenUsers[(int) $u['id']]) || (int) $u['id'] === $ignoreUserId || !legacy_same_person($name, $u['full_name'], false)) {
                continue;
            }
            $seenUsers[(int) $u['id']] = true;
            $bits = array_filter([MEMBER_TYPES[$u['member_type']] ?? '', $u['programme'] ?: '', $u['graduation_year'] ? 'Graduated ' . $u['graduation_year'] : '']);
            $out[] = ['kind' => 'account', 'id' => (int) $u['id'], 'guco_no' => $u['legacy_guco_no'] ?: ($u['member_no'] ? guco_number_label((int) $u['member_no']) : ''),
                'name' => $u['full_name'], 'detail' => implode(' · ', $bits), 'status' => 'claimed', 'reason' => 'name', 'email_hint' => dedupe_mask_email((string) $u['email'])];
        }
    }

    // The same phone number on another member record is a strong sign of the same person.
    $pk = dedupe_phone_key($phone);
    if ($pk !== '') {
        $rows = q("SELECT u.id, u.full_name, u.email, p.legacy_guco_no, p.member_no, p.phone FROM users u JOIN member_profiles p ON p.user_id = u.id
                   WHERE u.role = 'member' AND u.status <> 'rejected' AND p.phone LIKE ? LIMIT 20", ['%' . substr($pk, -7) . '%'])->fetchAll();
        foreach ($rows as $u) {
            if (isset($seenUsers[(int) $u['id']]) || (int) $u['id'] === $ignoreUserId || dedupe_phone_key((string) $u['phone']) !== $pk) {
                continue;
            }
            $seenUsers[(int) $u['id']] = true;
            $out[] = ['kind' => 'account', 'id' => (int) $u['id'], 'guco_no' => $u['legacy_guco_no'] ?: ($u['member_no'] ? guco_number_label((int) $u['member_no']) : ''),
                'name' => $u['full_name'], 'detail' => 'Uses the same phone number', 'status' => 'claimed', 'reason' => 'phone', 'email_hint' => dedupe_mask_email((string) $u['email'])];
        }
    }
    return array_slice($out, 0, 6);
}

/** One line for the Secretariat's notes. */
function dedupe_note(array $matches): string
{
    return implode('; ', array_map(fn($m) => ($m['guco_no'] ?: 'no number') . ' ' . $m['name'] . ' (' . ($m['kind'] === 'register' ? 'on the register, not yet claimed' : 'has an account') . ')', $matches));
}

/**
 * Gives a newly created member their GUCO number and records them on the register of GUCO members,
 * so they can be found by number or name and are never registered twice.
 */
function assign_guco_number(int $userId, int $memberNo, array $info): string
{
    $no = guco_number_label($memberNo);
    q('UPDATE member_profiles SET legacy_guco_no = ? WHERE user_id = ?', [$no, $userId]);
    $sex = ['female' => 'F', 'male' => 'M'][$info['gender'] ?? ''] ?? null;
    $des = ($info['member_type'] ?? 'alumni') === 'staff' ? 'staff' : 'alumnus';
    q("INSERT IGNORE INTO legacy_members (guco_no, title, full_name, sex, designation, course, faculty, graduation_series, student_reg_no, phone, status, source, claimed_user_id, claimed_at)
       VALUES (?, '', ?, ?, ?, ?, ?, ?, ?, ?, 'claimed', ?, ?, NOW())", [
        $no, mb_substr((string) ($info['full_name'] ?? ''), 0, 200), $sex, $des, ($info['programme'] ?? '') ?: null, ($info['college'] ?? '') ?: null,
        ($info['graduation_series'] ?? null) ?: null, ($info['student_reg_no'] ?? '') ?: null, mb_substr((string) ($info['phone'] ?? ''), 0, 40) ?: null,
        'Website registration ' . date('Y'), $userId,
    ]);
    return $no;
}
