<?php
require_once __DIR__ . '/../includes/admin.php';
$parents = ['' => 'Top-level item (main menu bar)'] + q('SELECT id, label FROM menu_items WHERE parent_id IS NULL ORDER BY sort_order, id')->fetchAll(PDO::FETCH_KEY_PAIR);
$all = ['' => 'Top level'] + q('SELECT id, label FROM menu_items ORDER BY id')->fetchAll(PDO::FETCH_KEY_PAIR);
crud([
    'table' => 'menu_items', 'title' => 'Navigation menu', 'perm' => 'menu',
    'order' => 'COALESCE(parent_id, id), parent_id IS NOT NULL, sort_order, id',
    'fields' => [
        'label' => ['label' => 'Menu text', 'type' => 'text', 'required' => true, 'list' => true],
        'url' => ['label' => 'Link', 'type' => 'text', 'required' => true, 'list' => true,
            'hint' => 'A site page such as about.php, a section such as about.php#vision, a custom page such as page.php?slug=my-page, or a full https:// address.'],
        'parent_id' => ['label' => 'Shows under', 'type' => 'select', 'options' => $parents, 'allow_empty' => true, 'list' => true,
            'hint' => 'Choose a top-level item to make this a drop-down link.'],
        'sort_order' => ['label' => 'Order', 'type' => 'number', 'default' => 100, 'list' => true, 'hint' => 'Lower numbers appear first.'],
        'is_visible' => ['label' => 'Visible in the menu', 'type' => 'check', 'default' => 1, 'list' => true],
        'new_tab' => ['label' => 'Open in a new tab', 'type' => 'check'],
    ],
    'hook' => function (array &$v, ?array $old) {
        if ($old && !empty($v['parent_id']) && (int) $v['parent_id'] === (int) $old['id']) {
            return 'An item cannot sit under itself.';
        }
        if ($old && !empty($v['parent_id']) && q('SELECT 1 FROM menu_items WHERE parent_id = ?', [$old['id']])->fetch()) {
            return 'This item has drop-down links of its own, so it must stay top-level. Move or delete those first.';
        }
        if (!empty($v['parent_id']) && q('SELECT parent_id FROM menu_items WHERE id = ?', [$v['parent_id']])->fetchColumn()) {
            return 'Choose a top-level item as the parent.';
        }
        if (!preg_match('#^(https?://|[A-Za-z0-9_\-./?=&\#%+]+$)#', $v['url'])) {
            return 'Enter a valid link.';
        }
        return null;
    },
]);
