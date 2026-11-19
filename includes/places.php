<?php
/**
 * Places shown on the Contact page map of Gulu.
 * Positions come from OpenStreetMap and are approximate: confirm on the ground before relying on them.
 * Edit this list to add, move or remove a place. Fields: id, name, group, lat, lng, note.
 */
const PLACE_GROUPS = [
    'university' => 'Gulu University',
    'government' => 'Government offices',
    'health' => 'Health',
    'security' => 'Security',
    'market' => 'Market and transport',
];

function gulu_places(): array
{
    return [
        ['guc', 'Gulu University (Main Campus, Laroo)', 'university', 2.78898, 32.31690, 'Home of GUCO. Address: P.O. Box 166, Laroo Road, Gulu City.'],
        ['gu-med', 'Gulu University Faculty of Medicine', 'university', 2.77777, 32.29801, 'Faculty of Medicine, near the town centre on Main Street.'],
        ['grrh', 'Gulu Regional Referral Hospital', 'health', 2.77734, 32.29857, 'Awere Road, Green Valley.'],
        ['district', 'Gulu District Council offices', 'government', 2.77769, 32.29630, 'Main Street, Green Valley.'],
        ['city', 'Gulu City Council offices', 'government', 2.77943, 32.29563, 'Gulu to Kitgum Road (the former Gulu Municipal Council).'],
        ['laroo', 'Laroo Division Council headquarters', 'government', 2.77689, 32.29996, 'Awere Road, Green Valley.'],
        ['immigration', 'Directorate of Citizenship and Immigration Control', 'government', 2.78160, 32.29938, 'Upper Churchill Drive.'],
        ['police', 'Gulu Police Station', 'security', 2.77714, 32.29765, 'Main Street, Green Valley.'],
        ['market', 'Gulu Main Market', 'market', 2.77249, 32.30070, 'Jomo Kenyatta Road, Green Valley.'],
        ['airfield', 'Gulu Airfield', 'market', 2.81080, 32.26994, 'Airport Road, Kasubi.'],
    ];
}
