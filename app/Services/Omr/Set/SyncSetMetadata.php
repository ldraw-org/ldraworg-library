<?php

namespace App\Services\Omr\Set;

use App\Enums\RebrickableCsvFile;
use App\Models\Omr\Set;
use App\Models\Omr\Theme;
use App\Services\External\RebrickableCsv;

/**
 * Updates the name, year and theme of existing sets from the Rebrickable CSV downloads.
 * Themes must be synced first; a set whose new theme is unknown is left unchanged.
 */
class SyncSetMetadata
{
    public function __construct(
        protected RebrickableCsv $rebrickableCsv,
    ) {
    }

    /**
     * @return int the number of sets updated
     */
    public function handle(): int
    {
        $sets = Set::withoutEagerLoads()->get(['id', 'number', 'name', 'year', 'theme_id'])->keyBy('number');
        $themeIds = array_flip(Theme::pluck('id')->all());
        $updated = 0;

        foreach ($this->rebrickableCsv->rows(RebrickableCsvFile::Sets) as $row) {
            $set = $sets->get($row['set_num']);
            if (is_null($set) || !isset($themeIds[(int) $row['theme_id']])) {
                continue;
            }

            $set->fill([
                'name' => $row['name'],
                'year' => (int) $row['year'],
                'theme_id' => (int) $row['theme_id'],
            ]);

            if ($set->isDirty()) {
                $set->save();
                $updated++;
            }
        }

        return $updated;
    }
}
