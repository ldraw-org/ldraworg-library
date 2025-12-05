<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Services\LDraw\Managers\Part\PartManager;
use App\Services\LDraw\Managers\StickerSheetManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
    public function handle(StickerSheetManager $stickerManager, PartManager $partManager): void
    {
        $stickerManager->refreshStickerParts();
        Part::whereRelation('rebrickable_part', 'is_local', 'true')
            ->each(fn (Part $part) => $partManager->updateRebrickable($part));
    }
}
