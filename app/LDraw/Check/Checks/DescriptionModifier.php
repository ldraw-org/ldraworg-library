<?php

namespace App\LDraw\Check\Checks;

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
            $fail(__('partcheck.description.subpartdesc'));
            return;
        }

        if (!$part->type->inPartsFolder()) {
            return;
        }
        if ($part instanceof Part) {
            $cat = $part->category->category;
        } else {
            $cat = $part->metaCategory ?? $part->descriptionCategory;
        }
        if (($cat == 'Moved' || $cat == 'Obsolete' || Str::endsWith($part->description, '(Obsolete)')) && !Str::startsWith($part->description, '~')) {
            $fail(__('partcheck.description.movedorobsolete'));
            return;
        }
        if ($part->type_qualifier == PartTypeQualifier::Alias && !Str::startsWith($part->description, '=')) {
            $fail(__('partcheck.description.aliasdesc'));
            return;
        }
        if ($part instanceof Part) {
            $name = basename($part->filename);
        } else {
            $name = basename(str_replace('\\', '/', $part->name));
        }
        if ($part->type == PartType::partsFolderTypes() &&
            Str::startsWith($name, 't') &&
            !Str::startsWith($part->description, '|')) {
            $fail(__('partcheck.description.thirdpartydesc'));
        }

    }
}
