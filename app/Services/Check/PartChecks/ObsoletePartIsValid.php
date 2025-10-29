<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartCategory;
use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use App\Models\Part\Part;
use Closure;
use Illuminate\Support\Str;

class ObsoletePartIsValid implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if (!$part->type()?->inPartsFolder()) {
            return;
        }

        $desc = Str::of($part->description());
        $descObsolete = $desc->contains('(Obsolete)') || $desc->startsWith('~Obsolete');
        $catObsolete = $part->category() == PartCategory::Obsolete;
        if (($catObsolete && !$descObsolete) || (!$catObsolete && $descObsolete)) {
            $message(PartError::ImproperObsolete);
        }
    }
}
