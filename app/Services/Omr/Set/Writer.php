<?php

namespace App\Services\Omr\Set;

use App\Models\Omr\Set;
use App\Models\Omr\Theme;
use App\Services\External\Rebrickable;
use App\Services\External\RebrickableRequestFailed;
use App\Services\Omr\Theme\SyncRebrickableThemes;

class Writer
{
    public function __construct(
        protected Rebrickable $rebrickable,
        protected SyncSetParts $syncSetParts,
        protected SyncRebrickableThemes $syncThemes,
    ) {}

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
        if (! $this->ensureThemeExists($setData['theme_id'])) {
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
        if ($set->rebrickable_parts()->doesntExist() ||
            $set->refreshed_at === null ||
            $set->refreshed_at->lt(now()->subMonth())
        ) {
            try {
                $this->syncSetParts->handle($set);
            } catch (RebrickableRequestFailed) {
                return $set;
            }

            $set->refreshed_at = now();
            $set->save();
        }

        return $set;
    }

    protected function ensureThemeExists(int $themeId): bool
    {
        if (Theme::whereKey($themeId)->exists()) {
            return true;
        }

        $this->syncThemes->handle();

        return Theme::whereKey($themeId)->exists();
    }
}
