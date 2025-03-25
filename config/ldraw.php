<?php

// LDraw Config Values

return [
    // The library rebrickable API key
    'rebrickable_api_key' => env('REBRICKABLE_API_KEY'),

    // LDView debug writting to logs
    'ldview_debug' => env('LDVIEW_DEBUG', false),


    // These are groups for Part Author/Reviewer tags
    'mybb-groups' => [
        'Administrators' => 4,
        'Part Author' => 8,
        'Part Reviewer' => 9,
        'Library Admin' => 10,
        'LDraw Member' => 21,
        'Registered' => 11,
    ],

    // External Site URL Stubs
    'external_sites' => [
        'BrickLink' => 'https://www.bricklink.com/v2/catalog/catalogitem.page?P=',
        'Rebrickable' => 'https://rebrickable.com/parts/',
        'BrickOwl' => 'https://www.brickowl.com/catalog/',
        'Brickset' => 'https://brickset.com/parts/design-'
    ],

    // Match patterns
    'patterns' => [
        'description' => '#^\h*0\h+(?P<description>.*)\h*#u',
        'library_approved_description' => '#^[^\p{C}\p{Zl}\p{Zp}]+$#u',
        'name' => '#^\h*0\h+Name:\h+(?P<name>.*?)\h*$#um',
        'basepart' => '#^((?:[uts]?\d+[a-z]?[0-9a-z]?)(?:p[0-9a-z]{2,3}|[cdk][0-9a-z]{2}|[pcd][0-9]{4})*?)(?:p[0-9a-z]{2,3}|[cdk][0-9a-z]{2}|-f[0-9a-z]|[pcd][0-9]{4})?\.(?:dat|png)$#u',
        'library_approved_name' => '#^[\\\\a-z0-9_-]+(\.dat|\.png)$#',
        'author' => '#^\h*0\h+Author:(\h+(?P<realname>[^\[\]\r\n]+?))?(\h+\[(?P<user>[a-zA-Z0-9_.-]+)\])?\h*$#um',
        'type' => '#^\h*0\h+!LDRAW_ORG\h+(?P<unofficial>Unofficial_)?(?P<type>###PartTypes###)(\h+(?P<qual>###PartTypesQualifiers###))?(\h+((?P<releasetype>ORIGINAL|UPDATE)(\h+(?P<release>\d{4}-\d{2}))?))?\h*$#um',
        'category' => '#^\h*0\h+!CATEGORY\h+(?P<category>.*?)\h*$#um',
        'license' => '#^\h*0\h+!LICENSE\h+(?P<license>.*?)\h*$#um',
        'help' => '#^\h*0\h+!HELP\h+(?P<help>.*?)\h*$#um',
        'keywords' => '#^\h*0\h+!KEYWORDS\h+(?P<keywords>.*?)\h*$#um',
        'bfc' => '#^\h*0\h+BFC\h+(?P<bfc>CERTIFY|NOCERTIFY|CCW|CW|NOCLIP|CLIP)(?:\h+)?(?P<winding>CCW|CW)?\h*$#um',
        'cmdline' => '#^\h*0\h+!CMDLINE\h+(?P<cmdline>.*?)\h*$#um',
        'preview' => '#^\h*0\h+!PREVIEW\h+(?P<preview>.*?)\h*$#um',
        'history' => '#^\h*0\h+!HISTORY\h+(?P<date>\d\d\d\d-\d\d-\d\d)\h+[\[{](?P<user>[\w\s\/\\.-]+)[}\]]\h+(?P<comment>.*?)\h*$#um',
        'textures' => '#^\h*0\h+!TEXMAP\h+(START|NEXT)\h+(PLANAR|CYLINDRICAL|SPHERICAL)\h+([-\.\d]+\h+){9,11}(?P<texture1>.*?\.png)(\h+GLOSSMAP\h+(?P<texture2>.*?\.png))?\h*$#um',
        'subparts' => '#^\h*(0\h+!\:\h+)?1\h+((0x)?\d+\h+){1}([-\.\d]+\h+){12}(?P<subpart>.*?\.(dat|ldr))\h*$#um',
        'colour' => '/^\h*0\h+!COLOUR\h+(?<name>[A-Za-z_]+)\h+CODE\h+(?<code>\d+)\h+VALUE\h+(?<value>(?:#|0x)[A-Fa-f\d]{6})\h+EDGE\h+(?<edge>\d+|(?:#|0x)[A-Fa-f\d]{6})(?:\h+ALPHA\h+(?<alpha>\d{1,3}))?(?:\h+LUMINANCE\h+(?<luminance>\d+))?(?:\h+(?<material>CHROME|METAL|PEARLESCENT|RUBBER|MATERIAL\h+.*))?\h*$/um',
        'colour_material' => '/^\h*MATERIAL\h+(?<type>SPECKLE|GLITTER)\h+VALUE\h+(?<value>#[a-fA-F0-9]{6})(?:\h+ALPHA\h+(?<alpha>[0-9]{1,3}))?(?:\h+LUMINANCE\h+(?<luminance>[0-9]{1,3}))?(?:\h+FRACTION\h+(?<fraction>[0-9.]+))(?:\h+VFRACTION\h+(?<vfraction>[0-9.]+))?(?:\h+SIZE\h+(?<size>[0-9.]+))?(?:\h+MINSIZE\h+(?<minsize>[0-9.]+))?(?:\h+MAXSIZE\h+(?<maxsize>[0-9.]+))?\h*$/u',
        'line_type_0' => '#^\h*0(?:\h*)(.*)?\s*$#um',
        'line_type_1' => '#^\h*1\h+(?P<color>0x2[a-fA-F\d]{6}|\d+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+(?P<subpart>[a-z0-9_.\\\\-]+)\h*?$#um',
        'line_type_2' => '#^\h*2\h+(?P<color>0x2[a-fA-F\d]{6}|\d+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h*$#um',
        'line_type_3' => '#^\h*3\h+(?P<color>0x2[a-fA-F\d]{6}|\d+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h*$#um',
        'line_type_4' => '#^\h*4\h+(?P<color>0x2[a-fA-F\d]{6}|\d+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h*$#um',
        'line_type_5' => '#^\h*5\h+(?P<color>0x2[a-fA-F\d]{6}|\d+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h+([\d.-]+)\h*$#um',
        'base' => '#^(?P<base>[0-9a-z]+?)(?P<suffix1>p[0-9a-z][0-9a-z]|p[cd][1-9a-z]{2}|[cdk][0-9a-z]{2}|[pcd]\d{4})?(?P<suffix2>p[0-9a-z][0-9a-z]|p[cd][1-9a-z]{2}|[cd][0-9a-z]{2}|[pcd]\d{4})?(?P<suffix3>p[0-9a-z][0-9a-z]|p[cd][1-9a-z]{2}|[cd][0-9a-z]{2}|[pcd]\d{4})?(?:-f\d)?\.(?:dat|png)$#i',
    ],

    'check' => [
        'max_point_angle' => 179.9,
        'min_point_angle' => 0.025,
        'coplaner_angle' => 3.0,
    ],

];
