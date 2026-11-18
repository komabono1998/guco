<?php
/** Hall of Fame, Mentorship Hub and Alumni Connect shared helpers. */

const FAME_CATEGORIES = ['Distinguished professional', 'Entrepreneur', 'Public servant', 'Innovator', 'Academic and researcher', 'Community leader', 'Young achiever'];

const MENTOR_ROLES = ['mentor' => 'Mentor (I can guide others)', 'mentee' => 'Mentee (I am looking for guidance)', 'both' => 'Both'];

/** Connection row between two users in either direction, or null. */
function connection_between(int $a, int $b): ?array
{
    return q('SELECT * FROM connections WHERE (requester_id = ? AND addressee_id = ?) OR (requester_id = ? AND addressee_id = ?)', [$a, $b, $b, $a])->fetch() ?: null;
}

/** True when the user is an approved, non-revoked member who agreed to be discoverable. */
function discoverable_member(int $userId): ?array
{
    return q("SELECT u.id, u.full_name, u.email, p.programme, p.college, p.graduation_year, p.profession, p.location, p.phone FROM users u JOIN member_profiles p ON p.user_id = u.id
        WHERE u.id = ? AND u.status = 'approved' AND p.card_status <> 'revoked' AND p.directory_consent = 1", [$userId])->fetch() ?: null;
}
