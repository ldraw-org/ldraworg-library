<?php

namespace App\Services\Omr\Set;

use App\Models\Omr\Set;
use App\Services\External\Rebrickable;
use App\Services\External\RebrickableRequestFailed;
use App\Services\LDraw\Managers\LDConfigManager;
use App\Services\RebrickablePart\Writer as RebrickablePartWriter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SyncSetParts
{
    protected const int PIVOT_INSERT_CHUNK = 500;

    public function __construct(
        protected Rebrickable $rebrickable,
        protected RebrickablePartWriter $rebrickablePartWriter,
        protected LDConfigManager $configManager,
    ) {
    }

    /**
     * @throws RebrickableRequestFailed leaving the set's existing parts untouched
     */
    public function handle(Set $set): void
    {
        $parts = $this->rebrickable->getSetParts($set->number);
        $colors = collect($this->configManager->ldrawColourCodesToRebrickable());

        // Parts are shared between sets, so write them outside the transaction to avoid holding their row locks
        $partIds = $parts
            ->groupBy('part.part_num')
            ->map(fn (Collection $rows) => $this->rebrickablePartWriter->updateOrCreateFromArray([
                ...$rows->first()['part'],
                'element' => $rows->whereNotNull('element_id')->last()['element_id'] ?? null,
            ])->id);

        $pivots = $parts->map(function (array $part) use ($set, $colors, $partIds) {
            $color = $colors->search($part['color']['id'], true);

            return [
                'set_id' => $set->id,
                'rebrickable_part_id' => $partIds[$part['part']['part_num']],
                'quantity' => $part['quantity'],
                'is_spare' => $part['is_spare'],
                'ldraw_colour_id' => $color === false ? null : $color,
            ];
        });

        DB::transaction(function () use ($set, $pivots) {
            $set->rebrickable_parts()->detach();
            $pivots->chunk(self::PIVOT_INSERT_CHUNK)->each(
                fn (Collection $chunk) => $set->rebrickable_parts()->newPivotStatement()->insert($chunk->values()->all())
            );
        });
    }
}
