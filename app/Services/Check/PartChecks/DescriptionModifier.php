<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartCategory;
use App\Enums\CheckType;
use App\Enums\PartError;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Services\Check\BaseCheck;
use Illuminate\Support\Str;

class DescriptionModifier extends BaseCheck
{
    public function check(): iterable
    {
        $prefix = $this->part->descriptionPrefix() ?? '';
        $type = $this->part->type();
        if ($type == PartType::Subpart && !Str::startsWith($prefix, '~')) {
            yield $this->error(CheckType::Error, PartError::NoTildeForSubpart);
            return;
        }

        if (!$type?->inPartsFolder()) {
            return;
        }
                       
        if ($this->part->category()?->isInactive() && !Str::startsWith($prefix, '~')) {
            yield $this->error(CheckType::Error, PartError::NoTildeForMovedObsolete);
            return;
        }
        if ($this->part->type_qualifier() == PartTypeQualifier::Alias && !Str::contains($prefix, '=')) {
            yield $this->error(CheckType::Error, PartError::NoEqualsForAlias);
            return;
        }
            
        $name = basename($this->part->name());

        if (Str::startsWith($name, 't') &&
            !Str::contains($prefix, '|')) {
            yield $this->error(CheckType::Error, PartError::NoPipeForThirdParty);
        }

    }
}
