<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Services\LDraw\Managers\StickerSheetManager;
use App\Services\Part\RebrickableSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;

#[Timeout(1800)]
#[Queue('maintenance')]
class UpdateRebrickableStickerParts implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(StickerSheetManager $stickerManager, RebrickableSync $partRebrickableService): void
    {
        $stickerManager->refreshStickerParts();
        Part::whereRelation('rebrickable_part', 'is_local', 'true')
            ->each(fn (Part $part) => $partRebrickableService->syncRebrickablePart($part));
    }
}
