<?php
require_once __DIR__ . '/../includes/admin.php';
crud([
    'table' => 'events', 'title' => 'Events', 'perm' => 'events', 'order' => 'starts_at DESC',
    'fields' => [
        'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'list' => true],
        'starts_at' => ['label' => 'Starts', 'type' => 'datetime', 'required' => true, 'list' => true],
        'ends_at' => ['label' => 'Ends', 'type' => 'datetime'],
        'location' => ['label' => 'Venue', 'type' => 'text', 'list' => true],
        'online_link' => ['label' => 'Online link', 'type' => 'text'],
        'audience' => ['label' => 'Intended audience', 'type' => 'text'],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
        'accessibility_info' => ['label' => 'Accessibility information', 'type' => 'text'],
        'contact' => ['label' => 'Official contact', 'type' => 'text'],
        'status' => ['label' => 'Status', 'type' => 'select', 'list' => true,
            'options' => ['scheduled' => 'Scheduled', 'postponed' => 'Postponed', 'cancelled' => 'Cancelled']],
        'registration_open' => ['label' => 'Registration open', 'type' => 'check', 'list' => true],
        'is_public' => ['label' => 'Show on website', 'type' => 'check', 'default' => 1],
    ],
    'extra_actions' => fn($r) => '<a class="btn secondary" href="' . e(url('admin/registrations.php?event=' . (int) $r['id'])) . '">Registrations</a>',
]);
