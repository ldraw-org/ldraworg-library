<?php

namespace App\Enums;

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

    public static function prefixes(): array
    {
        return array_map(
            fn (self $site) => strtolower($site->value),
            self::cases()
        );
    }

    public function keywordPrefix(): string
    {
        return strtolower($this->value) . ' ';
    }
}
