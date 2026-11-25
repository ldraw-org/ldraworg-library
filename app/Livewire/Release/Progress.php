<?php

namespace App\Livewire\Release;

use App\Enums\ReleaseStage;
use App\Models\Part\PartRelease;
use App\Models\Part\PartReleaseRun;
use App\Services\Part\Release\ReleaseProgress;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The milestones of the current parts release for release admins, polled while the release runs.
 */
class Progress extends Component
{
    public function mount(): void
    {
        $this->authorize('create', PartRelease::class);
    }

    /**
     * The current run while it is running or failed, or for a day after it completed.
     */
    #[Computed]
    public function run(): ?PartReleaseRun
    {
        $run = app(ReleaseProgress::class)->current();

        return !is_null($run) && ($run->stage !== ReleaseStage::Complete || $run->updated_at->isAfter(now()->subDay())) ? $run : null;
    }

    public function render(): View
    {
        return view('livewire.release.progress');
    }
}
