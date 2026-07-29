<?php

// LDraw Config Values

return [
    // The library rebrickable API key
    'rebrickable_api_key' => env('REBRICKABLE_API_KEY'),

    // LDView debug writting to logs
    'ldview_debug' => env('LDVIEW_DEBUG', false),

    // Enable Part library debug logging
    'library_debug' => env('LIBRARY_DEBUG', false),

    // Choose between cloudflare disk or local storage
    'archive_disk' => env('ARCHIVE_DISK', 'local'),

    // Local user Password
    'local_user_password' => env('LOCAL_USER_PASSWORD', null),

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
