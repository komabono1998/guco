<?php
require_once __DIR__ . '/../includes/admin.php';
crud([
    'table' => 'gallery_albums', 'title' => 'Gallery albums', 'perm' => 'gallery', 'order' => 'sort_order, event_date DESC, id DESC',
    'fields' => [
        'title' => ['label' => 'Album title', 'type' => 'text', 'required' => true, 'list' => true, 'hint' => 'e.g. Alumni & Stakeholders Dinner 2026, Graduation Day.'],
        'event_date' => ['label' => 'Event date', 'type' => 'date', 'list' => true],
        'description' => ['label' => 'Description', 'type' => 'text'],
        'cover' => ['label' => 'Cover picture', 'type' => 'image', 'hint' => 'Optional. Without one the first photo is used. You can also set a cover from the album\'s photos.'],
        'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 100, 'hint' => 'Lower numbers appear first.'],
        'is_public' => ['label' => 'Show on website', 'type' => 'check', 'default' => 1, 'list' => true],
    ],
    'extra_actions' => fn($r) => '<a class="btn secondary" href="' . e(url('admin/gallery_photos.php?album=' . (int) $r['id'])) . '">Photos (' . (int) q('SELECT COUNT(*) FROM gallery WHERE album_id = ?', [$r['id']])->fetchColumn() . ')</a>',
]);
