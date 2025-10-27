<?php

namespace App\Observers;

use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;

class PartKeywordObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(PartKeyword $keyword): void
    {
        if ($keyword->wasChanged()) {
            $keyword->parts->each->generateHeader();
        }
        if (config('ldraw.library_debug')) {
            Log::debug("Saved keyword {$keyword->id} ({$keyword->keyword})");
        }
    }    

    
    public function deleting(PartKeyword $keyword): void
    {
        $keyword->parts->each(fn (Part $part) => $part->keywords()->detach($keyword));
        if (config('ldraw.library_debug')) {
            Log::debug("Removed keyword {$keyword->id} ({$keyword->keyword})");
        }
    }    

}
