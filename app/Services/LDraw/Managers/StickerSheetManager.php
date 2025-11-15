<?php

namespace App\Services\LDraw\Managers;

use App\Services\LDraw\Rebrickable;
use App\Models\Part\Part;
use App\Models\RebrickablePart;
use App\Models\StickerSheet;

class StickerSheetManager
{
    public function __construct(
        protected Rebrickable $rebrickable
    ) {
    }

    public function addStickerSheet(string $number): StickerSheet
    {
        $sheet = StickerSheet::firstWhere('number', $number);
        if (is_null($sheet)) {
            return StickerSheet::create([
                'number' => $number,
                'part_colors' => [],
                'rebrickable' => [],
            ]);
        }
        return $sheet;
    }

    public function updateRebrickablePart(StickerSheet $sheet, bool $updateOfficial = false): void
    {
        RebrickablePart::findOrCreateFromStickerSheet($sheet, $this->rebrickable);
        $sheet->load('rebrickable_part')
            ->parts?->each(function (Part $p) use ($updateOfficial) {
                $p->setExternalSiteKeywords($updateOfficial);
                $p->load('keywords');
                $p->generateHeader();
            });
    }
}
