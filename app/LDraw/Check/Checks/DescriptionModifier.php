<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartCategory;
use App\Enums\PartError;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;
use Illuminate\Support\Str;

class DescriptionModifier implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part->type == PartType::Subpart && !Str::startsWith($part->description, '~')) {
            $fail(PartError::NoTildeForSubpart);
            return;
        }

        if (!$part->type->inPartsFolder()) {
            return;
        }
        if ($part instanceof Part) {
            $cat = $part->category;
        } else {
            $cat = $part->metaCategory ?? $part->descriptionCategory;
        }
        if (($cat == PartCategory::Moved || $cat == PartCategory::Obsolete || Str::endsWith($part->description, '(Obsolete)')) && !Str::startsWith($part->description, '~')) {
            $fail(PartError::NoTildeForMovedObsolete);
            return;
        }
        if ($part->type_qualifier == PartTypeQualifier::Alias && !Str::startsWith($part->description, '=')) {
            $fail(PartError::NoEqualsForAlias);
            return;
        }
        if ($part instanceof Part) {
            $name = basename($part->filename);
        } else {
            $name = basename(str_replace('\\', '/', $part->name));
        }
        if (Str::startsWith($name, 't') &&
            !Str::startsWith($part->description, '|')) {
            $fail(PartError::NoPipeForThirdParty);
        }

    }
}
