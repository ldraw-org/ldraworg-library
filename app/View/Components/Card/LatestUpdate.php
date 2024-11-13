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

    public string $blurb;
    public PartRelease $update;

    public function __construct()
    {
        $this->update = PartRelease::current();
        $data = $this->update->part_data;
        $prims = 0;
        $parts = 'no';
        foreach ($data['new_types'] as $t) {
            if (strpos($t['name'], 'Primitive') !== false) {
                $prims += $t['count'];
            }
            if ($t['name'] == 'Part') {
                $parts = $t['count'];
            }

        }
        $prims = $prims > 0 ? $prims : 'no';
        $this->blurb = "This update adds {$data['new_files']} new files to the core library, including {$parts} new parts and {$prims} new primitives.";
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.card.latest-update');
    }
}
