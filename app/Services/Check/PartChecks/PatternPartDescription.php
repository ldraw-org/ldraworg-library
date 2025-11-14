<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartCategory;
use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;

class PatternPartDescription extends BaseCheck
{
    public function check(): iterable
    {
        if (!$this->part->type()?->inPartsFolder() || !$this->part->isPattern()) {
            return;
        }

        $notExcludedCategory = !in_array($this->part->category(), [PartCategory::Moved, PartCategory::Sticker, PartCategory::StickerShortcut]);
        $hasPattern = preg_match('~Pattern(?:\h+\(.*\))?$~ui', $this->part->description(), $matches);
        $doesntHavekeyword = !in_array('Colour Combination', $this->part->keywords());
        if ($notExcludedCategory && !$hasPattern && $doesntHavekeyword) {
            yield $this->error(CheckType::Error, PartError::PatternNotInDescription);
        }
    }
}
