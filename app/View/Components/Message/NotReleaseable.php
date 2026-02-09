<?php

namespace App\View\Components\Message;

use App\Models\Part\Part;
use App\Services\Check\CheckMessage;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class NotReleaseable extends Component
{
    /**
     * Create a new component instance.
     */

    public ?Collection $messages;

    public function __construct(Part $part) {
        $this->messages = $part
            ->check_messages
            ->map(fn (CheckMessage $m) => [
                'checkType' => $m->checkType->value,
                'error' => $m->error->value,
                'message' => $m->message(),
                'lineNumber' => $m->lineNumber,
                'text' => $m->text,
            ])
            ->groupBy(['checkType', 'error']);
    }

    protected function makeMessageArray(Part $part): array
    {
        return [];
    }
    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.message.not-releaseable');
    }
}
