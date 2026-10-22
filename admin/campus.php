<?php
require_once __DIR__ . '/../includes/admin.php';
crud([
    'table' => 'campus_spots', 'title' => 'Explore your campus', 'perm' => 'campus', 'order' => 'sort_order, id',
    'fields' => [
        'title' => ['label' => 'Place or feature', 'type' => 'text', 'required' => true, 'list' => true, 'hint' => 'e.g. Main Campus Gate, Library, Faculty of Medicine, Pece Stadium.'],
        'description' => ['label' => 'Short description', 'type' => 'text', 'hint' => 'One or two sentences (up to 400 characters).'],
        'image' => ['label' => 'Photo', 'type' => 'image', 'list' => true, 'hint' => 'JPG, PNG or WebP up to 5MB. Use photos you have permission to publish.'],
        'link' => ['label' => 'Link', 'type' => 'text', 'list' => true, 'hint' => 'Optional: a page about this place or a virtual tour (https://...).'],
        'sort_order' => ['label' => 'Order', 'type' => 'number', 'default' => 100, 'hint' => 'Lower numbers appear first.'],
        'is_public' => ['label' => 'Show on the home page', 'type' => 'check', 'default' => 1, 'list' => true],
    ],
    'hook' => function (array &$v, ?array $old) {
        if ($v['link'] !== '' && !preg_match('#^https?://[^\s]+$#i', $v['link'])) {
            return 'The link must start with http:// or https://';
        }
        return null;
    },
]);
