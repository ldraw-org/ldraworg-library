<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Services\Part\RebrickableSync;
use App\Services\RebrickablePart\ImportRebrickableParts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;

/**
 * Imports the Rebrickable parts from the CSV downloads, then re-syncs the unofficial parts whose external ids
 * changed and the parts still linked to a local sticker sheet.
 */
#[Timeout(1800)]
#[Queue('maintenance')]
class UpdateRebrickableStickerParts implements ShouldQueue
{
    use Queueable;

    protected const int DISPATCH_CHUNK = 200;

    public function handle(ImportRebrickableParts $importer, RebrickableSync $partRebrickableService): void
    {
        $result = $importer->handle();

        Part::unofficial()
            ->whereIn('rebrickable_part_id', $result['external_ids_changed'])
            ->chunkById(self::DISPATCH_CHUNK, fn ($parts) => UpdateRebrickable::dispatch($parts));

        Part::whereRelation('rebrickable_part', 'is_local', true)
            ->each(fn (Part $part) => $partRebrickableService->syncRebrickablePart($part));
    }
}
