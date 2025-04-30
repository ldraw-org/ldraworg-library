<?php

return [
    'fileformat' => 'The file :attribute is invalid (:value)',
    'duplicate' => 'A :type already exists with this name',
    'missing' => 'Invalid/missing :attribute line',
    'circularreference' => 'Has a self referring Type 1 line',
    'bfc' => 'All parts must be BFC CERTIFY CCW',
    'previewinvalid' => 'Invalid PREVIEW line or singular/negative matrix',

    'obsoleteimproper' => 'An obsolete part must have the category Obsolete and ~Obsolete file or (Obsolete) in the description',
    
    'line' => [
        'invalid' => 'Line :value invalid',
        'invalidmeta' => 'Line :value, invalid META command or comment without //',
        'invalidcolor' => 'Line :value, color code not in LDConfig.ldr',
        'invalidcolor16' => 'Line :value, color code 16 not allowed for linetypes 2, 5',
        'invalidcolor24' => 'Line :value, color code 24 not allowed for linetypes 1, 3, 4',
        'invalidnumbers' => 'Line :value invalid',
        'singular' => 'Line :value, singular rotation matrix',
        'identicalpoints' => 'Line :value, identical points',
        'colinear' => 'Line :value, points are colinear',
        'notconvex' => 'Line :value, quad is concave or bowtie',
        'notcoplaner' => 'Line :value, quad is not coplanar (angle :angle)',
    ],

    'name' => [
        'invalidchars' => 'Only characters a-z, 0-9, _ . and - are allowed in file names',
        'mismatch' => 'Name: line (:value) does not match filename',
        'xparts' => 'Parts with unknown numbers no longer use "x" as a filename prefix. Contact admin to have them assign you a "u" number.',
        'flex' => 'Flexible section name must end with kXX',
    ],

    'description' => [
        'invalidchars' => 'Description line may not contain special characters',
        'patternword' => 'Pattern part description must end with "Pattern" or have a "Colour Combination" keyword',
        'subpartdesc' => 'Subpart descriptions must begin with "~"',
        'aliasdesc' => 'Alias part descriptions must begin with "="',
        'thirdpartydesc' => 'Third party part descriptions must begin with "|"',
        'movedorobsolete' => 'Moved or Obsolete part descriptions must begin with "~"',
    ],

    'type' => [
        'path' =>  'Path in Name: (:name) is invalid for !LDRAW_ORG part type (:type)',
        'phycolor' => 'Physical Color parts are no longer accepted',
        'alias' => 'Alias parts must have type Part or Shortcut',
        'flex' => 'Flexible Section parts must be of type Part',
    ],

    'author' => [
        'registered' => ':value is not a Parts Tracker registered author',
    ],

    'license' => [
        'approved' => 'All parts are required to be CC BY 4.0',
    ],

    'category' => [
        'invalid' => 'Category is not valid',
    ],

    'keywords' => [
        'patternset' => 'Pattern parts and sticker shortcuts must have a "Set <setnumber>", "CMF", or "Build-A-Minifigure" keyword',
    ],

    'history' => [
        'invalid' => 'Invalid history line(s)',
        'authorregistered' => 'History has an author who is not registered with the Parts Tracker',
        'alter' => 'All changes to existing history must be documented with a comment',
    ],

    'fix' => [
        'checked' => '"New version of official file(s)" must be checked to submit official part updates',
    ],

    'replace' => 'To submit a change to a part already on the Parts Tracker, you must check "Replace existing file(s)"',

    'proxy' => 'You are not authorized to submit parts by proxy',

    'tracker_hold' => [
        'uncertsubs' => 'Has uncertified subfiles',
        'nocertparents' => 'No path of certified files to a certified or official parent in the parts folder',
        'missing' => 'Has missing subfiles',
        'adminhold' => 'On administrative hold'
    ],

    'warning' => [
        'minifigcategory' => 'Ensure correct Minifig category',
        'notcoplaner' => 'Line :value, quad is not coplanar (angle :angle)',
    ]
];
