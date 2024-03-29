<?php

// LDraw Config Values

return [
  // Default directories for the ldraw folder
  'dirs' => [
    'parts',
    'parts/s',
    'parts/textures',
    'parts/textures/s',
    'p',
    'p/48',
    'p/8',
    'p/textures/48',
    'p/textures/8',
    'parts/h',
    'parts/textures/h',
  ],

  // The location for temporary staging of files
  'staging_dir' => [
    'disk' => 'local',
    'path' => 'tmp'
  ],

  'rebrickable' => [
    'rate_limit' => 1,
    'retry_limit' => 2,
    'api' => [
      'url' => 'https://rebrickable.com/api/v3/lego',
      'key' => env('REBRICKABLE_API_KEY'),
    ], 
  ],

  // LDView parameters and paths
  'render' => [
    'dir' => [
      'ldconfig' => [
        'disk' => 'library',
        'path' => 'official/LDConfig.ldr'
      ], 
      'image' => [
        'official' => [
          'disk' => 'images',
          'path' => 'library/official',  
        ],
        'unofficial' => [
          'disk' => 'images',
          'path' => 'library/unofficial',  
        ]      
      ],
    ],
    'options' => [
      'Texmaps' => '1',
      'AutoCrop' => '1',
      'BackgroundColor3' => '0xFFFFFF',
      'BFC' => '0', 
      'ConditionalHighlights' => '1',
      'FOV' => '0.1',
      'LineSmoothing' => '1',
      'MemoryUsage' => '0',
      'ProcessLDConfig' => '1',
      'SaveAlpha' => '1',
      'SaveZoomToFit' => '1', 
      'SeamWidth' => '0',
      'ShowHighlightLines' => '1',
      'SubduedLighting' => '1',
      'UseQualityStuds' => '1',
      'UseSpecular' => '0',
      'DebugLevel' => '0',
      'CheckPartTracker' => '0',
      'LightVector' => '-1,1,1', 
      'TextureStuds' => '0',
    ],
    'debug' => false,
    'alt-camera' => [
        '3678' => '-1 0 0 0 1 0 0 0 -1',
        '3678a' => '-1 0 0 0 1 0 0 0 -1',
        '3678b' => '-1 0 0 0 1 0 0 0 -1',
        '3678bpk0' => '1 0 0 0 1 0 0 0 1',
        '3678ad01' => '1 0 0 0 1 0 0 0 1',
        '3678ad24' => '1 0 0 0 1 0 0 0 1',
        '3678ad25' => '1 0 0 0 1 0 0 0 1',
        '3678ad26' => '1 0 0 0 1 0 0 0 1',
        '3678ad27' => '1 0 0 0 1 0 0 0 1',
        '3678ad28' => '1 0 0 0 1 0 0 0 1',
        '3678apc0' => '1 0 0 0 1 0 0 0 1',
        '4864' => '-1 0 0 0 1 0 0 0 -1',
        '4864a' => '-1 0 0 0 1 0 0 0 -1',
        '6268' => '-1 0 0 0 1 0 0 0 -1',
        '4215' => '-1 0 0 0 1 0 0 0 -1',
        '4215a' => '-1 0 0 0 1 0 0 0 -1',
        '2362' => '-1 0 0 0 1 0 0 0 -1',
        '2362a' => '-1 0 0 0 1 0 0 0 -1',
        '4865' => '-1 0 0 0 1 0 0 0 -1',
        '4345' => '-1 0 0 0 1 0 0 0 -1',
        '83496' => '1 0 0 0 -1 0 0 0 -1',
        '11203' => '1 0 0 0 -1 0 0 0 -1',
        '35459' => '1 0 0 0 -1 0 0 0 -1',
        '60581' => '-1 0 0 0 1 0 0 0 -1',
        '61287' => '-1 0 0 0 1 0 0 0 -1',
        '4182' => '-1 0 0 0 1 0 0 0 -1',
    ],
  ],
  // Max sizes for images
  'image' => [
    'normal' => [
      'width' => '300',
      'height' => '300',
    ],
    'thumb' => [
      'width' => '35',
      'height' => '75',
    ],
  ],

  'part_licenses' => [
    'CC_BY_2' => 'Licensed under CC BY 2.0 and CC BY 4.0 : see CAreadme.txt',
    'CC_BY_4' => 'Licensed under CC BY 4.0 : see CAreadme.txt',
    'CA' => 'Redistributable under CCAL version 2.0 : see CAreadme.txt',
    'NonCA' => 'Not redistributable : see NonCAreadme.txt'
  ],

  // The default license for the libary
  'license' => [
    'default' => 'CC_BY_4',
  ],

  // These are groups for Part Author/Reviewer tags
  'mybb-groups' => [
    'Part Author' => 8,
    'Part Reviewer' => 9,
    'Library Admin' => 10,
  ],
  'search' => [
    'quicksearch' => [
      'limit' => 7,
    ],
  ],
  // \x20-\x7E\p{Latin}\p{Han}\p{Hiragana}\p{Katakana}\pS
  // Match patterns
  'patterns' => [
    'description' => '#^\h*0\h+(?P<description>.*)\h*#u',
    'library_approved_description' => '#^[^\p{C}\p{Zl}\p{Zp}]+$#u',
    'name' => '#^\h*0\h+Name:\h+(?P<name>.*?)\h*$#um',
    'basepart' => '#^([uts]?\d+[a-z]?)(p[0-9a-z]{2,3}|c[0-9a-z]{2}|d[0-9a-z]{2}|k[0-9a-z]{2}|-f[0-9a-z])?\.(dat|png)#u',
    'library_approved_name' => '#^[\\\\a-z0-9_-]+(\.dat|\.png)$#',
    'author' => '#^\h*0\h+Author:(\h+(?P<realname>[^\[\]\r\n]+?))?(\h+\[(?P<user>[a-zA-Z0-9_.-]+)\])?\h*$#um',
    'type' => '#^\h*0\h+!LDRAW_ORG\h+(?P<unofficial>Unofficial_)?(?P<type>###PartTypes###)(\h+(?P<qual>###PartTypesQualifiers###))?(\h+((?P<releasetype>ORIGINAL|UPDATE)(\h+(?P<release>\d{4}-\d{2}))?))?\h*$#um',
    'category' => '#^\h*0\h+!CATEGORY\h+(?P<category>.*?)\h*$#um',
    'license' => '#^\h*0\h+!LICENSE\h+(?P<license>.*?)\h*$#um',
    'help' => '#^\h*0\h+!HELP\h+(?P<help>.*?)\h*$#um',
    'keywords' => '#^\h*0\h+!KEYWORDS\h+(?P<keywords>.*?)\h*$#um',
    'bfc' => '#^\h*0\h+BFC\h+(?P<bfc>CERTIFY|NOCERTIFY|CCW|CW|NOCLIP|CLIP)(?:\h+)?(?P<winding>CCW|CW)?\h*$#um',
    'cmdline' => '#^\h*0\h+!CMDLINE\h+(?P<cmdline>.*?)\h*$#um',
    'history' => '#^\h*0\h+!HISTORY\h+(?P<date>\d\d\d\d-\d\d-\d\d)\h+[\[{](?P<user>[\w\s\/\\.-]+)[}\]]\h+(?P<comment>.*?)\h*$#um',
    'textures' => '#^\h*0\h+!TEXMAP\h+(START|NEXT)\h+(PLANAR|CYLINDRICAL|SPHERICAL)\h+([-\.\d]+\h+){9,11}(?P<texture1>.*?\.png)(\h+GLOSSMAP\h+(?P<texture2>.*?\.png))?\h*$#um',
    'subparts' => '#^\h*(0\h+!\:\h+)?1\h+((0x)?\d+\h+){1}([-\.\d]+\h+){12}(?P<subpart>.*?\.(dat|ldr))\h*$#um',
    'line_type_0' => '#^\h*0(?:\h*)(.*)?\s*$#um',
    'line_type_1' => '#^\h*1\h+(?P<color>0x2[a-fA-F\d]{6}|\d+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+(?P<subpart>[\/a-z0-9_.\\\\-]+)\h*?$#um',
    'line_type_2' => '#^\h*2\h+(?P<color>0x2[a-fA-F\d]{6}|\d+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h*$#um',
    'line_type_3' => '#^\h*3\h+(?P<color>0x2[a-fA-F\d]{6}|\d+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h*$#um',
    'line_type_4' => '#^\h*4\h+(?P<color>0x2[a-fA-F\d]{6}|\d+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h*$#um',
    'line_type_5' => '#^\h*5\h+(?P<color>0x2[a-fA-F\d]{6}|\d+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h*$#um',
  ],

  'allowed_metas' => [
    'header' => [
      'Name:', 
      'Author:', 
      '!LDRAW_ORG', 
      '!LICENSE', 
      '!HELP', 
      'BFC', 
      '!CATEGORY', 
      '!KEYWORDS', 
      '!CMDLINE', 
      '!HISTORY'  
    ],
    'body' => [
      '!TEXMAP', 
      '!:', 
      'BFC', 
      '//',
    ],
  ],

  
  // !LDRAW_ORG Part types
  'part_types' => [
    'Part' => ['name' => 'Part', 'folder' => 'parts/', 'format' => 'dat'],
    'Subpart' => ['name' => 'Subpart', 'folder' => 'parts/s/', 'format' => 'dat'],
    'Primitive' => ['name' => 'Primitive', 'folder' => 'p/', 'format' => 'dat'],
    '8_Primitive' => ['name' => '8 Segment Primitive', 'folder' => 'p/8/', 'format' => 'dat'],
    '48_Primitive' => ['name' => '48 Segment Primitive', 'folder' => 'p/48/', 'format' => 'dat'],
    'Shortcut' => ['name' => 'Shortcut', 'folder' => 'parts/', 'format' => 'dat'],
    'Helper' => ['name' => 'Helper', 'folder' => 'parts/h/', 'format' => 'dat'],
    'Part_Texmap' => ['name' => 'TEXMAP Image', 'folder' => 'parts/textures/', 'format' => 'png'],
    'Subpart_Texmap' => ['name' => 'Subpart TEXMAP Image', 'folder' => 'parts/textures/s/', 'format' => 'png'],
    'Primitive_Texmap' => ['name' => 'Primitive TEXMAP Image', 'folder' => 'p/textures/', 'format' => 'png'],
    '8_Primitive_Texmap' => ['name' => '8 Segment Primitive TEXMAP Image', 'folder' => 'p/textures/8/', 'format' => 'png'],
    '48_Primitive_Texmap' => ['name' => '48 Segment Primitive TEXMAP Image', 'folder' => 'p/textures/48/', 'format' => 'png'],  
  ],
  
  'part_type_qualifiers' => [
    'Alias' => 'Alias',
    'Physical_Colour' => 'Physical Colour',
    'Flexible_Section' => 'Flexible Section'
  ],
  
  'known_author_aliases' => [
    'The LEGO Universe Team' => 'LEGO Universe Team',
    'simlego' => 'Tore_Eriksson',
    'Valemar' => 'rhsexton',
  ],

  // Valid part categories
  'categories' => [
    'Animal',
    'Antenna',
    'Arch',
    'Arm',
    'Bar',
    'Baseplate',
    'Belville',
    'Boat',
    'Bracket',
    'Brick',
    'Car',
    'Clikits',
    'Cockpit',
    'Cone',
    'Constraction',
    'Constraction Accessory',
    'Container',
    'Conveyor',
    'Crane',
    'Cylinder',
    'Dish',
    'Door',
    'Duplo',
    'Electric',
    'Exhaust',
    'Fence',
    'Figure',
    'Figure Accessory',
    'Flag',
    'Forklift',
    'Freestyle',
    'Garage',
    'Glass',
    'Grab',
    'Helper',
    'Hinge',
    'Homemaker',
    'Hose',
    'Ladder',
    'Lever',
    'Magnet',
    'Minifig',
    'Minifig Accessory',
    'Minifig Footwear',
    'Minifig Headwear',
    'Minifig Hipwear',
    'Minifig Neckwear',
    'Monorail',
    'Moved',
    'Obsolete',
    'Panel',
    'Plane',
    'Plant',
    'Plate',
    'Platform',
    'Pov-RAY',
    'Propeller',
    'Rack',
    'Roadsign',
    'Rock',
    'Scala',
    'Screw',
    'Sheet Cardboard',
    'Sheet Fabric',
    'Sheet Plastic',
    'Slope',
    'Sphere',
    'Staircase',
    'Sticker',
    'String',
    'Support',
    'Tail',
    'Tap',
    'Technic',
    'Tile',
    'Tipper',
    'Tractor',
    'Trailer',
    'Train',
    'Turntable',
    'Tyre',
    'Vehicle',
    'Wedge',
    'Wheel',
    'Winch',
    'Window',
    'Windscreen',
    'Wing',
    'Znap',
  ],

  'pattern-codes' => [
    '0' => 'General/Miscellaneous and Town',
    '1' => 'Town, including Paradisa',
    '2' => 'Town, including Paradisa',
    '3' => 'Pirates, Soldiers, Islanders',
    '4' => 'Castle',
    '5' => 'Space',
    '6' => 'Space',
    '7' => 'Modern Town',
    '8' => 'Modern Town',
    '9' => 'Modern Town',
    'a' => 'Action (Adventurers, Aquazone, Alpha Team, Rock Raiders)',
    'b' => 'Superheroes',
    'c' => 'Control Panels, dials, gauges, keyboards, readouts, etc.) or Superheroes for Minifig Parts',
    'c0' => 'Collectable Minifigures from accessory packs',
    'c1' => 'Collectable Minifigures Series 1',
    'c2' => 'Collectable Minifigures Series 2',
    'c3' => 'Collectable Minifigures Series 3',
    'c4' => 'Collectable Minifigures Series 4',
    'c5' => 'Collectable Minifigures Series 5',
    'c6' => 'Collectable Minifigures Series 6',
    'c7' => 'Collectable Minifigures Series 7',
    'c8' => 'Collectable Minifigures Series 8',
    'c9' => 'Collectable Minifigures Series 9',
    'ca' => 'Collectable Minifigures Series 10',
    'cb' => 'Collectable Minifigures Series 11',
    'cc' => 'Collectable Minifigures Series 12',
    'cd' => 'Collectable Minifigures Series 13',
    'ce' => 'Collectable Minifigures Series 14',
    'cf' => 'Collectable Minifigures Series 15',
    'cg' => 'Collectable Minifigures Series 16',
    'ch' => 'Collectable Minifigures Series 17',
    'ci' => 'Collectable Minifigures Series 18',
    'cj' => 'Collectable Minifigures Series 19',
    'ck' => 'Collectable Minifigures Series 20',
    'cl' => 'Collectable Minifigures Series 21',
    'cm' => 'Collectable Minifigures Series 22',
    'cn' => 'Collectable Minifigures Series 23',
    'co' => 'Collectable Minifigures Series 24',
    'cp' => 'Collectable Minifigures Series 25',
    'cq' => 'Collectable Minifigures Series 26',
    'cr' => 'Collectable Minifigures Series 27',
    'cs' => 'Collectable Minifigures Series 28',
    'ct' => 'Collectable Minifigures Series 29',
    'cu' => 'Collectable Minifigures Series 30',
    'cv' => 'Collectable Minifigures Series 31',
    'cw' => 'Collectable Minifigures Series 32',
    'cx' => 'Collectable Minifigures Series 33',
    'cy' => 'Collectable Minifigures Series 34',
    'cz' => 'Collectable Minifigures Series 35',
    'd' => 'Studios',
    'd0' => 'Collectable Minifigures 2012 Team GB',
    'd1' => 'Collectable Minifigures Simpsons Series 1',
    'd2' => 'Collectable Minifigures The LEGO Movie',
    'd3' => 'Collectable Minifigures Simpsons Series 2',
    'd4' => 'Collectable Minifigures Disney Series 1',
    'd5' => 'Collectable Minifigures 2016 German Football Team',
    'd6' => 'Collectable The LEGO Batman Movie Series 1',
    'd7' => 'Collectable The LEGO Ninjago Movie',
    'd8' => 'Collectable The LEGO Batman Movie Series 2',
    'd9' => 'Collectable Minifigures Wizarding World',
    'da' => 'Collectable Minifigures The LEGO Movie 2',
    'db' => 'Collectable Minifigures Disney Series 2',
    'e' => 'Nexo Knights',
    'f' => 'Fabuland, Scala, or Castle (minifig parts)',
    'g' => 'Soccer, Basketball',
    'h' => 'Harry Potter',
    'i' => 'Unused',
    'j' => 'Indiana Jones',
    'k' => 'Cars (Disney Pixar)',
    'l' => 'Unused',
    'm' => 'Middle Earth (Lord of the Rings), Elves',
    'n' => 'Ninja',
    'o' => 'Unused',
    'p' => 'Reserved',
    'q' => 'Pharaoh\'s Quest',
    'r' => 'Star Wars',
    's' => 'Star Wars',
    't' => 'General Textual Patterns (lettering and numbers) and Trademark items (Corporate Logos, etc)',
    'u' => 'Extended textual patterns or Modern Town/City (minifig parts)',
    'v' => 'Extended textual patterns',
    'w' => 'Extended textual patterns or Western (minifig parts)',
    'x' => 'Miscellaneous Licenses (SpongeBob SquarePants, Ideas)',
    'y' => 'Racing (Racers, Tiny Turbos, Speed Champions)',
    'z' => 'Brickheadz',
  ],  
  
];  
