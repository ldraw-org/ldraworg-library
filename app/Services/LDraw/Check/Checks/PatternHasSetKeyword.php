<?php

namespace App\Services\LDraw\Check\Checks;

use App\Enums\PartCategory;
use App\Enums\PartError;
use App\Services\LDraw\Check\Contracts\Check;
use App\Services\LDraw\Parse\ParsedPart;
use App\Services\LDraw\Parse\Parser;
use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PatternHasSetKeyword implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if (!$part->type->inPartsFolder()) {
            return;
        }
        if ($part instanceof Part) {
            if ($part->is_pattern !== true || $part->category == PartCategory::Modulex) {
                return;
            }
            $cat = $part->category;
            $hasSetKw = $part
                ->keywords
                ->filter(fn (PartKeyword $kw): bool => Str::startsWith(Str::lower($kw->keyword), ['set ', 'cmf', 'build-a-minifigure']))
                ->isNotEmpty();
        } else {
            $cat = $part->metaCategory ?? $part->descriptionCategory;
            if (!app(Parser::class)->patternName($part->name) || $cat == PartCategory::Modulex) {
                return;
            }
            $hasSetKw = count(Arr::reject(
                $part->keywords ?? [],
                fn (string $kw) => !Str::startsWith(Str::lower($kw), ['set ', 'cmf', 'build-a-minifigure'])
            )) > 0;
        }

        if ($cat != PartCategory::Moved && ! $hasSetKw) {
            $fail(PartError::NoSetKeywordForPattern);
        }

    }
}
