<?php

namespace App\Services\LDraw\Managers;

use App\Enums\PartCategory;
use App\Models\Part\Part;
use App\Models\RebrickablePart;
use App\Services\LDraw\Rebrickable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class StickerSheetManager
{

    public function __construct(
        protected Rebrickable $rebrickable,
        protected SetManager $setManager,
        protected RebrickablePartManager $rebrickablePartManager,
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

    function refreshStickerParts(): void
    {
        $stickerParts = $this->rebrickable->getParts([
            'part_cat_id' => 58,
            'page' => 1,
            'page_size' => 1,
        ], true);

        $rbCount = $stickerParts->get('count');
        $dbCount = RebrickablePart::where('rb_part_category_id', 58)
            ->where('is_local', false)
            ->count();
        if ($rbCount == $dbCount) {
            return;
        }
      
        $stickerParts = $this->rebrickable
            ->getParts([
                'part_cat_id' => 58,
                'page_size' => 1000,
            ]);
      
        $rbStickerNumbers = $stickerParts
            ->pluck('part_num')
            ->values();
        $dbStickerNumbers = RebrickablePart::where('rb_part_category_id', 58)
            ->where('is_local', false)
            ->pluck('number')
            ->values();

        $newNumbers = $rbStickerNumbers->diff($dbStickerNumbers)->values()->all();
        $newNumbersSet = array_flip($newNumbers);

        $stickerParts
            ->filter(fn (array $item) => isset($newNumbersSet[$item['part_num']]))
            ->each(function (array $item) {
                $rbPart  = $this->rebrickablePartManager->updateOrCreateFromArray($item);
                $this->refreshStickerSets($rbPart);
            });
    }

    public function refreshStickerSets(RebrickablePart $sheet): void
    {
        $this->rebrickable->getPartColorSets($sheet->number, 9999)
            ->each(fn (array $set) => $this->setManager->updateOrCreateSetFromArray($set));
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
        $setNumbers = $part->keywords->pluck('keyword')
            ->filter(fn ($kw) => preg_match('/set\s+([\w\-]+)/i', $kw, $matches))
            ->map(fn ($kw) => preg_replace('/^set\s+/i', '', $kw))
            ->map(fn ($setNum) => preg_match('/-\d$/', $setNum) ? $setNum : $setNum . '-1')
            ->unique();
        $rbPart = RebrickablePart::where('rb_part_category_id', 58)
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
        } else {
            // Create local placeholder
            return RebrickablePart::updateOrCreate([
                'number' => "u-{$flatBase}",
                'is_local' => true,
                'rb_part_category_id' => 58,
            ], [
                'name' => "Sticker Sheet for {$flatBase}",
            ]);
        }   

    }

    public function resolveSheetLdrawNumber(Part $part): ?string
    {
        $filename = basename($part->filename, '.dat');

        // Case 1 — formed sticker (ends in cNN)
        $flat = $this->extractFlatBaseFromFormed($filename);
        if ($flat && $part->category === PartCategory::Sticker) {
            return $this->stripLetterSuffix($flat);
        }

        // Case 2 — sticker shortcut
        if ($part->category === PartCategory::StickerShortcut) {
            if ($sticker = $this->extractStickerSubpart($part)) {
                $flat = $this->extractFlatBaseFromFormed(basename($sticker->filename, '.dat'));
                if (!$flat) {
                    $flat = basename($sticker->filename, '.dat');
                }
                return $this->stripLetterSuffix($flat);
            }
        }

        // Case 3 — flat sticker
        if ($part->category === PartCategory::Sticker) {
            return $this->stripLetterSuffix($filename);
        }

        return null;
    }

    public function isFormed(Part $part): bool
    {
        if (is_null($part->rebrickable_part) || $part->rebrickable_part->rb_part_category_id !== 58) {
            return false;
        }
        return !is_null($this->extractFlatBaseFromFormed(basename($part->filename, '.dat')));
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
        preg_replace('/-f\d$/', '', $flat);
        return preg_replace('/[A-Za-z][A-Za-z0-9]?$/', '', $flat);
    }

    protected function extractStickerSubpart(Part $part): ?Part
    {
        return $part->subparts
            ?->first(fn(Part $p) => $p->category === PartCategory::Sticker);
    }

}
