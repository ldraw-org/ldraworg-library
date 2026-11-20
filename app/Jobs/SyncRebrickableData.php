<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Services\External\RebrickableCsvDownloader;
use App\Services\LDraw\Managers\LDConfigManager;
use App\Services\Omr\Set\ImportSetInventories;
use App\Services\Omr\Set\SyncSetMetadata;
use App\Services\Omr\Theme\SyncRebrickableThemes;
use App\Services\Part\RelinkRebrickableParts;
use App\Services\RebrickablePart\DeleteEmptyLocalStickerSheets;
use App\Services\RebrickablePart\ImportRebrickableParts;
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
 * sheets left empty by the relink are deleted.
 */
#[Timeout(3600)]
#[Tries(1)]
#[Queue('maintenance')]
class SyncRebrickableData implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    protected const int DISPATCH_CHUNK = 200;

    public function __construct(
        public bool $forceDownload = false,
        public bool $skipDownload = false,
    ) {
    }

    public function handle(
        RebrickableCsvDownloader $downloader,
        SyncRebrickableThemes $themes,
        LDConfigManager $configManager,
        ImportRebrickableParts $parts,
        RelinkRebrickableParts $relinker,
        ImportSetInventories $inventories,
        SyncSetMetadata $setMetadata,
        DeleteEmptyLocalStickerSheets $deleteEmptySheets,
    ): void {
        $downloaded = !$this->skipDownload && $downloader->download($this->forceDownload);

        // Themes first, as added sets and set details refer to them
        $themes->handle();
        $configManager->importColours();

        $partResult = $parts->handle();
        // Before relinking, as stickers are matched to sheets through the sets holding them
        $setResult = $inventories->handle();
        $relinked = $relinker->handle($partResult['external_ids_changed']);
        $sheetsDeleted = $deleteEmptySheets->handle();

        // Unofficial parts still without a Rebrickable part, mostly stickers that get a local sheet
        Part::unofficial()
            ->canHaveRebrickablePart()
            ->doesntHave('rebrickable_part')
            ->chunkById(self::DISPATCH_CHUNK, fn ($chunk) => UpdateRebrickable::dispatch($chunk));

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
