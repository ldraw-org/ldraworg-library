<?php

namespace App\Services\Omr\Set;

use App\Models\Omr\Set;
use App\Services\External\Rebrickable;
use App\Services\External\RebrickableRequestFailed;
use App\Services\Omr\Theme\SyncRebrickableThemes;

class Writer
{
    public function __construct(
        protected Rebrickable $rebrickable,
        protected SyncSetParts $syncSetParts,
        protected SyncRebrickableThemes $syncThemes,
    ) {
    }

    public function findFromSetNumber(string $setNumber, bool $createIfMissing = false): ?Set
    {
        $set = Set::firstWhere('number', $setNumber);
        if ($set === null && $createIfMissing === true) {
            $rebrickableSet = $this->rebrickable->getSet($setNumber);
            if ($rebrickableSet->isNotEmpty()) {
                $set = $this->updateOrCreateSetFromArray($rebrickableSet->toArray());
            } else {
                $set = null;
            }
        }

        return $set;
    }

    public function updateOrCreateSetFromArray(array $setData): ?Set
    {
        if (! $this->syncThemes->ensureExists($setData['theme_id'])) {
            return null;
        }

        $set = Set::updateOrCreate(
            ['number' => $setData['set_num']],
            [
                'name' => $setData['name'],
                'year' => $setData['year'],
                'theme_id' => $setData['theme_id'],
                'rb_url' => $setData['set_url'],
            ]
        );
        // Synced sets are kept current by ImportSetInventories, so only a never synced one needs the API
        if ($set->refreshed_at === null) {
            $this->syncParts($set);
        }

        return $set;
    }

    /**
     * @return bool false when the parts could not be fetched, leaving the existing parts untouched
     */
    public function syncParts(Set $set): bool
    {
        try {
            $this->syncSetParts->handle($set);
        } catch (RebrickableRequestFailed) {
            return false;
        }

        $set->refreshed_at = now();
        $set->save();

        return true;
    }
}
