<?php

namespace App\Services\Part\Release;

use App\Enums\ReleaseStage;
use App\Jobs\BackupDatabaseOnly;
use App\Jobs\MakePartRelease;
use App\Jobs\SyncRebrickableData;
use App\Models\Part\PartRelease;
use App\Models\Part\PartReleaseRun;
use App\Models\User;
use App\Settings\LibrarySettings;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PartReleaseManager
{
    protected const string STAGING_FOLDER = 'release-staging';

    public function __construct(
        protected LibrarySettings $settings,
        protected ReleaseStatistics $statistics,
        protected ReleaseNotes $notes,
        protected PartReleaser $partReleaser,
        protected ReleaseArchiver $archiver,
        protected PostReleaseCleanup $cleanup,
        protected ReleaseProgress $progress,
    ) {
    }

    /**
     * Queue a release behind a full Rebrickable sync and a database backup; a failure in any step stops
     * the chain and marks the run failed. The sync goes first: as the chain's first job its unique lock
     * is not checked, so a weekly sync already holding it cannot silently drop the chain.
     *
     * @param array<string, string> $extraFiles file contents keyed by library-relative filename
     * @return ?PartReleaseRun null when a release is already running
     */
    public function queueRelease(User $actor, bool $includeLdconfig = false, array $extraFiles = []): ?PartReleaseRun
    {
        $run = $this->progress->start($actor);
        if (is_null($run)) {
            return null;
        }

        $runId = $run->id;
        Bus::chain([
            new SyncRebrickableData(releaseRun: $run),
            new BackupDatabaseOnly($run),
            new MakePartRelease($actor, $includeLdconfig, $extraFiles, $run),
        ])
            ->catch(fn (Throwable $e) => app(ReleaseProgress::class)->fail($runId, $e->getMessage()))
            ->dispatch();

        return $run;
    }

    /**
     * Run a full parts update: create the release, make the marked parts official,
     * publish the downloads and reset the tracker for the next cycle.
     *
     * @param array<string, string> $extraFiles file contents keyed by library-relative filename
     */
    public function createRelease(
        User $actor,
        bool $includeLdconfig = false,
        array $extraFiles = [],
        ?PartReleaseRun $run = null,
    ): PartRelease {
        $this->setTrackerLocked(true);

        Log::debug('Creating Release');
        $statistics = $this->statistics->collect();
        $this->progress->stage($run, ReleaseStage::ReleasingParts, $statistics['total']);
        $release = PartRelease::create([
            ...$this->statistics->nextReleaseNumber(),
            'total' => $statistics['total'],
            'new' => $statistics['new'],
            'new_of_type' => $statistics['new_of_type'],
        ]);
        $this->progress->attachRelease($run, $release);
        $notesPath = self::STAGING_FOLDER . "/Note{$release->short}CA.txt";
        Storage::put($notesPath, $this->notes->make($release, $statistics));

        Log::debug('Releasing Parts');
        $this->partReleaser->updateExternalSiteKeywords();
        $this->partReleaser->releaseMinorEdits($release, $actor);
        $this->partReleaser->releaseMarkedParts(
            $release,
            $actor,
            self::STAGING_FOLDER,
            fn (int $released) => $this->progress->advance($run, $released),
        );

        $this->setTrackerLocked(false);

        Log::debug('Making Zips');
        $this->progress->stage($run, ReleaseStage::MakingZips);
        $this->archiver->publish($release, self::STAGING_FOLDER, $notesPath, $extraFiles, $includeLdconfig);

        Log::debug('Post Release Cleanup');
        $this->progress->stage($run, ReleaseStage::Cleanup);
        $this->cleanup->handle($release);

        $this->progress->complete($run);
        Log::debug('Release Complete');

        return $release;
    }

    protected function setTrackerLocked(bool $locked): void
    {
        $this->settings->tracker_locked = $locked;
        $this->settings->save();
    }
}
