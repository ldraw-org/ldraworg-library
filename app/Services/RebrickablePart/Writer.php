<?php

namespace App\Services\RebrickablePart;

use App\Models\Part\Part;
use App\Models\RebrickablePart;

class Writer
{
    /**
     * Finds the imported Rebrickable part the way the API's ldraw_id lookup does: a part mapped to the
     * LDraw number (the same number first, then the lowest), else a part with the same number.
     */
    public function findForPart(Part $part): ?RebrickablePart
    {
        $partNum = basename($part->filename, '.dat');

        $mapped = RebrickablePart::where('is_local', false)
            ->whereJsonContains('ldraw', $partNum)
            ->get();

        return $mapped->firstWhere('number', $partNum)
            ?? $mapped->sortBy('number', SORT_STRING)->first()
            ?? RebrickablePart::where('is_local', false)->firstWhere('number', $partNum);
    }

    /**
     * Links the part to its Rebrickable part, leaving the existing link alone when nothing matches.
     */
    public function linkPart(Part $part): ?RebrickablePart
    {
        $rb = $this->findForPart($part);

        if (is_null($rb)) {
            return null;
        }

        $part->rebrickable_part()->associate($rb);
        $part->save();

        return $rb;
    }
}
