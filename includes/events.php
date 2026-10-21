<?php
const EVENT_CATEGORIES = ['Dinner', 'AGM', 'Sports', 'Networking', 'Awards', 'Community Service', 'Chapter Event', 'Other'];

/** Where the event is held, as a short line. */
function event_where(array $ev): string
{
    return trim($ev['location'] . ($ev['online_link'] && !$ev['location'] ? 'Online' : ''));
}

/** "Fri 20 Nov 2026, 10:00am" or "Date to be announced". */
function event_when(array $ev, string $format = 'D j M Y, g:ia'): string
{
    return $ev['starts_at'] ? fmt_date($ev['starts_at'], $format) : 'Date to be announced';
}

function event_flyer_url(array $ev): ?string
{
    return $ev['flyer'] ? url('uploads/' . rawurlencode($ev['flyer'])) : null;
}

/** Live countdown; shown only for confirmed, scheduled, future events with the countdown switched on. */
function event_countdown(array $ev, string $class = ''): string
{
    if (!$ev['show_countdown'] || !$ev['starts_at'] || $ev['status'] !== 'scheduled') {
        return '';
    }
    $ts = strtotime($ev['starts_at']);
    if ($ts <= time()) {
        return '';
    }
    return '<div class="countdown ' . e($class) . '" data-start="' . e(date('c', $ts)) . '" role="timer" aria-label="Time until ' . e($ev['title']) . '">'
        . '<span><b data-u="d">--</b>days</span><span><b data-u="h">--</b>hours</span>'
        . '<span><b data-u="m">--</b>minutes</span><span><b data-u="s">--</b>seconds</span></div>';
}

function event_status_badge(array $ev): string
{
    return $ev['status'] === 'scheduled' ? '' : '<span class="badge bad">' . e(ucfirst($ev['status'])) . '</span>';
}
