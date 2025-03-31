<?php

namespace App\Models;

use App\LDraw\Rebrickable;
use App\Models\Part\Part;
use App\Models\Traits\HasParts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

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

}

