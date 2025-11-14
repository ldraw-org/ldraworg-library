<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartCategory;
use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;
use Illuminate\Support\Str;

class PatternHasSetKeyword extends BaseCheck
{
    public function check(): iterable
    {
        if (!$this->part->type()?->inPartsFolder() || $this->part->category() == PartCategory::Modulex || $this->part->category() == PartCategory::Moved) {
            return;
        }
      
        $isPattern = $this->part->isPattern();
        $noKeyword = collect($this->part->keywords())
          ->doesntContain(fn (string $keyword) => Str::of($keyword)->lower()->startsWith(['set ', 'cmf', 'build-a-minifigure']));
        if ($isPattern && $noKeyword) {
            yield $this->error(CheckType::Error, PartError::NoSetKeywordForPattern);
        }
    }
}
