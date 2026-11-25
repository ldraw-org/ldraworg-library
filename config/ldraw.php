<?php

// LDraw Config Values

return [
    // The library rebrickable API key
    'rebrickable_api_key' => env('REBRICKABLE_API_KEY'),

    // Directory holding the Rebrickable CSV downloads (.csv or .csv.gz)
    'rebrickable_csv_path' => env('REBRICKABLE_CSV_PATH', storage_path('app/rebrickable-csv/all_downloads')),

    // Rebrickable's archive of every CSV download. Rebrickable allows it to be downloaded at most once per day.
    'rebrickable_csv_url' => env('REBRICKABLE_CSV_URL', 'https://cdn.rebrickable.com/media/downloads/all_downloads.tar.gz'),

    // LDView debug writting to logs
    'ldview_debug' => env('LDVIEW_DEBUG', false),

    // Enable Part library debug logging
    'library_debug' => env('LIBRARY_DEBUG', false),

    // Choose between cloudflare disk or local storage
    'archive_disk' => env('ARCHIVE_DISK', 'local'),

    // Local user Password
    'local_user_password' => env('LOCAL_USER_PASSWORD', null),

    // The committed Inno Setup script template for the Windows installer, filled in for each release.
    // Placeholders: @RELEASE_NAME@ (2026-12), @RELEASE_SHORT@ (2612), @APP_ID@.
    'installer_template' => env('LDRAW_INSTALLER_TEMPLATE', resource_path('installer/LDrawParts.iss')),

    // The installer's Inno Setup AppId GUID, without braces (write AppId={{@APP_ID@} in the template).
    // Kept out of the repository; no script is written without it.
    'installer_app_id' => env('LDRAW_INSTALLER_APP_ID'),

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
