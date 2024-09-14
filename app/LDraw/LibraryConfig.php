<?php

namespace App\LDraw;

class LibraryConfig
{
    public static function partLicenses(): array
    {
        return [
            ['name' => 'CC_BY_2', 'text' => 'Licensed under CC BY 2.0 and CC BY 4.0 : see CAreadme.txt'],
            ['name' => 'CC_BY_4', 'text' => 'Licensed under CC BY 4.0 : see CAreadme.txt'],
            ['name' => 'CC0', 'text' => 'Marked with CC0 1.0 : see CAreadme.txt'],
            ['name' => 'CA', 'text' => 'Redistributable under CCAL version 2.0 : see CAreadme.txt'],
            ['name' => 'NonCA', 'text' => 'Not redistributable : see NonCAreadme.txt'],
        ];
    }

    public static function partTypes(): array
    {
        return [
            ['type' => 'Part', 'name' => 'Part', 'folder' => 'parts/', 'format' => 'dat'],
            ['type' => 'Subpart', 'name' => 'Subpart', 'folder' => 'parts/s/', 'format' => 'dat'],
            ['type' => 'Primitive', 'name' => 'Primitive', 'folder' => 'p/', 'format' => 'dat'],
            ['type' => '8_Primitive', 'name' => '8 Segment Primitive', 'folder' => 'p/8/', 'format' => 'dat'],
            ['type' => '48_Primitive', 'name' => '48 Segment Primitive', 'folder' => 'p/48/', 'format' => 'dat'],
            ['type' => 'Shortcut', 'name' => 'Shortcut', 'folder' => 'parts/', 'format' => 'dat'],
            ['type' => 'Helper', 'name' => 'Helper', 'folder' => 'parts/helpers/', 'format' => 'dat'],
            ['type' => 'Part_Texmap', 'name' => 'TEXMAP Image', 'folder' => 'parts/textures/', 'format' => 'png'],
            ['type' => 'Subpart_Texmap', 'name' => 'Subpart TEXMAP Image', 'folder' => 'parts/textures/s/', 'format' => 'png'],
            ['type' => 'Primitive_Texmap', 'name' => 'Primitive TEXMAP Image', 'folder' => 'p/textures/', 'format' => 'png'],
            ['type' => '8_Primitive_Texmap', 'name' => '8 Segment Primitive TEXMAP Image', 'folder' => 'p/textures/8/', 'format' => 'png'],
            ['type' => '48_Primitive_Texmap', 'name' => '48 Segment Primitive TEXMAP Image', 'folder' => 'p/textures/48/', 'format' => 'png'],
        ];
    }

    public static function partTypeQualifiers(): array
    {
        return [
            ['type' => 'Alias', 'name' => 'Alias'],
            ['type' => 'Physical_Colour', 'name' => 'Physical Colour'],
            ['type' => 'Flexible_Section', 'name' => 'Flexible Section'],
        ];
    }

    public static function partCategories(): array
    {
        return [
            ['category' => 'Animal'],
            ['category' => 'Antenna'],
            ['category' => 'Arch'],
            ['category' => 'Arm'],
            ['category' => 'Bar'],
            ['category' => 'Baseplate'],
            ['category' => 'Belville'],
            ['category' => 'Boat'],
            ['category' => 'Bracket'],
            ['category' => 'Brick'],
            ['category' => 'Car'],
            ['category' => 'Clikits'],
            ['category' => 'Cockpit'],
            ['category' => 'Cone'],
            ['category' => 'Constraction'],
            ['category' => 'Constraction Accessory'],
            ['category' => 'Container'],
            ['category' => 'Conveyor'],
            ['category' => 'Crane'],
            ['category' => 'Cylinder'],
            ['category' => 'Dish'],
            ['category' => 'Door'],
            ['category' => 'Duplo'],
            ['category' => 'Electric'],
            ['category' => 'Exhaust'],
            ['category' => 'Fence'],
            ['category' => 'Figure'],
            ['category' => 'Figure Accessory'],
            ['category' => 'Flag'],
            ['category' => 'Forklift'],
            ['category' => 'Freestyle'],
            ['category' => 'Garage'],
            ['category' => 'Glass'],
            ['category' => 'Grab'],
            ['category' => 'Helper'],
            ['category' => 'Hinge'],
            ['category' => 'Homemaker'],
            ['category' => 'Hose'],
            ['category' => 'Ladder'],
            ['category' => 'Lever'],
            ['category' => 'Magnet'],
            ['category' => 'Minifig'],
            ['category' => 'Minifig Accessory'],
            ['category' => 'Minifig Footwear'],
            ['category' => 'Minifig Headwear'],
            ['category' => 'Minifig Hipwear'],
            ['category' => 'Minifig Neckwear'],
            ['category' => 'Monorail'],
            ['category' => 'Moved'],
            ['category' => 'Obsolete'],
            ['category' => 'Panel'],
            ['category' => 'Plane'],
            ['category' => 'Plant'],
            ['category' => 'Plate'],
            ['category' => 'Platform'],
            ['category' => 'Pov-RAY'],
            ['category' => 'Propeller'],
            ['category' => 'Rack'],
            ['category' => 'Roadsign'],
            ['category' => 'Rock'],
            ['category' => 'Scala'],
            ['category' => 'Screw'],
            ['category' => 'Sheet Cardboard'],
            ['category' => 'Sheet Fabric'],
            ['category' => 'Sheet Plastic'],
            ['category' => 'Slope'],
            ['category' => 'Sphere'],
            ['category' => 'Staircase'],
            ['category' => 'Sticker'],
            ['category' => 'Sticker Shortcut'],
            ['category' => 'String'],
            ['category' => 'Support'],
            ['category' => 'Tail'],
            ['category' => 'Tap'],
            ['category' => 'Technic'],
            ['category' => 'Tile'],
            ['category' => 'Tipper'],
            ['category' => 'Tractor'],
            ['category' => 'Trailer'],
            ['category' => 'Train'],
            ['category' => 'Turntable'],
            ['category' => 'Tyre'],
            ['category' => 'Vehicle'],
            ['category' => 'Wedge'],
            ['category' => 'Wheel'],
            ['category' => 'Winch'],
            ['category' => 'Window'],
            ['category' => 'Windscreen'],
            ['category' => 'Wing'],
            ['category' => 'Znap'],
        ];
    }

    public static function partEventTypes(): array
    {
        return [
            ['slug' => 'review', 'name' => 'Review'],
            ['slug' => 'submit', 'name' => 'Submit'],
            ['slug' => 'edit', 'name' => 'Header Edit'],
            ['slug' => 'rename', 'name' => 'Rename'],
            ['slug' => 'release', 'name' => 'Release'],
            ['slug' => 'delete', 'name' => 'Delete'],
            ['slug' => 'comment', 'name' => 'Comment'],
        ];
    }

    public static function voteTypes(): array
    {
        return [
            ['code' => 'C', 'short' => 'certify', 'name' => 'Certify', 'phrase' => 'Certify (Yes).  This file is ready for general release.', 'order' => 4],
            ['code' => 'A', 'short' => 'admincertify', 'name' => 'Admin Certify', 'phrase' => 'Admin Certify (Approve).  This file is approved for release.', 'order' => 3],
            ['code' => 'T', 'short' => 'fasttrack', 'name' => 'Admin Fast Track', 'phrase' => 'Fast-track (Yes).  This file is eligible for fast-track review and is approved release.', 'order' => 6],
            ['code' => 'H', 'short' => 'hold', 'name' => 'Hold', 'phrase' => 'Hold (No).  It\'s getting there, but not yet.', 'order' => 5],
            ['code' => 'N', 'short' => 'cancel', 'name' => 'Cancel Vote', 'phrase' => 'Cancel Vote.  This will clear your vote on this part.', 'order' => 2],
            ['code' => 'M', 'short' => 'comment', 'name' => 'Comment', 'phrase' => 'Comment.  Comment on this part without voting or changing your vote.', 'order' => 1],
        ];
    }
}
