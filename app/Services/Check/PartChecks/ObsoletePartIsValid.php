<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartCategory;
use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;
use Illuminate\Support\Str;

class ObsoletePartIsValid extends BaseCheck
{
    public function check(): iterable
    {
        if (!$this->part->type()?->inPartsFolder()) {
            return;
        }

        $desc = Str::of($this->part->description());
        $descObsolete = $desc->contains('(Obsolete)') || $desc->startsWith('~Obsolete');
        $catObsolete = $this->part->category() == PartCategory::Obsolete;
        if (($catObsolete && !$descObsolete) || (!$catObsolete && $descObsolete)) {
            yield $this->error(CheckType::Error, PartError::ImproperObsolete);
        }
    }
}
