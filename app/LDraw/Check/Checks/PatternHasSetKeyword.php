<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartTypeQualifier;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
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
            if ($part->is_pattern !== true) {
                return;
            }
            $cat = $part->category->category;
            $hasSetKw = $part
                ->keywords
                ->filter(fn (PartKeyword $kw) => Str::startsWith(Str::lower($kw->keyword), ['set ', 'cmf', 'build-a-minifigure']))
                ->count() > 0;
        } else {
            $result = preg_match(config('ldraw.patterns.base'), basename(str_replace('\\', '/', $part->name)), $matches);
            if (!$result || (!Str::startsWith($matches[5], 'p') && !Str::startsWith($matches[6], 'p') && !Str::startsWith($matches[7], 'p'))) {
                return;
            }
            $cat = $part->metaCategory ?? $part->descriptionCategory;
            $hasSetKw = count(Arr::reject(
                $part->keywords,
                fn (string $kw) => !Str::startsWith(Str::lower($kw), ['set ', 'cmf', 'build-a-minifigure'])
            )) > 0;
        }

        if ($cat != 'Moved' && ! $hasSetKw) {
            $fail(__('partcheck.keywords'));
        }

    }
}
