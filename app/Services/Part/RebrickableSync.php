<?php

namespace App\Services\Part;

use App\Models\Part\Part;
use App\Services\LDraw\Managers\RebrickablePartManager;
use App\Services\LDraw\Managers\StickerSheetManager;

class RebrickableSync
{
    public function __construct(
        protected StickerSheetManager $stickerSheetManager,
        protected RebrickablePartManager $rebrickablePartManager,
    )
    {}

    public function syncRebrickablePart(Part $part, bool $updateOfficial = false): void
    {
        if (! $part->canSetRebrickablePart()) {
            return;
        }

        if ($part->category->isSticker()) {
            $rbPart = $this->stickerSheetManager->getStickerPart($part);
            $part->rebrickable_part()->associate($rbPart);
            $part->save();
        } else {
            $this->rebrickablePartManager->findOrCreateFromPart($part);
        }

        $part->setExternalSiteKeywords($updateOfficial);
    }

}
