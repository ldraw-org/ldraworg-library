<?php

namespace App\Services\Part\Release;

use App\Enums\ReleaseStage;
use App\Models\Part\PartRelease;
use App\Models\Part\PartReleaseRun;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Records the milestones of the current parts release for the frontend to poll.
 * Every method taking a run does nothing when given none, so release code can run without tracking.
 */
class ReleaseProgress
{
    /** Part counts are written every this many parts rather than for each one */
    protected const int ADVANCE_EVERY = 25;

    public function current(): ?PartReleaseRun
    {
        return PartReleaseRun::find(PartReleaseRun::CURRENT_ID);
    }

    public function isRunning(): bool
    {
        return PartReleaseRun::running()->exists();
    }

    /**
     * Whether new releases are locked out: one is running, or one failed and has not been cleared.
     * A failed release is left for a manual database rollback rather than retried.
     */
    public function isLockedOut(): bool
    {
        return PartReleaseRun::incomplete()->exists();
    }

    /**
     * Claim the run for a new release, replacing a completed one.
     * Both writes are single atomic statements, so two starts at once cannot both succeed.
     *
     * @return ?PartReleaseRun null when releases are locked out
     */
    public function start(User $actor): ?PartReleaseRun
    {
        $values = [
            'user_id' => $actor->id,
            'part_release_id' => null,
            'stage' => ReleaseStage::Queued->value,
            'progress_current' => null,
            'progress_total' => null,
            'error' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $claimed = PartReleaseRun::insertOrIgnore(['id' => PartReleaseRun::CURRENT_ID, ...$values]) === 1
            || PartReleaseRun::whereKey(PartReleaseRun::CURRENT_ID)->complete()->update($values) === 1;

        return $claimed ? $this->current() : null;
    }

    /**
     * @param ?int $total the number of items the stage works through, when it reports a count
     */
    public function stage(?PartReleaseRun $run, ReleaseStage $stage, ?int $total = null): void
    {
        $run?->update([
            'stage' => $stage,
            'progress_current' => is_null($total) ? null : 0,
            'progress_total' => $total,
        ]);
    }

    public function advance(?PartReleaseRun $run, int $current): void
    {
        if (!is_null($run) && ($current % self::ADVANCE_EVERY === 0 || $current === $run->progress_total)) {
            $run->update(['progress_current' => $current]);
        }
    }

    public function attachRelease(?PartReleaseRun $run, PartRelease $release): void
    {
        $run?->update(['part_release_id' => $release->id]);
    }

    public function complete(?PartReleaseRun $run): void
    {
        $this->stage($run, ReleaseStage::Complete);
    }

    public function fail(int $runId, string $error): void
    {
        PartReleaseRun::whereKey($runId)->update(['error' => Str::limit($error, 1000)]);
    }
}
