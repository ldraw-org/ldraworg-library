<?php

namespace App\View\Components\OmrModel;

use App\Models\Omr\OmrModel;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Latest extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public int $limit = 5
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $models = OmrModel::latest()->limit($this->limit)->get();
        return view('components.omr-model.latest', compact('models'));
    }
}
