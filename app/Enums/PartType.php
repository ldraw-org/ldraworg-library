<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;

enum PartType: string
{
    use CanBeOption;

    case Part = 'Part';
    case Subpart = 'Subpart';
    case Primitive = 'Primitive';
    case LowResPrimitive = '8_Primitive';
    case HighResPrimitive = '48_Primitive';
    case Shortcut = 'Shortcut';
    case Helper = 'Helper';
    case PartTexmap = 'Part_Texmap';
    case SubpartTexmap = 'Subpart_Texmap';
    case PrimitiveTexmap = 'Primitive_Texmap';
    case LowResPrimitiveTexmap = '8_Primitive_Texmap';
    case HighResPrimitiveTexmap = '48_Primitive_Texmap';

    public function ldrawString(bool $unofficial = false): string
    {
        $u = $unofficial ? 'Unofficial_' : '';
        return "0 !LDRAW_ORG {$u}{$this->value}";
    }

    public function folder(): string
    {
        return match ($this) {
            PartType::Part, PartType::Shortcut => 'parts',
            PartType::Subpart => 'parts/s',
            PartType::Primitive => 'p',
            PartType::LowResPrimitive => 'p/8',
            PartType::HighResPrimitive => 'p/48',
            PartType::Helper => 'parts/helper',
            PartType::PartTexmap => 'parts/textures',
            PartType::SubpartTexmap => 'parts/textures/s',
            PartType::PrimitiveTexmap => 'p/textures',
            PartType::LowResPrimitiveTexmap => 'p/textures/8',
            PartType::HighResPrimitiveTexmap => 'p/textures/48'
        };
    }

    public function description(): string
    {
        return match ($this) {
            PartType::LowResPrimitive => '8 Segment Primitive',
            PartType::HighResPrimitive => '48 Segment Primitive',
            PartType::PartTexmap => 'TEXMAP Image',
            PartType::SubpartTexmap => 'Subpart TEXMAP Image',
            PartType::PrimitiveTexmap => 'Primitve TEXMAP Image',
            PartType::LowResPrimitiveTexmap => '8 Segment Primitive TEXMAP Image',
            PartType::HighResPrimitiveTexmap => '48 Segment Primitive TEXMAP Image',
            default => $this->value
        };
    }

    public function inPartsFolder(): bool
    {
        return in_array($this, self::partsFolderTypes());
    }

    public static function partsFolderTypes(): array
    {
        return [PartType::Part, PartType::Shortcut];
    }

    public static function imageFormat(): array
    {
        return [
            PartType::PartTexmap,
            PartType::SubpartTexmap,
            PartType::PrimitiveTexmap,
            PartType::LowResPrimitiveTexmap,
            PartType::HighResPrimitiveTexmap
        ];
    }

    public static function datFormat(): array
    {
        return [
            PartType::Part,
            PartType::Shortcut,
            PartType::Subpart,
            PartType::Primitive,
            PartType::LowResPrimitive,
            PartType::HighResPrimitive,
            PartType::Helper
        ];
    }

    public function isImageFormat(): bool
    {
        return in_array($this, self::imageFormat());
    }

    public function isDatFormat(): bool
    {
        return in_array($this, self::datFormat());
    }

    public function format(): string
    {
        return $this->isDatFormat() ? 'dat' : 'png';
    }
}
