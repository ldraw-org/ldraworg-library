<?php

namespace App\Services\Part;

use App\Models\Part\Part;
use App\Services\RebrickablePart\StickerSheetFinder;
use App\Services\RebrickablePart\Writer as RebrickablePartWriter;

class RebrickableSync
{
    public function __construct(
        protected StickerSheetFinder $stickerSheetFinder,
        protected RebrickablePartWriter $rebrickablePartWriter,
        protected GenerateHeader $generateHeader,
    ) {
    }

    public function syncRebrickablePart(Part $part, bool $updateOfficial = false): void
    {
        if (! $part->canSetRebrickablePart()) {
            return;
        }

        $this->link($part);
        $part->setExternalSiteKeywords($updateOfficial);
        $this->generateHeader->updatePartHeader($part);
    }

    /**
     * Link the part to its Rebrickable part, or for a sticker its sticker sheet, without touching keywords or header.
     */
    public function link(Part $part): void
    {
        if ($part->category->isSticker()) {
            $part->rebrickable_part()->associate($this->stickerSheetFinder->findForSticker($part));
            $part->save();
        } else {
            $this->rebrickablePartWriter->linkPart($part);
        }
    }

}
