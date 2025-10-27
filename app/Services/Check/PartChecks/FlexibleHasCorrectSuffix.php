<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Enums\PartTypeQualifier;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;
use Illuminate\Support\Arr;

class FlexibleHasCorrectSuffix implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        $nameRaw = $part->nameRaw();
        $category = $part->category();
        dump(Arr::get($nameRaw, 'k'), is_null(Arr::get($nameRaw, 'k')));
        if ($part->type_qualifier() == PartTypeQualifier::FlexibleSection && $category?->isActive() &&
            is_null(Arr::get($nameRaw, 'k'))
        ) {
            $message(PartError::FlexSectionIncorrectSuffix);
        }
    }
}
