<?php

namespace App\Jobs;

use App\Enums\ReleaseStage;
use App\Models\Part\Part;
use App\Models\Part\PartReleaseRun;
use App\Services\External\RebrickableCsvDownloader;
use App\Services\LDConfig\ColourImporter;
use App\Services\Omr\Set\ImportSetInventories;
use App\Services\Omr\Set\SyncSetMetadata;
use App\Services\Omr\Theme\SyncRebrickableThemes;
use App\Services\Part\RebrickableSync;
use App\Services\Part\Release\ReleaseProgress;
use App\Services\Part\RelinkRebrickableParts;
use App\Services\RebrickablePart\DeleteEmptyLocalStickerSheets;
use App\Services\RebrickablePart\ImportRebrickableParts;
use App\Services\Support\MemoryLimit;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;

/**
 * Downloads fresh Rebrickable CSVs and resyncs everything taken from them: themes, colour mappings,
 * Rebrickable parts and the library parts linked to them, set inventories and set details. Local sticker
 * sheets left empty by the relink are deleted. Nothing is left queued, so the sync is complete when the job ends.
 */
#[Timeout(3600)]
#[Tries(1)]
#[Queue('maintenance')]
class SyncRebrickableData implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    protected const int UNLINKED_CHUNK = 200;

    /** The set inventory import holds every set's inventory at once, so the sync needs ~100MB on top of the worker's own */
    protected const string MEMORY_LIMIT = '512M';

    public function __construct(
        public bool $forceDownload = false,
        public bool $skipDownload = false,
        public ?PartReleaseRun $releaseRun = null,
    ) {
    }

    public function handle(
        RebrickableCsvDownloader $downloader,
        SyncRebrickableThemes $themes,
        ColourImporter $colourImporter,
        ImportRebrickableParts $parts,
        RelinkRebrickableParts $relinker,
        ImportSetInventories $inventories,
        SyncSetMetadata $setMetadata,
        DeleteEmptyLocalStickerSheets $deleteEmptySheets,
        RebrickableSync $rebrickableSync,
        ReleaseProgress $progress,
        MemoryLimit $memoryLimit,
    ): void {
        $progress->stage($this->releaseRun, ReleaseStage::SyncingRebrickable);
        $memoryLimit->raiseTo(self::MEMORY_LIMIT);

        $downloaded = !$this->skipDownload && $downloader->download($this->forceDownload);

        // Themes first, as added sets and set details refer to them
        $themes->handle();
        $colourImporter->import();

        $partResult = $parts->handle();
        // Before relinking, as stickers are matched to sheets through the sets holding them
        $setResult = $inventories->handle();
        $relinked = $relinker->handle($partResult['external_ids_changed']);
        $sheetsDeleted = $deleteEmptySheets->handle();

        // Unofficial parts still without a Rebrickable part, mostly stickers that get a local sheet. Done here
        // rather than queued, so every link, keyword and header change is in place when the job finishes
        // and a release chained after the sync never runs alongside it.
        Part::unofficial()
            ->canHaveRebrickablePart()
            ->doesntHave('rebrickable_part')
            ->lazyById(self::UNLINKED_CHUNK)
            ->each(fn (Part $part) => $rebrickableSync->syncRebrickablePart($part));

        $setsUpdated = $setMetadata->handle();

        Log::info('Rebrickable sync complete', [
            'downloaded' => $downloaded,
            'parts_created' => $partResult['created'],
            'parts_updated' => $partResult['updated'],
            'stickers_retired' => $partResult['retired_stickers'],
            'parts_relinked' => $relinked,
            'empty_local_sheets_deleted' => $sheetsDeleted,
            'sets_added' => $setResult['added_sets'],
            'inventories_updated' => $setResult['updated_sets'],
            'inventory_parts_missing' => $setResult['missing_parts'],
            'set_details_updated' => $setsUpdated,
        ]);
    }
}
