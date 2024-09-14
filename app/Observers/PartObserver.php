<?php

namespace App\Observers;

use App\Events\PartDeleted;
use Illuminate\Support\Facades\Auth;
use App\Models\Part;
use App\Models\ReviewSummary\ReviewSummaryItem;

class PartObserver
{
    /*
        public function saved(Part $part)
        {
            if ($part->wasChanged([
                'user_id',
                'part_category_id',
                'part_license_id',
                'part_type_id',
                'part_release_id',
                'part_type_qualifier_id',
                'description',
                'filename',
                'header',
                'cmdline',
                'bfc',
            ])) {
                $part->generateHeader();
            }
        }
    */
    public function deleting(Part $part): void
    {
        $part->putDeletedBackup();
        $part->deleteRelationships();
        ReviewSummaryItem::where('part_id', $part->id)->delete();
    }

    public function deleted(Part $part)
    {
        PartDeleted::dispatch(Auth::user(), $part->filename, $part->description, $part->parents()->unofficial()->pluck('id')->all());
    }
}
