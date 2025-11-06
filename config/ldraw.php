<?php

// LDraw Config Values

return [
    // The library rebrickable API key
    'rebrickable_api_key' => env('REBRICKABLE_API_KEY'),

    // LDView debug writting to logs
    'ldview_debug' => env('LDVIEW_DEBUG', false),

    // Enable Part library debug logging
    'library_debug' => env('LIBRARY_DEBUG', false),

    // These are groups for Part Author/Reviewer tags
    'mybb-groups' => [
        'Administrators' => 4,
        'Part Author' => 8,
        'Part Reviewer' => 9,
        'Library Admin' => 10,
        'LDraw Member' => 21,
        'Registered' => 11,
    ],

    // Mybb OMR info
    'mybb_omr' => [
        'omr_forum_id' => 17,
        'checked_icon_id' => 17,
        'attachment_path' => '/var/www/ldraw.org/mybb/uploads/',
    ],

    // External Site URL Stubs
    'external_sites' => [
        'bricklink' => 'https://www.bricklink.com/v2/catalog/catalogitem.page?P=',
        'rebrickable' => 'https://rebrickable.com/parts/',
        'brickowl' => 'https://www.brickowl.com/catalog/',
        'brickset' => 'https://brickset.com/parts/design-'
    ],

    // Check limits
    'check' => [
        'max_point_angle' => 179.9,
        'min_point_angle' => 0.025,
        'coplanar_angle_error' => 3.0,
        'coplanar_angle_warning' => 1.0,
    ],

];
