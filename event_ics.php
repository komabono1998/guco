<?php
require_once __DIR__ . '/includes/bootstrap.php';
$ev = q('SELECT * FROM events WHERE id = ? AND is_public = 1 AND starts_at IS NOT NULL', [(int) ($_GET['id'] ?? 0)])->fetch();
if (!$ev) {
    http_response_code(404);
    exit('Event not found.');
}
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="guco-event-' . (int) $ev['id'] . '.ics"');
echo event_ics_text($ev);
