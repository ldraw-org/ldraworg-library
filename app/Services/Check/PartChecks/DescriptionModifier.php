<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartCategory;
use App\Enums\PartError;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;
use Illuminate\Support\Str;

class DescriptionModifier implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        $prefix = $part->descriptionPrefix() ?? '';
        $type = $part->type();
        if ($type == PartType::Subpart && !Str::startsWith($prefix, '~')) {
            $message(PartError::NoTildeForSubpart);
            return;
        }

        if (!$type?->inPartsFolder()) {
            return;
        }
                       
        if ($part->category()?->isInactive() && !Str::startsWith($prefix, '~')) {
            $message(PartError::NoTildeForMovedObsolete);
            return;
        }
        if ($part->type_qualifier() == PartTypeQualifier::Alias && !Str::contains($prefix, '=')) {
            $message(PartError::NoEqualsForAlias);
            return;
        }
            
        $name = basename($part->name());

        if (Str::startsWith($name, 't') &&
            !Str::contains($prefix, '|')) {
            $message(PartError::NoPipeForThirdParty);
        }

    }
}
