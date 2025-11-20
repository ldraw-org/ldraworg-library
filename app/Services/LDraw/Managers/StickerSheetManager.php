<?php

namespace App\Services\LDraw\Managers;

use App\Enums\PartCategory;
use App\Models\Part\Part;
use App\Models\RebrickablePart;
use App\Services\LDraw\Rebrickable;
use Illuminate\Database\Eloquent\Collection;

class StickerSheetManager
{

    public function __construct(
        protected Rebrickable $rebrickable
    )
    {}

    public function complete_set(RebrickablePart $sheet): Collection
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

            $hasFormed = $filenames->contains(function ($fn) use ($patternPrefix, $p) {
                return $fn !== $p->filename && str_starts_with($fn, $patternPrefix);
            });
            return $hasFormed;
        });
    }

    public function getStickerPart(Part $part): ?RebrickablePart
    {
        if ($part->category !== PartCategory::Sticker && $part->category !== PartCategory::StickerShortcut) {
            return null;
        }

        if (!is_null($part->unofficial_part?->rebrickable_part)) {
            return $part->unofficial_part->rebrickable_part;
        }

        $flatBase = $this->resolveSheetLdrawNumber($part);

        // Try LDraw number
        $rbPart = RebrickablePart::where('ldraw_number', $flatBase)
            ->where('rb_part_category_id', 58)
            ->where('is_local', false)
            ->first();

        if ($rbPart) {
            return $rbPart;
        }

        // Try element field
        $rbPart = RebrickablePart::where('element', $flatBase)
            ->where('rb_part_category_id', 58)
            ->where('is_local', false)
            ->first();

        if ($rbPart) {
            return $rbPart;
        }

        // Lookup via set keyword
        foreach ($part->keywords ?? [] as $kw) {
            if (preg_match('/set\s+([\w\-]+)/i', $kw, $m)) {
                $setNum = $m[1];
                if (!preg_match('/-\d$/', $setNum)) {
                    $setNum .= '-1';
                }
                $rbData = $this->rebrickable->getSetParts($setNum)
                    ->first(fn(array $item) => $item['part']['part_cat_id'] === 58);

                if (!is_null($rbData)) {
                    return RebrickablePart::updateOrCreateFromArray($rbData['part']);
                }
            }
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

    public function resolveSheetLdrawNumber(Part $part): ?string
    {
        $filename = basename($part->filename, '.dat');

        // Case 1 — formed sticker (ends in cNN)
        if ($flat = $this->extractFlatBaseFromFormed($filename)) {
            return $this->stripLetterSuffix($flat);
        }

        // Case 2 — sticker shortcut
        if ($part->category === PartCategory::StickerShortcut) {
            if ($sticker = $this->extractStickerSubpart($part)) {
                $flat = basename($sticker->filename, '.dat');
                return $this->stripLetterSuffix($flat);
            }
        }

        // Case 3 — flat sticker
        if ($part->category === PartCategory::Sticker) {
            return $this->stripLetterSuffix($filename);
        }

        return null;
    }

    protected function extractFlatBaseFromFormed(string $name): ?string
    {
        if (preg_match('/^(.*?)[cC]\d+$/', $name, $m)) {
            return $m[1];
        }
        return null;
    }

    protected function stripLetterSuffix(string $flat): string
    {
        return preg_replace('/[a-z]+$/i', '', $flat);
    }

    protected function extractStickerSubpart(Part $part): ?Part
    {
        return $part->subparts
            ?->first(fn(Part $p) => $p->category === PartCategory::Sticker);
    }

}
