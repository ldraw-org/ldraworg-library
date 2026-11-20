<?php

namespace App\Services\RebrickablePart;

use App\Enums\PartCategory;
use App\Models\Part\Part;
use App\Models\RebrickablePart;
use Illuminate\Database\Eloquent\Collection;

/**
 * Sorts the official LDraw stickers on a Rebrickable sticker sheet.
 */
class StickerSheetStickers
{
    /**
     * The sheet's official stickers and shortcuts needed to build a set. Stickers used by a shortcut and flat
     * stickers with a formed version on the sheet are left out.
     */
    public function completeSet(RebrickablePart $sheet): Collection
    {
        if ($sheet->rb_part_category_id != 58) {
            return $sheet->parts->newCollection();
        }

        $sheet->loadMissing('parts', 'parts.parents');

        $parts = $sheet->parts->whereNull('unofficial_part');
        $filenames = $parts->pluck('filename')->values();

        return $parts->reject(function (Part $p) use ($filenames) {
            $hasShortcut = $p->parents
                ->contains(fn (Part $parent) => $parent->category === PartCategory::StickerShortcut);
            if ($hasShortcut) {
                return true;
            }
            $basename = basename($p->filename, '.dat');
            $patternPrefix = "parts/{$basename}c";

            return $filenames->contains(fn (string $fn) => $fn !== $p->filename && str_starts_with($fn, $patternPrefix));
        });
    }

    public function isFormed(Part $part): bool
    {
        if (is_null($part->rebrickable_part) || $part->rebrickable_part->rb_part_category_id !== 58) {
            return false;
        }

        return !is_null(self::flatBaseOfFormed(basename($part->filename, '.dat')));
    }

    /**
     * The flat sticker's number for a formed sticker's (one ending in cNN), else null.
     */
    public static function flatBaseOfFormed(string $name): ?string
    {
        if (preg_match('/^(.*?)[cC]\d+$/', $name, $m)) {
            return $m[1];
        }

        return null;
    }
}
