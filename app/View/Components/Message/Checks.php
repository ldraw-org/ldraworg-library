<?php

namespace App\View\Components\Message;

use App\Models\Part\Part;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Checks extends Component
{
    /**
     * Create a new component instance.
     */

    public ?Collection $messages;

    public function __construct(public Part $part) {
        $this->messages = $part
            ->check_messages
            ->messageArray();
    }

    public function render(): View|Closure|string
    {
        return view('components.message.checks');
    }
}
