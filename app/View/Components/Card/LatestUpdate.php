<?php

namespace App\View\Components\Card;

use App\Models\Part\PartRelease;
use App\Services\LibraryStatisticsService;
use App\Services\PartReleaseService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;

class LatestUpdate extends Component
{
    /**
     * Create a new component instance.
     */

    public PartRelease $update;
    public int $officialCount;

    public function __construct(
        protected PartReleaseService $releaseService,
        protected LibraryStatisticsService $statistics,
    )
    {
        $this->update = $this->releaseService->currentRelease();
        $this->officialCount = $this->statistics->officialPartCount();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string|null
    {
        if (is_null($this->update)) {
            return null;
        }

        return view('components.card.latest-update');
    }
}
