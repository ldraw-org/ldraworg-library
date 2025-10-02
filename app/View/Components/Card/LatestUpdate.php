<?php

namespace App\View\Components\Card;

use App\Models\Part\PartRelease;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LatestUpdate extends Component
{
    /**
     * Create a new component instance.
     */

    public PartRelease $update;

    public function __construct()
    {
        $this->update = PartRelease::current();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.card.latest-update');
    }
}
