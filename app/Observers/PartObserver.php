<?php

namespace App\Observers;

use App\Services\LDraw\Managers\Part\PartManager;
use App\Events\PartDeleted;
use App\Jobs\UpdateLibraryCsv;
use Illuminate\Support\Facades\Auth;
use App\Models\Part\Part;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;

class PartObserver implements ShouldHandleEventsAfterCommit
{
    public function deleting(Part $part): void
    {
        $part->putDeletedBackup();
        $part->load('parents');
    }

    public function deleted(Part $part): void
    {
        $pm = app(PartManager::class);
        $part->parents->each(function (Part $p) use ($pm) {
            $pm->loadSubparts($p);
            $pm->checkPart($p);
        });
        PartDeleted::dispatch(Auth::user() ?? User::find(1), $part->filename, $part->description);
    }


    public function saving(Part $part): void
    {
        if (config('ldraw.library_debug')) {
            Log::debug("Saving part {$part->id} ({$part->filename})");
        }
        if ($part->isDirty(['description', 'filename']) && $part->type->inPartsFolder() && $part->isNotFix()) {
            UpdateLibraryCsv::dispatch();
            if (config('ldraw.library_debug')) {
                Log::debug("Updated library.csv while saving {$part->id} ({$part->filename})");
            }
        }
/*
        if ($part->isDirty([
            'description', 
            'filename',
            'user_id',
            'type',
            'type_qualifier',
            'part_release_id',
            'help',
            'category',
            'part_release_id',
            'bfc',
            'cmdline',
            'license',
            'preview'
        ])) {
            $part->generateHeader(false);
        }
*/
    }

/*
    public function updating(Part $part): void
    {
        if ($part->isDirty()) {
            $part->generateHeader(false);
        }
        if (config('ldraw.library_debug')) {
            Log::debug("Updated part {$part->id} ({$part->filename})");
        }
    }

    public function retrieved(Part $part): void
    {
        if (config('ldraw.library_debug')) {
            Log::debug("Retrieved part {$part->id} ({$part->filename})");
        }
    }

    public function pivotAttached(Part $part, string $relationName, array $pivotIds, array $pivotIdsAttributes): void
    {
        if (config('ldraw.library_debug')) {
            Log::debug("Pivot {$relationName} updated for {$part->id} ({$part->filename})");
        }
    }

    public function pivotDetached(Part $part, string $relationName, array $pivotIds, array $pivotIdsAttributes): void
    {
        if (config('ldraw.library_debug')) {
            Log::debug("Pivot {$relationName} updated for {$part->id} ({$part->filename})");
        }
    }
*/
}
