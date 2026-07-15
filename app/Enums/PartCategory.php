<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;
use Filament\Support\Contracts\HasLabel;

enum PartCategory: string implements HasLabel
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
    case Freestyle = 'Freestyle';
    case Garage = 'Garage';
    case Glass = 'Glass';
    case Helper = 'Helper';
    case Hinge = 'Hinge';
    case Homemaker = 'Homemaker';
    case Hose = 'Hose';
    case Ladder = 'Ladder';
    case Magnet = 'Magnet';
    case MinifigHead = 'Minifig Head';
    case MinifigUpper = 'Minifig Upper';
    case MinifigLower = 'Minifig Lower';
    case MinifigLeg = 'Minifig Leg';
    case MinifigHips = 'Minifig Hips';
    case MinifigArm = 'Minifig Arm';
    case MinifigHand = 'Minifig Hand';
    case MinifigTorso = 'Minifig Torso';
    case MinifigBody = 'Minifig Body';
    case MinifigAssembly = 'Minifig Assembly';
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

    // Legacy Categoris
    case Minifig = 'Minifig';
    case PovRAY = 'Pov-RAY';
    case Lever = 'Lever';
    case Grab = 'Grab';
    case Forklift = 'Forklift';

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

    public function isSticker(): bool
    {
        return match ($this) {
            self::Sticker,
            self::StickerShortcut => true,
            default => false,
        };
    }

    public function retired(): array
    {
        return [
            self::Minifig,
            self::PovRAY,
            self::Forklift,
            self::Lever,
            self::Grab
        ];
    }

    public function isRetired(): bool
    {
        return in_array($this, $this->retired());
    }

    public function retiredMessage(): string
    {
        return match ($this) {
            self::Minifig => 'use suitable active Minifig category',
            self::Forklift => 'use Vehicle',
            self::PovRAY,
            self::Lever,
            self::Grab => 'no replacement',
            default => ''
        };
    }

    public function customLabel(): string
    {
        return $this->value;
    }
}
