<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartCategory;
use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use App\Services\LDraw\Parse\Parser;
use App\Models\Part\Part;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PatternPartDescription implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if (!$part->type()?->inPartsFolder() || is_null(Arr::get($part->nameRaw(), 'p'))) {
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
