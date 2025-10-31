<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartCategory;
use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;

class PatternPartDescription implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if (!$part->type()?->inPartsFolder() || !$part->isPattern()) {
            return;
        }

        $notExcludedCategory = !in_array($part->category(), [PartCategory::Moved, PartCategory::Sticker, PartCategory::StickerShortcut]);
        $hasPattern = preg_match('~Pattern(?:\h+\(.*\))?$~ui', $part->description(), $matches);
        $doesntHavekeyword = !in_array('Colour Combination', $part->keywords() ?? []);
        if ($notExcludedCategory && !$hasPattern && $doesntHavekeyword) {
            $message(PartError::PatternNotInDescription);
        }
    }
}
