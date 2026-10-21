<?php
require_once __DIR__ . '/../includes/admin.php';
crud([
    'table' => 'pages', 'title' => 'Custom pages', 'perm' => 'pages', 'order' => 'updated_at DESC',
    'fields' => [
        'title' => ['label' => 'Page title', 'type' => 'text', 'required' => true, 'list' => true],
        'slug' => ['label' => 'Web address (slug)', 'type' => 'text', 'list' => true,
            'hint' => 'Leave blank to create it from the title. The page is at page.php?slug=your-slug. Add it to the Menu to link it.'],
        'meta_description' => ['label' => 'Search description', 'type' => 'text', 'hint' => 'One sentence shown in search results.'],
        'image' => ['label' => 'Header image', 'type' => 'image'],
        'body' => ['label' => 'Page content', 'type' => 'textarea', 'rows' => 16, 'required' => true,
            'hint' => 'Blank line = new paragraph. ## Heading, - bullet, **bold**, [link text](https://example.com).'],
        'is_published' => ['label' => 'Published', 'type' => 'check', 'default' => 1, 'list' => true],
    ],
    'hook' => function (array &$v, ?array $old) {
        $slug = slugify($v['slug'] !== '' ? $v['slug'] : $v['title']);
        $dup = q('SELECT id FROM pages WHERE slug = ?' . ($old ? ' AND id <> ' . (int) $old['id'] : ''), [$slug])->fetch();
        if ($dup) {
            return 'Another page already uses that web address.';
        }
        $v['slug'] = $slug;
        return null;
    },
    'extra_actions' => fn($r) => '<a class="btn secondary" href="' . e(url('page.php?slug=' . urlencode($r['slug']))) . '" target="_blank" rel="noopener">View</a>',
]);
