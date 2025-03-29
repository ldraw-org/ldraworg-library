<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum ExternalSite: string
{
    case Rebrickable = 'rebrickable';
    case BrickOwl = 'brickowl';
    case BrickLink = 'bricklink';
    case Brickset = 'brickset';

    public function url(?string $number): ?string
    {
        if (is_null($number)) {
            return null;
        }
        $urlStart = config("ldraw.external_sites.{$this->value}");
        return is_null($urlStart) ? null : $urlStart . $number;
    }
}
