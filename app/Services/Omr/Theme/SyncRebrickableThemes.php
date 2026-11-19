<?php

namespace App\Services\Omr\Theme;

use App\Enums\RebrickableCsvFile;
use App\Models\Omr\Set;
use App\Models\Omr\Theme;
use App\Services\External\Rebrickable;
use App\Services\External\RebrickableCsv;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class SyncRebrickableThemes
{
    public function __construct(
        protected RebrickableCsv $rebrickableCsv,
        protected Rebrickable $rebrickable,
    ) {
    }

    public function handle(): void
    {
        $themes = $this->rebrickableCsv->rows(RebrickableCsvFile::Themes)
            ->map(fn (array $row) => [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'parent_id' => is_null($row['parent_id']) ? null : (int) $row['parent_id'],
            ])
            ->all();

        Theme::upsert($themes, ['id'], ['name', 'parent_id']);
        $this->pruneStaleThemes(array_column($themes, 'id'));
    }

    /**
     * Deletes themes no longer in the downloads unless a set or a child theme still uses them,
     * repeating so a chain of unused stale themes is removed from the leaves up.
     *
     * @param array<int, int> $currentIds
     */
    protected function pruneStaleThemes(array $currentIds): void
    {
        do {
            $usedIds = Set::query()->distinct()->pluck('theme_id')
                ->merge(Theme::whereNotNull('parent_id')->distinct()->pluck('parent_id'))
                ->unique();
            $staleIds = Theme::whereNotIn('id', $currentIds)
                ->whereNotIn('id', $usedIds)
                ->pluck('id');
            Theme::whereKey($staleIds)->delete();
        } while ($staleIds->isNotEmpty());
    }

    /**
     * Syncs from the CSV downloads when the theme is missing, then falls back to the API for a
     * theme (and any missing ancestors) newer than the downloads.
     */
    public function ensureExists(int $themeId): bool
    {
        if (Theme::whereKey($themeId)->exists()) {
            return true;
        }

        try {
            $this->handle();
        } catch (FileNotFoundException) {
            // Without the downloads the API is the only source
        }

        $id = $themeId;
        while (!is_null($id) && Theme::whereKey($id)->doesntExist()) {
            $theme = $this->rebrickable->getTheme($id);
            if ($theme->isEmpty()) {
                break;
            }
            Theme::create($theme->only(['id', 'name', 'parent_id'])->all());
            $id = $theme->get('parent_id');
        }

        return Theme::whereKey($themeId)->exists();
    }
}
