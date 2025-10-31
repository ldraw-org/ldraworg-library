<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Enums\PartCategory;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;

class StickerColorWarning implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if ($part->type()?->inPartsFolder() && $part->category() == PartCategory::StickerShortcut) {
            $part->where('linetype', 1)
                ->each(function (array $line) use ($message) {
                    if ($line['color'] != 16) {
                        $message(PartError::WarningStickerColor);
                        return false;
                    }
                });
        }
    }
}