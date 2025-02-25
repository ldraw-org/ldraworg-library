<?php

return [
    'fileformat' => 'The file :attribute is invalid (:value)',
    'duplicate' => 'A :type already exists with this name',
    'missing' => 'Invalid/missing :attribute line',
    'selfreference' => 'Has a self referring Type 1 line',
    'folder' => 'Selected destination (:folder) is invalid for :attribute line (:value)',
    'bfc' => 'All parts must be BFC CERTIFY CCW',
    'preview' => 'Invalid PREVIEW line or singluar/negative matrix',
    'line' => [
        'invalid' => 'Line :value invalid',
        'invalidmeta' => 'Line :value, invalid META command or comment without //',
    ],
    'name' => [
        'invalidchars' => 'Only characters a-z, 0-9, _ . and - are allowed in file names',
        'mismatch' => 'Name: line (:value) does not match filename',
        'xparts' => 'Parts with unknown numbers no longer use "x" as a filename prefix. Contact admin to have them assign you a "u" number.',
        'change' => 'The Name: attribute cannot be changed in a header edit',
    ],
    'description' => [
        'missing' => 'First line must be a description line starting "0 "',
        'invalidchars' => 'Description line may not contain special characters',
        'patternword' => 'Pattern part description must end with "Pattern"',
    ],
    'type' => [
        'path' =>  'Path in Name: (:name) is invalid for !LDRAW_ORG part type (:type)',
        'phycolor' => 'Physical Color parts are no longer accepted',
        'alias' => 'Alias parts must have type Part or Shortcut',
        'flex' => 'Flexible Section parts must be of type Part',
        'aliasdesc' => 'Alias part description must begin with "="',
        'flexname' => 'Flexible section file name must end with "kNN"',
        'subpartdesc' => 'Subpart description must begin with "~"',
        'change' => 'A part\'s folder cannot change via header edit',
    ],
    'author' => [
        'registered' => ':value is not a Parts Tracker registered author',
        'mismatch' => 'File author (:value) does not match submitting author id',
    ],
    'license' => [
        'approved' => '!LICENSE is not an approved Parts Library license',
        'ccby40' => 'CC BY 4.0 License used but all authors listed have not approved the 4.0 CA'
    ],
    'category' => [
        'invalid' => ':value is not a valid category',
        'movedto' => 'Moved to part description must begin with "~"',
    ],
    'keywords' => 'Pattern parts and sticker shortcuts must have a "Set <setnumber>", "CMF", or "Build-A-Minifigure" keyword',
    'history' => [
        'invalid' => 'Invalid history line(s)',
        'author' => 'History has an author who is not registered with the Parts Tracker',
        'removed' => 'History lines cannot be removed via header edit',
        'alter' => 'All changes to existing history must be documented with a comment',
        'eventmismatch' => 'Users with submit events but no !HISTORY line, :users',
    ],
    'fix' => [
        'unofficial' => 'Official part fixes can only be submitted by an admin, send the part to parts@ldraw.org',
        'checked' => '"New version of official file(s)" must be checked to submit official part updates',
    ],
    'replace' => 'To submit a change to a part already on the Parts Tracker, you must check "Replace existing file(s)"',
    'proxy' => 'You are not authorized to submit parts by proxy',
];
