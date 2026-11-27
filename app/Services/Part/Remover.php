<?php

namespace App\Services\Part;

use App\Events\PartDeleted;
use App\Jobs\CheckPart;
use App\Models\Part\Part;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Remover
{
    public function __construct(
        protected SyncSubparts $syncSubparts,
    ) {
    }

    /**
     * Deletes a part, keeping a backup, and relinks the parts that used it.
     */
    public function delete(Part $part, User $actor): void
    {
        // Read before deleting: the delete cascades to the part's related_parts rows
        $parents = $part->parents()->get();

        $this->putDeletedBackup($part);

        DB::transaction(function () use ($part, $actor): void {
            $part->delete();
            PartDeleted::dispatch($actor, $part->filename, $part->description);
        });

        $parents->each(function (Part $parent): void {
            $this->syncSubparts->loadSubparts($parent);
            CheckPart::dispatch($parent->withoutRelations());
        });
    }

    public function putDeletedBackup(Part $part): void
    {
        $t = time();
        Storage::put("deleted/library/{$part->filename}.{$t}", $part->get());
        Storage::put('deleted/library/' . str_replace(['.png', '.dat'], '.evt', $part->filename). ".{$t}", $part->events->toJson());
    }
}
