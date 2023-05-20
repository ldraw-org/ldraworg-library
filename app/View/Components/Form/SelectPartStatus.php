<?php

namespace App\View\Components\Form;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SelectPartStatus extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public $label = "Status",
        public $placeholder = "Status",
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $options = [
            'certified' => 'Certified', 
            'adminreview' => 'Needs Admin Review', 
            'memberreview' => 'Needs More Votes', 
            'needsubfile' => 'Uncertified Subfiles', 
            'held' => 'Hold', 
            '2certvotes' => '2 (or more) Certify Votes', 
            '1certvote' => '1 Certify Vote'
        ];
        return view('components.form.select-part-status', compact('options'));
    }
}