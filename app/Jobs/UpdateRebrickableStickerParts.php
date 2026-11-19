<?php

namespace App\Jobs;

use App\Services\Part\RelinkRebrickableParts;
use App\Services\RebrickablePart\ImportRebrickableParts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;

/**
 * Imports the Rebrickable parts from the CSV downloads, then relinks and re-syncs the keywords of the
 * official and unofficial parts affected.
 */
#[Timeout(1800)]
#[Queue('maintenance')]
class UpdateRebrickableStickerParts implements ShouldQueue
{
    use Queueable;

    public function handle(ImportRebrickableParts $importer, RelinkRebrickableParts $relinker): void
    {
        $relinker->handle($importer->handle()['external_ids_changed']);
    }
}
