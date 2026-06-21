<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;

enum License: string
{
    use CanBeOption;

    case CC_BY_4 = 'CC_BY_4';
    case CC_BY_2 = 'CC_BY_2';
    case CC0 = 'CC0';

    public function text(): string
    {
        return match($this) {
            License::CC0 => 'Marked with CC0 1.0 : see CAreadme.txt',
            License::CC_BY_2 => 'Licensed under CC BY 2.0 and CC BY 4.0 : see CAreadme.txt',
            License::CC_BY_4 => 'Licensed under CC BY 4.0 : see CAreadme.txt',
        };
    }

    public function ldrawString(): string
    {
        return "0 !LICENSE {$this->text()}";
    }

    public static function tryFromText(string $text): ?self
    {
        foreach (self::cases() as $lic) {
            /** var License $lic */
            if ($lic->text() == $text) {
                return $lic;
            }
        }
        return null;
    }

    public function customLabel(): ?string
    {
        return match($this) {
            self::CC_BY_4 => 'CC BY 4.0 - Creative Commons Attribution 4.0 International',
            self::CC_BY_2 => 'CC BY 2.0 - Creative Commons Attribution 2.0 Generic',
            self::CC0 => 'CC0 - Creative Commons Zero 1.0 Universal',
        };
    }
}
