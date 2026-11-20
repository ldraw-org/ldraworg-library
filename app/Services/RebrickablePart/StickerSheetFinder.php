<?php

namespace App\Services\RebrickablePart;

use App\Enums\PartCategory;
use App\Models\Part\Part;
use App\Models\RebrickablePart;
use Illuminate\Support\Str;

/**
 * Finds the Rebrickable sticker sheet an LDraw sticker or sticker shortcut belongs to, creating a local
 * placeholder sheet when Rebrickable has none.
 */
class StickerSheetFinder
{
    public function findForSticker(Part $part): ?RebrickablePart
    {
        if ($part->category !== PartCategory::Sticker && $part->category !== PartCategory::StickerShortcut) {
            return null;
        }

        if (!is_null($part->unofficial_part?->rebrickable_part)) {
            return $part->unofficial_part->rebrickable_part;
        }

        $flatBase = $this->resolveSheetLdrawNumber($part);

        // Try the curated LDraw number, then the sheet's own number, then its element. LDraw pads sheet
        // numbers with zeros (004402) where Rebrickable may not (4402).
        $numbers = array_values(array_unique(array_filter([$flatBase, ltrim((string) $flatBase, '0')])));
        foreach (['ldraw_number', 'number', 'element'] as $column) {
            $rbPart = RebrickablePart::sticker_sheets()
                ->where('is_local', false)
                ->whereIn($column, $numbers)
                ->orderBy('id')
                ->first();

            if ($rbPart) {
                return $rbPart;
            }
        }

        // Lookup via set keyword
        $setNumbers = $part->keywords->pluck('keyword')
            ->filter(fn ($kw) => preg_match('/set\s+([\w\-]+)/i', $kw, $matches))
            ->map(fn ($kw) => preg_replace('/^set\s+/i', '', $kw))
            ->map(fn ($setNum) => preg_match('/-\d$/', $setNum) ? $setNum : $setNum . '-1')
            ->unique();
        $rbPart = RebrickablePart::sticker_sheets()
            ->whereHas('sets', function ($query) use ($setNumbers) {
                $query->whereIn('number', $setNumbers);
            })
            ->first();
        if ($rbPart) {
            return $rbPart;
        }

        if (Str::startsWith($flatBase, 's')) {
            return RebrickablePart::updateOrCreate([
                'number' => "u-unknown",
                'is_local' => true,
                'rb_part_category_id' => 58,
            ], [
                'name' => "Unknown Sticker Sheet",
            ]);
        }

        // Create local placeholder
        return RebrickablePart::updateOrCreate([
            'number' => "u-{$flatBase}",
            'is_local' => true,
            'rb_part_category_id' => 58,
        ], [
            'name' => "Sticker Sheet for {$flatBase}",
        ]);
    }

    protected function resolveSheetLdrawNumber(Part $part): ?string
    {
        $filename = basename($part->filename, '.dat');

        // Case 1 — formed sticker (ends in cNN)
        $flat = StickerSheetStickers::flatBaseOfFormed($filename);
        if ($flat && $part->category === PartCategory::Sticker) {
            return $this->stripLetterSuffix($flat);
        }

        // Case 2 — sticker shortcut
        if ($part->category === PartCategory::StickerShortcut) {
            if ($sticker = $this->extractStickerSubpart($part)) {
                $stickerName = basename($sticker->filename, '.dat');
                return $this->stripLetterSuffix(StickerSheetStickers::flatBaseOfFormed($stickerName) ?? $stickerName);
            }
        }

        // Case 3 — flat sticker
        if ($part->category === PartCategory::Sticker) {
            return $this->stripLetterSuffix($filename);
        }

        return null;
    }

    protected function stripLetterSuffix(string $flat): string
    {
        $flat = preg_replace('/-f\d$/', '', $flat);
        return preg_replace('/[A-Za-z][A-Za-z0-9]?$/', '', $flat);
    }

    protected function extractStickerSubpart(Part $part): ?Part
    {
        return $part->subparts
            ?->first(fn (Part $p) => $p->category === PartCategory::Sticker);
    }
}
