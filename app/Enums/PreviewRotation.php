<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum PreviewRotation: string implements HasLabel {
    case Default = '1 0 0 0 1 0 0 0 1';
    case XNegative90 = '1 0 0 0 0 1 0 -1 0';
    case XPositive90 = '1 0 0 0 0 -1 0 1 0';
    case X180 = '1 0 0 0 -1 0 0 0 -1';
    case YNegative90 = '0 0 1 0 1 0 -1 0 0';
    case YPositive90 = '0 0 -1 0 1 0 1 0 0';
    case Y180 = '-1 0 0 0 1 0 0 0 -1';
    case ZNegative90 = '0 -1 0 1 0 0 0 0 1';
    case ZPositive90 = '0 1 0 -1 0 0 0 0 1';
    case Z180 = '-1 0 0 0 -1 0 0 0 1';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Default => 'Default (No Rotation)',
            self::XNegative90 => 'X -90°',
            self::XPositive90 => 'X +90°',
            self::X180 => 'X 180°',
            self::YNegative90 => 'Y -90°',
            self::YPositive90 => 'Y +90°',
            self::Y180 => 'Y 180°',
            self::ZNegative90 => 'Z -90°',
            self::ZPositive90 => 'Z +90°',
            self::Z180 => 'Z 180°',
        };
    }

    protected function previewString(): string
    {
        return "16 0 0 0 {$this->value}";
    }

    public function commandString(): string
    {
        return "1 {$this->previewString()}";
    }

    public function ldrawString(): string {
        if ($this === self::Default) {
            return '';
        }
        return "0 !PREVIEW {$this->previewString()}";
    }
}
