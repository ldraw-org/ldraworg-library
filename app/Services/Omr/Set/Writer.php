<?php

namespace App\Services\Omr\Set;

use App\Enums\RebrickableCsvFile;
use App\Models\Omr\Set;
use App\Services\External\Rebrickable;
use App\Services\External\RebrickableCsv;
use App\Services\External\RebrickableRequestFailed;
use App\Services\Omr\Theme\SyncRebrickableThemes;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

/**
 * Adds sets from the Rebrickable CSV downloads, using the API only for a set the downloads don't have.
 */
class Writer
{
    public function __construct(
        protected Rebrickable $rebrickable,
        protected RebrickableCsv $rebrickableCsv,
        protected SyncSetParts $syncSetParts,
        protected ImportSetInventories $importSetInventories,
        protected SyncRebrickableThemes $syncThemes,
    ) {
    }

    public function findFromSetNumber(string $setNumber, bool $createIfMissing = false): ?Set
    {
        $set = Set::firstWhere('number', $setNumber);
        if ($set === null && $createIfMissing === true) {
            $setData = $this->setData($setNumber);
            $set = is_null($setData) ? null : $this->updateOrCreateSetFromArray($setData);
        }

        return $set;
    }

    /**
     * The set's Rebrickable details in the API's shape, from the CSV downloads or else the API.
     *
     * @return ?array{set_num: string, name: string, year: int, theme_id: int, set_url: string}
     */
    public function setData(string $setNumber): ?array
    {
        try {
            $row = $this->rebrickableCsv->rows(RebrickableCsvFile::Sets)
                ->first(fn (array $row) => $row['set_num'] === $setNumber);
        } catch (FileNotFoundException) {
            $row = null;
        }

        if (!is_null($row)) {
            return [
                'set_num' => $row['set_num'],
                'name' => $row['name'],
                'year' => (int) $row['year'],
                'theme_id' => (int) $row['theme_id'],
                'set_url' => "https://rebrickable.com/sets/{$row['set_num']}/",
            ];
        }

        $apiSet = $this->rebrickable->getSet($setNumber);

        return $apiSet->isEmpty() ? null : $apiSet->only(['set_num', 'name', 'year', 'theme_id', 'set_url'])->all();
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
        // Synced sets are kept current by ImportSetInventories, so only a never synced one needs syncing here
        if ($set->refreshed_at === null) {
            $this->syncParts($set);
        }

        return $set;
    }

    /**
     * Syncs the set's parts from the CSV downloads, or from the API when the downloads don't have the set.
     *
     * @return bool false when the parts could not be fetched, leaving the existing parts untouched
     */
    public function syncParts(Set $set): bool
    {
        try {
            if ($this->importSetInventories->importSet($set)) {
                return true;
            }
        } catch (FileNotFoundException) {
            // Without the downloads the API is the only source
        }

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
