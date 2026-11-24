<?php

namespace App\Services\Part\Release;

use App\Models\Part\PartRelease;
use App\Models\User;
use App\Settings\LibrarySettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
    ) {
    }

    /**
     * Run a full parts update: create the release, make the marked parts official,
     * publish the downloads and reset the tracker for the next cycle.
     *
     * @param array<string, string> $extraFiles file contents keyed by library-relative filename
     */
    public function createRelease(User $actor, bool $includeLdconfig = false, array $extraFiles = []): PartRelease
    {
        $this->setTrackerLocked(true);

        Log::debug('Creating Release');
        $statistics = $this->statistics->collect();
        $release = PartRelease::create([
            ...$this->statistics->nextReleaseNumber(),
            'total' => $statistics['total'],
            'new' => $statistics['new'],
            'new_of_type' => $statistics['new_of_type'],
        ]);
        $notesPath = self::STAGING_FOLDER . "/Note{$release->short}CA.txt";
        Storage::put($notesPath, $this->notes->make($release, $statistics));

        Log::debug('Releasing Parts');
        $this->partReleaser->updateExternalSiteKeywords();
        $this->partReleaser->releaseMinorEdits($release, $actor);
        $this->partReleaser->releaseMarkedParts($release, $actor, self::STAGING_FOLDER);

        $this->setTrackerLocked(false);

        Log::debug('Making Zips');
        $this->archiver->publish($release, self::STAGING_FOLDER, $notesPath, $extraFiles, $includeLdconfig);

        Log::debug('Post Release Cleanup');
        $this->cleanup->handle($release);

        Log::debug('Release Complete');

        return $release;
    }

    protected function setTrackerLocked(bool $locked): void
    {
        $this->settings->tracker_locked = $locked;
        $this->settings->save();
    }
}
