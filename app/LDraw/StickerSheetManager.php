<?php

namespace App\LDraw;

use App\Models\Part\PartKeyword;
use App\Models\RebrickablePart;
use App\Models\StickerSheet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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

    public function updateRebrickablePart(StickerSheet $sheet): void
    {
        $rb_part = $this->rebrickable->getPart($sheet->number);
        $rb = [];
        if ($rb_part->isNotEmpty()) {
            $rb = $rb_part->all();
        }

        $bricklink = PartKeyword::whereHas(
            'parts',
            fn (Builder $query): Builder => $query->where('sticker_sheet_id', $sheet->id)
        )
        ->where('keyword', 'LIKE', 'Bricklink %')
        ->pluck('keyword')
        ->first();

        if (!is_null($bricklink)) {
            $bricklink = Str::chopStart(Str::lower($bricklink), 'bricklink ');
            $rb_part = $this->rebrickable->getParts(['bricklink_id' => $bricklink]);
            if (!$rb_part->isEmpty()) {
                $rb = $rb_part->first()->all();
            }
        }

        $rb_part = $this->rebrickable->getParts(['search' => $sheet->number]);
        if ($rb_part->isNotEmpty()) {
            $part = $rb_part->first(function (array $item): bool {
                return Str::startsWith($item['name'], 'Sticker Sheet');
            });
            $rb = $part;
        }

        $set_nums = PartKeyword::whereHas(
            'parts',
            fn (Builder $query): Builder => $query->where('sticker_sheet_id', $sheet->id)
        )
        ->where('keyword', 'LIKE', 'Set %')
        ->pluck('keyword')
        ->transform(function (string $set) {
            $set = Str::chopStart(Str::lower($set), 'set ');
            if (!Str::endsWith($set, ['-1', '-2'])) {
                $set .= '-1';
            }
            return $set;
        });
        foreach ($set_nums as $set_num) {
            $rb_part = $this->rebrickable->getSetParts($set_num ?? '');
            if ($rb_part->isNotEmpty()) {
                $part = $rb_part->first(function (array $item): bool {
                    return Str::startsWith($item['part']['name'], 'Sticker Sheet');
                });
                if (!is_null(Arr::get($part, 'part'))) {
                    $rb = $part['part'];
                }
            }
        }
        $rb = RebrickablePart::findOrCreateFromArray($rb);
        $sheet->rebrickable_part()->associate($rb);
        $sheet->save();
    }
}
