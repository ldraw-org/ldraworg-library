<?php

namespace App\Services\Part;

use App\Models\Part\Part;

class SyncFix
{
    public function handle(Part $part): void
    {
        if (Part::where('filename', $part->filename)->count() <= 1) {
            return;
        }
        if ($part->isUnofficial()) {
            $fixpart = Part::official()->firstWhere('filename', $part->filename);
            $fixpart->unofficial_part()->associate($part);
            $fixpart->save();
        } else {
            $fixpart = Part::unofficial()->firstWhere('filename', $part->filename);
            $part->unofficial_part()->associate($fixpart);
            $part->save();
        }
        $part->refresh();
    }
}
