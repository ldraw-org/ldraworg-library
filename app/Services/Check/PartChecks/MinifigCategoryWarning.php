<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Enums\PartCategory;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;

class MinifigCategoryWarning implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if ($part->type()?->inPartsFolder() && $part->category() == PartCategory::Minifig) {
            $message(PartError::WarningMinifigCategory);
        }
    }
}