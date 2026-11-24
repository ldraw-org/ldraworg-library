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

        if ($part->category->isSticker()) {
            $rbPart = $this->stickerSheetFinder->findForSticker($part);
            $part->rebrickable_part()->associate($rbPart);
            $part->save();
        } else {
            $this->rebrickablePartWriter->linkPart($part);
        }

        $part->setExternalSiteKeywords($updateOfficial);
        $this->generateHeader->updatePartHeader($part);
    }

}
