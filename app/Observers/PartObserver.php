<?php

namespace App\Observers;

use App\Events\PartDeleted;
use Illuminate\Support\Facades\Auth;
use App\Models\Part;

class PartObserver
{
    /**
     * Handle the Part "deleting" event.
     */
    public function deleting(Part $part): void
    {
        $part->putDeletedBackup();
        $part->deleteRelationships();
        \App\Models\ReviewSummaryItem::where('part_id', $part->id)->delete();
        PartDeleted::dispatch(Auth::user(), $part->filename, $part->description, $part->parents()->unofficial()->pluck('id')->all());
    }
}
