<?php

namespace App\Services\Omr\Set;

use App\Models\Omr\Set;
use App\Models\RebrickablePart;
use App\Services\External\Rebrickable;
use App\Services\External\RebrickableRequestFailed;
use App\Services\LDraw\Managers\LDConfigManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fetches one set's parts from the Rebrickable API. Used only for a set that isn't stored yet, so isn't
 * covered by ImportSetInventories.
 */
class SyncSetParts
{
    protected const int PIVOT_INSERT_CHUNK = 500;

    public function __construct(
        protected Rebrickable $rebrickable,
        protected LDConfigManager $configManager,
    ) {
    }

    /**
     * @throws RebrickableRequestFailed leaving the set's existing parts untouched
     */
    public function handle(Set $set): void
    {
        $parts = $this->rebrickable->getSetParts($set->number);
        $colours = $this->configManager->rebrickableColoursToLdrawCodes();

        // Imported parts are used as they are. A part newer than the CSV downloads gets a placeholder without
        // external ids, which the next import fills in. Parts are written outside the transaction to avoid
        // holding their row locks.
        $partIds = $parts
            ->pluck('part')
            ->unique('part_num')
            ->mapWithKeys(fn (array $part) => [
                $part['part_num'] => RebrickablePart::firstOrCreate(
                    ['number' => $part['part_num']],
                    [
                        'name' => $part['name'] ?? 'Unknown',
                        'rb_part_category_id' => $part['part_cat_id'] ?? 11,
                        'url' => $part['part_url'] ?? "https://rebrickable.com/parts/{$part['part_num']}/",
                    ]
                )->id,
            ]);

        $pivots = $parts->map(fn (array $part) => [
            'set_id' => $set->id,
            'rebrickable_part_id' => $partIds[$part['part']['part_num']],
            'quantity' => $part['quantity'],
            'is_spare' => $part['is_spare'],
            'ldraw_colour_id' => $colours[$part['color']['id']] ?? null,
        ]);

        DB::transaction(function () use ($set, $pivots) {
            $set->rebrickable_parts()->detach();
            $pivots->chunk(self::PIVOT_INSERT_CHUNK)->each(
                fn (Collection $chunk) => $set->rebrickable_parts()->newPivotStatement()->insert($chunk->values()->all())
            );
        });
    }
}
