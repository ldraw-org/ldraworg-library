<?php

namespace App\Services\Part;

use App\Enums\PartCategory;
use App\Models\Part\Part;
use App\Services\LDraw\Managers\RebrickablePartManager;
use App\Services\LDraw\Managers\StickerSheetManager;

class PartRebrickablePartService
{
    public function __construct(
        protected RebrickablePartManager $rebrickablePartManager,
        protected StickerSheetManager $stickerManager
    ) {}

    public function updateRebrickable(Part $part, bool $updateOfficial = false): void
    {
        if ($part->canSetRebrickablePart()) {
            if ($part->category == PartCategory::Sticker || $part->category == PartCategory::StickerShortcut) {
                $rbPart = $this->stickerManager->getStickerPart($part);
                $part->rebrickable_part()->associate($rbPart);
                $part->setExternalSiteKeywords($updateOfficial);
                $part->save();
                return;
            }
            $this->rebrickablePartManager->findOrCreateFromPart($part);
            $part->setExternalSiteKeywords($updateOfficial);
        }
    }
}