<?php

namespace App\Observers;

use App\Models\Part\Part;
use App\Models\Part\PartBody;
use Illuminate\Support\Facades\Log;

class PartBodyObserver
{
    public function saved(PartBody $body): void
    {
        if ($body->wasChanged()) {
            $body->part->ancestorsAndSelf->each(fn (Part $part) => $part->makeImage());
        }
        if (config('ldraw.library_debug')) {
            Log::debug("Saved part body {$body->part_id} ({$body->part->filename})");
        }
    }
}
