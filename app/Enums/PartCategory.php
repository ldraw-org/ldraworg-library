<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;

enum PartCategory: string
{
    use CanBeOption;

    case Animal = 'Animal';
    case Antenna = 'Antenna';
    case Arch = 'Arch';
    case Arm = 'Arm';
    case Bar = 'Bar';
    case Baseplate = 'Baseplate';
    case Belville = 'Belville';
    case Boat = 'Boat';
    case Bracket = 'Bracket';
    case Brick = 'Brick';
    case Car = 'Car';
    case Clikits = 'Clikits';
    case Cockpit = 'Cockpit';
    case Cone = 'Cone';
    case Constraction = 'Constraction';
    case ConstractionAccessory = 'Constraction Accessory';
    case Container = 'Container';
    case Conveyor = 'Conveyor';
    case Crane = 'Crane';
    case Cylinder = 'Cylinder';
    case Dish = 'Dish';
    case Door = 'Door';
    case Duplo = 'Duplo';
    case Electric = 'Electric';
    case Exhaust = 'Exhaust';
    case Fence = 'Fence';
    case Figure = 'Figure';
    case FigureAccessory = 'Figure Accessory';
    case Flag = 'Flag';
    case Flexible = 'Flexible';
    case Forklift = 'Forklift';
    case Freestyle = 'Freestyle';
    case Garage = 'Garage';
    case Glass = 'Glass';
    case Grab = 'Grab';
    case Helper = 'Helper';
    case Hinge = 'Hinge';
    case Homemaker = 'Homemaker';
    case Hose = 'Hose';
    case Ladder = 'Ladder';
    case Lever = 'Lever';
    case Magnet = 'Magnet';
    case Minifig = 'Minifig';
    case MinifigAccessory = 'Minifig Accessory';
    case MinifigFootwear = 'Minifig Footwear';
    case MinifigHeadwear = 'Minifig Headwear';
    case MinifigHipwear = 'Minifig Hipwear';
    case MinifigNeckwear = 'Minifig Neckwear';
    case Monorail = 'Monorail';
    case Modulex = 'Modulex';
    case Moved = 'Moved';
    case Mursten = 'Mursten';
    case Obsolete = 'Obsolete';
    case Panel = 'Panel';
    case Plane = 'Plane';
    case Plant = 'Plant';
    case Plate = 'Plate';
    case Platform = 'Platform';
    case PovRAY = 'Pov-RAY';
    case Propeller = 'Propeller';
    case Quatro = 'Quatro';
    case Rack = 'Rack';
    case Roadsign = 'Roadsign';
    case Rock = 'Rock';
    case Scala = 'Scala';
    case Screw = 'Screw';
    case SheetCardboard = 'Sheet Cardboard';
    case SheetFabric = 'Sheet Fabric';
    case SheetPlastic = 'Sheet Plastic';
    case Slope = 'Slope';
    case Sphere = 'Sphere';
    case Staircase = 'Staircase';
    case Sticker = 'Sticker';
    case StickerShortcut = 'Sticker Shortcut';
    case String = 'String';
    case Support = 'Support';
    case Tail = 'Tail';
    case Tap = 'Tap';
    case Technic = 'Technic';
    case Tile = 'Tile';
    case Tipper = 'Tipper';
    case Tractor = 'Tractor';
    case Trailer = 'Trailer';
    case Train = 'Train';
    case Turntable = 'Turntable';
    case Tyre = 'Tyre';
    case Vehicle = 'Vehicle';
    case Wedge = 'Wedge';
    case Wheel = 'Wheel';
    case Winch = 'Winch';
    case Window = 'Window';
    case Windscreen = 'Windscreen';
    case Wing = 'Wing';
    case Znap = 'Znap';

    public function ldrawString(): string
    {
        return "0 !CATEGORY {$this->value}";
    }

    public static function inactiveCategories(): array
    {
        return [PartCategory::Moved, PartCategory::Obsolete];
    }

    public function isActive(): bool
    {
        return !in_array($this, $this->inactiveCategories());
    }

    public function isInactive(): bool
    {
        return in_array($this, $this->inactiveCategories());
    }

}
