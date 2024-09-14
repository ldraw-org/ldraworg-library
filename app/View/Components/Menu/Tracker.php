<?php

namespace App\View\Components\Menu;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Tracker extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        // Nothing yet
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $summaries = \App\Models\ReviewSummary\ReviewSummary::ordered()->get();
        return view('components.menu.tracker', compact('summaries'));
    }
}
