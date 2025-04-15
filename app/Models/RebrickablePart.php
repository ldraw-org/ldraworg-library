<?php

namespace App\Models;

use App\Enums\ExternalSite;
use App\LDraw\Rebrickable;
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

    protected function casts(): array
    {
        return [
            'bricklink' => 'array',
            'brickowl' => 'array',
            'brickset' => 'array',
            'lego' => 'array',
        ];
    }

    public function sticker_sheets(): HasMany
    {
        return $this->hasMany(StickerSheet::class);
    }

    public static function findOrCreateFromArray(array $part): ?self
    {
        if (!Arr::has($part, 'part_num')) {
            return null;
        }
        return self::firstWhere('number', Arr::get($part, 'part_num')) ?? self::createFromArray($part);
    }

    public static function createFromArray(array $part): ?self
    {
        if (!Arr::has($part, 'part_num')) {
            return null;
        }
        $values = [
            'number' => Arr::get($part, 'part_num'),
            'name' => Arr::get($part, 'name'),
            'url' => Arr::get($part, 'part_url'),
            'image_url' => Arr::get($part, 'part_img_url'),
            'bricklink' => Arr::get($part, 'external_ids.BrickLink'),
            'brickset' => Arr::get($part, 'external_ids.Brickset'),
            'brickowl' => Arr::get($part, 'external_ids.BrickOwl'),
            'lego' => Arr::get($part, 'external_ids.LEGO'),
        ];
        $rb = self::create($values);
        return $rb;
    }

    public static function findOrCreateFromPart(Part $part): ?self
    {
        $part_num = basename($part->filename, '.dat');
        $rb_data = (new Rebrickable())->getParts(['ldraw_id' => $part_num]);
        if ($rb_data->isEmpty()) {
            return null;
        }
        $rb_data = $rb_data->where('part_num', $part_num)->isEmpty() ? $rb_data->first() : $rb_data->where('part_num', $part_num)->first();
        $rb = self::findOrCreateFromArray($rb_data);
        $part->rebrickable_part()->associate($rb);
        $part->save();
        return $rb;
    }

    public static function findOrCreateFromStickerSheet(StickerSheet $sheet): ?self
    {
        $rb = new Rebrickable();
        $rb_data = new Collection();

        $rb_data = $rb->getPart($sheet->number);
        if ($rb_data->isEmpty()) {
            $rb_num = $sheet->parts?->first(fn (Part $part) => !is_null($part->getExternalSiteNumber(ExternalSite::Rebrickable)))
                ?->getExternalSiteNumber(ExternalSite::Rebrickable);
            $rb_data = $rb_num ? $rb->getPart($rb_num) : $rb_data;
        }
        if ($rb_data->isEmpty()) {
            $bl_num = $sheet->parts?->first(fn (Part $part) => !is_null($part->getExternalSiteNumber(ExternalSite::BrickLink)))
                ?->getExternalSiteNumber(ExternalSite::BrickLink);
            $rb_data = $bl_num ? $rb->getParts(['bricklink_id' => $bl_num])->first() : $rb_data;
        }
        if ($rb_data->isEmpty()) {
            $search_data = $rb->getParts(['search' => $sheet->number])?->first(fn (array $item) => Str::startsWith($item['name'], 'Sticker Sheet'));
            $rb_data = $search_data ? collect($search_data) : $rb_data;
        }

        if ($rb_data->isEmpty()) {
            $sets = new Collection();
            $sheet->parts?->each(function (Part $part) use (&$sets) {
                $s = $part->keywords?->pluck('keyword')
                ->filter(fn (string $kw) => Str::of($kw)->lower()->startsWith('set'))
                ->map(function (string $kw) {
                    $kw = Str::of($kw)->lower()->chopStart('set ');
                    $kw = $kw->endsWith(['-1', '-2']) ? $kw : $kw->append('-1');
                    return $kw->toString();
                });
                $sets = $sets->merge($s);
            });
            $sets->unique()->each(function (string $set) use (&$rb_data, $rb) {
                $rb_set = $rb->getSetParts($set);
                if ($rb_set->isNotEmpty()) {
                    $part = $rb_set->first(fn (array $item) => Str::startsWith($item['part']['name'], 'Sticker Sheet'));
                    if (!is_null(Arr::get($part, 'part'))) {
                        $rb_data = collect([$part['part']]);
                        return false;
                    }
                }
            });
        }
        if ($rb_data->isEmpty()) {
            return null;
        }
        $rb = self::findOrCreateFromArray($rb_data->all());
        $sheet->rebrickable_part()->associate($rb);
        $sheet->save();
        return $rb;
    }

}
