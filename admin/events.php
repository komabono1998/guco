<?php
require_once __DIR__ . '/../includes/admin.php';
crud([
    'table' => 'events', 'title' => 'Events', 'perm' => 'events', 'order' => 'starts_at IS NULL DESC, starts_at DESC',
    'fields' => [
        'title' => ['label' => 'Event title', 'type' => 'text', 'required' => true, 'list' => true],
        'category' => ['label' => 'Category', 'type' => 'select', 'options' => array_combine(EVENT_CATEGORIES, EVENT_CATEGORIES), 'list' => true],
        'starts_at' => ['label' => 'Date and start time', 'type' => 'datetime', 'list' => true,
            'hint' => 'Leave blank while the date is unconfirmed: the site shows "Date to be announced" and no countdown. Times are East Africa Time.'],
        'ends_at' => ['label' => 'End date and time', 'type' => 'datetime'],
        'location' => ['label' => 'Venue', 'type' => 'text', 'list' => true],
        'online_link' => ['label' => 'Online meeting link', 'type' => 'text'],
        'flyer' => ['label' => 'Event flyer', 'type' => 'image', 'hint' => 'JPG, PNG or WebP up to 5MB. Shown on the event page, calendar and home page.'],
        'description' => ['label' => 'Description', 'type' => 'textarea', 'rows' => 5],
        'extra_details' => ['label' => 'More details', 'type' => 'textarea', 'rows' => 5, 'hint' => 'Programme, dress code, tickets, rules, fixtures, etc.'],
        'audience' => ['label' => 'Intended audience', 'type' => 'text'],
        'accessibility_info' => ['label' => 'Accessibility information', 'type' => 'text'],
        'contact' => ['label' => 'Official contact', 'type' => 'text'],
        'show_countdown' => ['label' => 'Show a live countdown to the start time', 'type' => 'check', 'default' => 1, 'list' => true],
        'featured' => ['label' => 'Feature on the home page (spotlight banner with flyer and countdown)', 'type' => 'check', 'list' => true],
        'registration_open' => ['label' => 'Registration open', 'type' => 'check', 'list' => true],
        'status' => ['label' => 'Status', 'type' => 'select', 'list' => true,
            'options' => ['scheduled' => 'Scheduled', 'postponed' => 'Postponed', 'cancelled' => 'Cancelled']],
        'is_public' => ['label' => 'Show on website', 'type' => 'check', 'default' => 1],
    ],
    'hook' => function (array &$v, ?array $old) {
        if (!empty($v['starts_at']) && !empty($v['ends_at']) && strtotime($v['ends_at']) < strtotime($v['starts_at'])) {
            return 'The end time must be after the start time.';
        }
        return null;
    },
    'extra_actions' => fn($r) => '<a class="btn secondary" href="' . e(url('admin/registrations.php?event=' . (int) $r['id'])) . '">Registrations</a>'
        . '<a class="btn secondary" href="' . e(url('admin/event_invite.php?event=' . (int) $r['id'])) . '">Invite</a>',
]);
