<?php

namespace App\Services\RebrickablePart;

use App\Models\RebrickablePart;

/**
 * Deletes local sticker sheets that no part or set inventory refers to any more, such as placeholders
 * whose stickers moved to the real sheet.
 */
class DeleteEmptyLocalStickerSheets
{
    /**
     * @return int the number of sheets deleted
     */
    public function handle(): int
    {
        return RebrickablePart::sticker_sheets()
            ->where('is_local', true)
            ->doesntHave('parts')
            ->doesntHave('sets')
            ->delete();
    }
}
