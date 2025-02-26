<?php

namespace App\View\Components\Part;

use App\Enums\PartStatus;
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
        public array $summary = ['1' => 0, '2' => 0, '3' => 0, '5' => 0],
        public bool $small = true
    ) {
        $this->summary = Part::unofficial()->pluck('part_status')->countBy(fn (PartStatus $p) => $p->value)->all();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.part.unofficial-part-count');
    }
}
