<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Enums\PartCategory;
use App\Services\Check\BaseCheck;

class StickerColorWarning extends BaseCheck
{
    public function check(): iterable
    {
        if ($this->part->type()?->inPartsFolder() && $this->part->category() == PartCategory::StickerShortcut) {
            foreach($this->part->bodyLines()->where('linetype', 1) as $line) {
                if ($line['color'] != 16) {
                    yield $this->error(CheckType::Warning, PartError::WarningStickerColor);
                    break;
                }
            }
       }
    }
}