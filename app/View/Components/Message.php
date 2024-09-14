<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Message extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $type,
        public ?string $header = null,
        public ?string $message = null,
        public bool $centered = false,
        public bool $compact = false,
        public bool $icon = false,
    ) {
        if (in_array($this->type, ['warning', 'error', 'info']) === false) {
            $this->type = 'info';
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.message.index');
    }
}
