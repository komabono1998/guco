<?php
require_once __DIR__ . '/../includes/admin.php';
$cats = ['Constitution', 'Governance', 'Annual Reports', 'Financial Reports', 'Audit Reports', 'Programme and Impact Reports', 'Newsletters', 'Notices', 'Policies and Guidance', 'Forms and Templates', 'Other'];
crud([
    'table' => 'resources', 'title' => 'Reports & documents', 'perm' => 'resources', 'order' => 'published_at DESC, id DESC',
    'fields' => [
        'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'list' => true],
        'description' => ['label' => 'Short description', 'type' => 'text'],
        'category' => ['label' => 'Category', 'type' => 'select', 'options' => array_combine($cats, $cats), 'list' => true],
        'file' => ['label' => 'Document', 'type' => 'doc', 'hint' => 'PDF or Office file up to 40MB. Upload a file, or give a link below.'],
        'external_url' => ['label' => 'Or link to the document online', 'type' => 'text', 'hint' => 'Optional full address (https://...) if the report is hosted elsewhere.'],
        'published_at' => ['label' => 'Publication date', 'type' => 'date', 'list' => true],
        'member_only' => ['label' => 'Members only', 'type' => 'check', 'list' => true],
        'is_public' => ['label' => 'Publish for public viewing', 'type' => 'check', 'default' => 1, 'list' => true],
    ],
    'list_extra' => [
        'Uploaded by' => fn(array $r) => $r['uploaded_by_name'] ?: '-',
        'Date added' => fn(array $r) => fmt_date($r['created_at'], 'j M Y, g:ia'),
    ],
    'hook' => function (array &$v, ?array $old) {
        if (!$old) {
            $who = current_user();
            $v['uploaded_by'] = $who['id'] ?? null;
            $v['uploaded_by_name'] = $who['full_name'] ?? null;
        }
        if ($v['external_url'] !== '' && !preg_match('#^https?://[^\s]+$#i', $v['external_url'])) {
            return 'The link must start with http:// or https://';
        }
        $hasFile = !empty($v['filename']) || ($old && !empty($old['filename']));
        if (!$hasFile && $v['external_url'] === '') {
            return 'Upload the document or give a link to it.';
        }
        if (empty($v['published_at'])) {
            $v['published_at'] = date('Y-m-d');
        }
        return null;
    },
]);
