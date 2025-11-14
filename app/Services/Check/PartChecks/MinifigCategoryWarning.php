<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Enums\PartCategory;
use App\Services\Check\BaseCheck;
use App\Services\Check\Traits\PartOnly;

class MinifigCategoryWarning extends BaseCheck
{
    use PartOnly;

    public function check(): iterable
    {
        if ($this->part->rawPart()->isUnofficial() && $this->part->type()?->inPartsFolder() && $this->part->category() == PartCategory::Minifig) {
            yield $this->error(CheckType::Warning, PartError::WarningMinifigCategory);
        }
    }
}