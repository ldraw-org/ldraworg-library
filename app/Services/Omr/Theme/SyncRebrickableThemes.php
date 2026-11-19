<?php

namespace App\Services\Omr\Theme;

use App\Enums\RebrickableCsvFile;
use App\Models\Omr\Theme;
use App\Services\External\Rebrickable;
use App\Services\External\RebrickableCsv;

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

        $this->handle();

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
