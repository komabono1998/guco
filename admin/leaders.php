<?php
require_once __DIR__ . '/../includes/admin.php';
crud([
    'table' => 'leaders', 'title' => 'Leadership profiles', 'perm' => 'leaders', 'order' => 'organ, sort_order, id',
    'fields' => [
        'name' => ['label' => 'Name', 'type' => 'text', 'list' => true, 'hint' => 'Leave blank to show "To be announced". Enter only formally confirmed office bearers.'],
        'title' => ['label' => 'Title / office', 'type' => 'text', 'required' => true, 'list' => true],
        'organ' => ['label' => 'Governing body', 'type' => 'select', 'list' => true,
            'options' => ['patron' => 'General Assembly / Patron', 'board' => 'Board', 'executive' => 'Executive Committee', 'disciplinary' => 'Disciplinary Committee']],
        'term' => ['label' => 'Term', 'type' => 'text', 'hint' => 'e.g. 2023 - 2027'],
        'photo' => ['label' => 'Photograph', 'type' => 'image', 'hint' => 'Square or portrait photo, JPG/PNG/WebP up to 5MB.'],
        'bio' => ['label' => 'Profile (biography)', 'type' => 'textarea', 'rows' => 6],
        'role_description' => ['label' => 'Role and responsibilities', 'type' => 'textarea', 'rows' => 4],
        'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 100, 'hint' => 'Lower numbers appear first within the governing body.'],
        'is_public' => ['label' => 'Show on website', 'type' => 'check', 'default' => 1, 'list' => true],
    ],
]);
