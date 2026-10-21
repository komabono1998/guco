<?php
require_once __DIR__ . '/../includes/admin.php';
crud([
    'table' => 'leaders', 'title' => 'Leadership profiles', 'perm' => 'leaders', 'order' => 'organ, sort_order, id',
    'fields' => [
        'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'list' => true],
        'title' => ['label' => 'Title / office', 'type' => 'text', 'required' => true, 'list' => true],
        'organ' => ['label' => 'Organ', 'type' => 'select', 'list' => true,
            'options' => ['patron' => 'Patron', 'board' => 'Board', 'executive' => 'Executive Committee', 'disciplinary' => 'Disciplinary Committee']],
        'term' => ['label' => 'Term', 'type' => 'text', 'hint' => 'e.g. 2023 - 2027'],
        'photo' => ['label' => 'Photograph', 'type' => 'image'],
        'bio' => ['label' => 'Short biography', 'type' => 'textarea'],
        'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 100],
        'is_public' => ['label' => 'Publish (only after formal confirmation)', 'type' => 'check', 'list' => true],
    ],
]);
