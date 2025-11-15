<?php

namespace App\Models;

use App\Enums\ExternalSite;
use App\Services\LDraw\Rebrickable;
use App\Models\Part\Part;
use App\Models\Traits\HasParts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class RebrickablePart extends Model
{
    use HasParts;

    protected $guarded = [];

    /**
     * @return array{
     *     'bricklink': 'array',
     *     'brickowl': 'array',
     *     'brickset': 'array',
     *     'lego': 'array'
     * }
     */
    protected function casts(): array
    {
        return [
            'bricklink' => 'array',
            'brickowl' => 'array',
            'brickset' => 'array',
            'lego' => 'array',
        ];
    }

  /* TODO: RebrickableSet
    public function sets(): BelongsToMany
    {
        return $this->belongsToMany(RebrickableSet::class, 'rebrickable_set_part', 'rebrickable_part_id', 'set_id')
                    ->withPivot('color_id', 'quantity');
    }
*/

    public function sticker_sheets(): HasMany
    {
        return $this->hasMany(StickerSheet::class);
    }

    // -------------------------
    // Core Update / Create
    // -------------------------

    public static function updateOrCreateFromArray(array $partData): self
    {
        $values = [
            'number'    => Arr::get($partData, 'part_num'),
            'name'      => Arr::get($partData, 'name', 'Unknown'),
            'url'       => Arr::get($partData, 'part_url'),
            'image_url' => Arr::get($partData, 'part_img_url'),
            'bricklink' => Arr::get($partData, 'external_ids.BrickLink', []),
            'brickset'  => Arr::get($partData, 'external_ids.Brickset', []),
            'brickowl'  => Arr::get($partData, 'external_ids.BrickOwl', []),
            'lego'      => Arr::get($partData, 'external_ids.LEGO', []),
        ];

        return static::updateOrCreate(
            ['number' => $values['number']],
            $values
        );
    }

    public function refreshFromApi(Rebrickable $rbService): self
    {
        $data = $rbService->getParts(['ldraw_id' => $this->number])->first();

        if ($data) {
            static::updateOrCreateFromArray($data);
            $this->refresh(); // reload the model from DB
        }

        return $this;
    }

    // -------------------------
    // Find or create from Part
    // -------------------------

    public static function findOrCreateFromPart(Part $part, Rebrickable $rbService): ?self
    {
        $partNum = basename($part->filename, '.dat');

        $rbParts = $rbService->getParts(['ldraw_id' => $partNum]);
        if ($rbParts->isEmpty()) {
            return null;
        }

        // Prefer exact match
        $rbData = $rbParts->firstWhere('part_num', $partNum) ?? $rbParts->first();
        if (!$rbData) {
            return null;
        }

        $rb = static::updateOrCreateFromArray($rbData);

        // Associate and save
        $part->rebrickable_part()->associate($rb);
        $part->save();

        return $rb;
    }

    // -------------------------
    // Find or create from StickerSheet
    // -------------------------

    public static function findOrCreateFromStickerSheet(StickerSheet $sheet, Rebrickable $rbService): ?self
    {
        $rbData = static::lookupStickerSheetData($sheet, $rbService);
        if (!$rbData) {
            return null;
        }

        return static::persistStickerSheetData($sheet, $rbData);
    }

    protected static function lookupStickerSheetData(StickerSheet $sheet, Rebrickable $rbService): ?array
    {
        return static::findBySheetNumber($sheet, $rbService)
            ?? static::findByPartRebrickableId($sheet, $rbService)
            ?? static::findByPartBrickLinkId($sheet, $rbService)
            ?? static::findBySearch($sheet, $rbService)
            ?? static::findBySetParts($sheet, $rbService);
    }

    protected static function persistStickerSheetData(StickerSheet $sheet, array $rbData): self
    {
        $rbPart = static::updateOrCreateFromArray($rbData);
        $sheet->rebrickable_part()->associate($rbPart);
        $sheet->save();

        return $rbPart;
    }

    // -------------------------
    // Lookup Strategies
    // -------------------------

    protected static function findBySheetNumber(StickerSheet $sheet, Rebrickable $rbService): ?array
    {
        $data = $rbService->getPart($sheet->number);
        return $data->isNotEmpty() ? $data->first() : null;
    }

    protected static function findByPartRebrickableId(StickerSheet $sheet, Rebrickable $rbService): ?array
    {
        $part = $sheet->parts?->first(fn(Part $p) => $p->getExternalSiteNumber(ExternalSite::Rebrickable));
        if (!$part) return null;

        $data = $rbService->getPart($part->getExternalSiteNumber(ExternalSite::Rebrickable));
        return $data->isNotEmpty() ? $data->first() : null;
    }

    protected static function findByPartBrickLinkId(StickerSheet $sheet, Rebrickable $rbService): ?array
    {
        $part = $sheet->parts?->first(fn(Part $p) => $p->getExternalSiteNumber(ExternalSite::BrickLink));
        if (!$part) return null;

        $data = $rbService->getParts(['bricklink_id' => $part->getExternalSiteNumber(ExternalSite::BrickLink)]);
        return $data->isNotEmpty() ? $data->first() : null;
    }

    protected static function findBySearch(StickerSheet $sheet, Rebrickable $rbService): ?array
    {
        $data = $rbService->getParts(['search' => $sheet->number]);
        return $data->first(fn($item) => Str::startsWith($item['name'], 'Sticker Sheet')) ?? null;
    }

    protected static function findBySetParts(StickerSheet $sheet, Rebrickable $rbService): ?array
    {
        $sets = collect();
        $sheet->parts?->each(function (Part $part) use (&$sets) {
            $keywords = $part->keywords?->pluck('keyword')
                ->filter(fn(string $kw) => Str::of($kw)->lower()->startsWith('set'))
                ->map(fn(string $kw) => Str::of($kw)->lower()->chopStart('set ')->append('-1')->toString());
            $sets = $sets->merge($keywords);
        });

        $rbData = null;
        $sets->unique()->each(function (string $set) use (&$rbService, &$rbData) {
            $rbSet = $rbService->getSetParts($set);
            if ($rbSet->isNotEmpty()) {
                $part = $rbSet->first(fn(array $item) => Str::startsWith($item['part']['name'], 'Sticker Sheet'));
                if (!is_null($part['part'] ?? null)) {
                    $rbData = $part['part'];
                    return false; // break loop
                }
            }
        });

        return $rbData;
    }
}
