<?php
require_once __DIR__ . '/../includes/admin.php';
crud([
    'table' => 'partners', 'title' => 'Partners', 'perm' => 'partners', 'order' => 'sort_order, id',
    'fields' => [
        'name' => ['label' => 'Partner name', 'type' => 'text', 'required' => true, 'list' => true],
        'logo' => ['label' => 'Logo', 'type' => 'image', 'list' => true, 'hint' => 'Optional. Without a logo the name is shown as text. Add only formally confirmed partners.'],
        'url' => ['label' => 'Website', 'type' => 'text', 'list' => true, 'hint' => 'Optional, e.g. https://example.org'],
        'sort_order' => ['label' => 'Order', 'type' => 'number', 'default' => 100, 'hint' => 'Lower numbers appear first.'],
        'is_public' => ['label' => 'Show on the home page', 'type' => 'check', 'default' => 1, 'list' => true],
    ],
    'hook' => function (array &$v, ?array $old) {
        if ($v['url'] !== '' && !preg_match('#^https?://#i', $v['url'])) {
            return 'The website must start with http:// or https://';
        }
        return null;
    },
]);
