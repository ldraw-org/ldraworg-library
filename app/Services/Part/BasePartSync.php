<?php

namespace App\Services\Part;

use App\Enums\PartCategory;
use App\Models\Part\Part;
use App\Services\Parser\ParsedPartCollection;

class BasePartSync
{
    public function syncBasePart(Part $part): void
    {
        if (!$part->type->inPartsFolder() || $part->category == PartCategory::Moved || $part->isObsolete()) {
            return;
        }
        $name = new ParsedPartCollection("0 Name: {$part->meta_name}\n" . $part->type->ldrawString(true));
        $base = $name->basepart();
        if (is_null($base) || ("{$base}.dat" == $part->meta_name || "{$base}-f1.dat" == $part->meta_name)) {
            $part->base_part()->disassociate();
            $part->is_pattern = false;
            $part->is_composite = false;
            $part->save();
            return;
        }

        $bp = Part::doesntHave('official_part')
            ->where(
                fn ($q) => $q
                    ->orWhere('filename', "parts/{$base}.dat")
                    ->orWhere('filename', "parts/{$base}-f1.dat")
            )
            ->first();

        if (!is_null($bp)) {
            $part->base_part()->associate($bp);
        }

        $part->is_pattern = $name->isPattern();
        $part->is_composite = $name->isComposite();
        if ($part->isDirty()) {
            $part->save();
        }
    }

}
