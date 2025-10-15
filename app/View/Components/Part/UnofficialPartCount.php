<?php

namespace App\View\Components\Part;

use Illuminate\Contracts\View\View;
use Closure;
use Illuminate\View\Component;
use App\Models\Part\Part;

class UnofficialPartCount extends Component
{
    /**
       * Create a new component instance.
       *
       * @return void
       */
    public function __construct(
        public array $summary = [],
        public bool $small = true
    ) {
        $this->summary = Part::select('part_status', 'id')->unofficial()->get()->countBy('part_status')->all();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|Closure|string
     */
    public function render()
    {
        return view('components.part.unofficial-part-count');
    }
}
