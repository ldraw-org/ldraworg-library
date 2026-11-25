<?php

namespace App\Livewire\Release;

use App\Enums\ReleaseStage;
use App\Models\Part\PartRelease;
use App\Models\Part\PartReleaseRun;
use App\Services\Part\Release\ReleaseProgress;
use Filament\Notifications\Notification;
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

    /** Session key holding the start time of the completed run this user dismissed */
    protected const string DISMISSED_KEY = 'release-progress-dismissed';

    /**
     * The current run while it is running or failed, or for a day after it completed unless dismissed.
     */
    #[Computed]
    public function run(): ?PartReleaseRun
    {
        $run = app(ReleaseProgress::class)->current();

        if (is_null($run) || $run->stage !== ReleaseStage::Complete) {
            return $run;
        }

        $isDismissed = session(self::DISMISSED_KEY) === $run->created_at->getTimestamp();

        return !$isDismissed && $run->updated_at->isAfter(now()->subDay()) ? $run : null;
    }

    /**
     * Hide a completed release's summary for this user; the next release shows again.
     */
    public function dismiss(): void
    {
        if ($this->run?->stage === ReleaseStage::Complete) {
            session([self::DISMISSED_KEY => $this->run->created_at->getTimestamp()]);
        }
        unset($this->run);
    }

    public function clearLockout(): void
    {
        $this->authorize('clearLockout', PartRelease::class);

        $cleared = app(ReleaseProgress::class)->clearLockout();
        unset($this->run);

        Notification::make()
            ->title($cleared ? 'Release lock-out cleared' : 'There is no failed or stalled release to clear')
            ->status($cleared ? 'success' : 'warning')
            ->send();
    }

    public function render(): View
    {
        return view('livewire.release.progress');
    }
}
