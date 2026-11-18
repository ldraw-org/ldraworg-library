<?php

namespace App\Services\Omr\Theme;

use App\Models\Omr\Theme;
use App\Services\External\Rebrickable;

class SyncRebrickableThemes
{
    public function __construct(
        protected Rebrickable $rebrickable
    ) {}

    public function handle(): void
    {
        $themes = $this->rebrickable->getThemes(fresh: true);
        foreach ($themes as $theme) {
            Theme::updateOrCreate(['id' => $theme['id']], $theme);
        }
    }
}
