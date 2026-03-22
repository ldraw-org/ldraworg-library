<?php

namespace App\View\Components\Card;

use App\Models\Part\PartRelease;
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

    public function __construct()
    {
        $this->update = PartRelease::current();
        $this->officialCount = Cache::get('current_official_part_count', 0);
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
