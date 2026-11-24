<?php

namespace App\View\Components\Message;

use App\Services\Part\Release\ReleaseProgress;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ReleaseRunning extends Component
{
    public function __construct(
        protected ReleaseProgress $progress,
    ) {
    }

    public function shouldRender(): bool
    {
        return $this->progress->isRunning();
    }

    public function render(): View|Closure|string
    {
        return view('components.message.release-running');
    }
}
