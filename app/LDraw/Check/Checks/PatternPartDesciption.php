<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartCategory;
use App\Enums\PartError;
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
            $cat = $part->category;
            $keywords = $part->keywords->pluck('keyword');
        } else {
            if (!app(Parser::class)->patternName($part->name)) {
                return;
            }
            $cat = $part->metaCategory ?? $part->descriptionCategory;
            $keywords = collect($part->keywords);
        }

        $notExcludedCategory = !in_array($cat, [PartCategory::Moved, PartCategory::Sticker, PartCategory::StickerShortcut]);
        $doesntHavePattern = preg_match('#^.*?\h+Pattern(?:\h+\(.*\))?$#ui', $part->description, $matches) < 1;
        $doesntHavekeyword = is_null($keywords->first(fn (string $kw) => Str::of($kw)->lower()->startsWith('colour combination')));
        if ($notExcludedCategory && $doesntHavePattern && $doesntHavekeyword) {
            $fail(PartError::PatternNotInDescription);
        }
    }
}
