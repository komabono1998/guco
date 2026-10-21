<?php
require_once __DIR__ . '/includes/bootstrap.php';
$ev = q('SELECT * FROM events WHERE id = ? AND is_public = 1 AND starts_at IS NOT NULL', [(int) ($_GET['id'] ?? 0)])->fetch();
if (!$ev) {
    http_response_code(404);
    exit('Event not found.');
}
$fmt = fn($t) => gmdate('Ymd\THis\Z', strtotime($t));
$esc = fn($s) => str_replace(["\\", ";", ",", "\r\n", "\n"], ["\\\\", "\\;", "\\,", "\\n", "\\n"], (string) $s);
$end = $ev['ends_at'] ?: date('Y-m-d H:i:s', strtotime($ev['starts_at']) + 7200);
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="guco-event-' . (int) $ev['id'] . '.ics"');
echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//GUCO//Events//EN\r\nBEGIN:VEVENT\r\n";
echo 'UID:guco-event-' . (int) $ev['id'] . "@guco\r\nDTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
echo 'DTSTART:' . $fmt($ev['starts_at']) . "\r\nDTEND:" . $fmt($end) . "\r\n";
echo 'SUMMARY:' . $esc($ev['title']) . "\r\nLOCATION:" . $esc($ev['location']) . "\r\n";
echo 'DESCRIPTION:' . $esc($ev['description']) . "\r\nSTATUS:" . ($ev['status'] === 'cancelled' ? 'CANCELLED' : 'CONFIRMED') . "\r\n";
echo "END:VEVENT\r\nEND:VCALENDAR\r\n";
