<?php

namespace App\LDraw\Check\Checks;

use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\LDraw\Parse\Parser;
use App\Models\Part\Part;
use Closure;
use Illuminate\Support\Str;

class PatternPartDesciption implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if (!$part->type->inPartsFolder()) {
            return;
        }
        if ($part instanceof Part) {
            if ($part->is_pattern !== true) {
                return;
            }
            $cat = $part->category->category;
        } else {
            if (!app(Parser::class)->patternName($part->name)) {
                return;
            }
            $cat = $part->metaCategory ?? $part->descriptionCategory;
        }

        if (! in_array($cat, ['Moved', 'Sticker', 'Sticker Shortcut']) &&
            ! preg_match('#^.*?\h+Pattern(?:\h+\(.*\))?$#ui', $part->description, $matches)) {
                $fail(__('partcheck.description.patternword'));
        }
    }
}
