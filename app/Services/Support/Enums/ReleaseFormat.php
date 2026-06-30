<?php

namespace App\Services\Support\Enums;

enum ReleaseFormat: string
{
    case Arj = 'ARJ';
    case Zip = 'ZIP';


    public function completeFile(): string
    {
        return match ($this) {
            self::Arj => 'updates/complete.exe',
            self::Zip => 'updates/complete.zip',
        };
    }

    public function baseFile(): string
    {
        return match ($this) {
            self::Arj => 'updates/ldraw027.exe',
            self::Zip => 'updates/ldraw027.zip',
        };
    }
}
