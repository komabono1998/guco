<?php
require_once __DIR__ . '/../includes/admin.php';
crud([
    'table' => 'awards', 'title' => 'Awards', 'perm' => 'awards', 'order' => 'id',
    'fields' => [
        'name' => ['label' => 'Award name', 'type' => 'text', 'required' => true, 'list' => true],
        'description' => ['label' => 'Description and purpose', 'type' => 'textarea', 'rows' => 3],
        'eligibility' => ['label' => 'Eligibility criteria', 'type' => 'textarea', 'rows' => 3],
        'nomination_status' => ['label' => 'Nomination status', 'type' => 'select', 'list' => true,
            'options' => ['not_open' => 'Not open', 'open' => 'Open', 'closed' => 'Closed'],
            'hint' => 'Only set to Open once the nomination details are approved.'],
        'deadline' => ['label' => 'Nomination deadline', 'type' => 'date', 'list' => true],
        'required_documents' => ['label' => 'Required supporting documents', 'type' => 'textarea', 'rows' => 3],
        'selection_process' => ['label' => 'Selection and review process', 'type' => 'textarea', 'rows' => 3],
        'past_recipients' => ['label' => 'Past recipients (approved)', 'type' => 'textarea', 'rows' => 3],
        'contact' => ['label' => 'Official contact for enquiries', 'type' => 'text'],
        'is_public' => ['label' => 'Show on website', 'type' => 'check', 'default' => 1, 'list' => true],
    ],
]);
