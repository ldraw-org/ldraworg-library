<?php

namespace App\View\Components\Message;

use App\Models\Part\Part;
use App\Services\Check\CheckMessage;
use App\Services\Check\CheckMessageCollection;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class SubmitValidation extends Component
{
    /**
     * Create a new component instance.
     */
    public Collection $messages;
    public string $filename;
    public function __construct(
        string $filename,
        Collection $messages
    ) {
        $this->filename = $filename;
        $this->messages = $messages
            ->arrayByType();
    }

    public function render(): View|Closure|string
    {
        return view('components.message.not-releaseable');
    }
}
