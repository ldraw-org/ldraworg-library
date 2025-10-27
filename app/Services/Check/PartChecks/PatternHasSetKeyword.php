<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartCategory;
use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use App\Services\LDraw\Parse\Parser;
use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PatternHasSetKeyword implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if (!$part->type()?->inPartsFolder() || $part->category() == PartCategory::Modulex || $part->category() == PartCategory::Moved) {
            return;
        }
        $isPattern = $part->isPattern();
        $noKeyword = collect($part->keywords())
          ->doesntContain(fn (string $keyword) => Str::of($keyword)->lower()->startsWith(['set ', 'cmf', 'build-a-minifigure']));
        
        if ($isPattern && $noKeyword) {
            $message(PartError::NoSetKeywordForPattern);
        }
    }
}
