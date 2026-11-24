<?php

namespace App\Jobs;

use App\Enums\ReleaseStage;
use App\Models\Part\PartReleaseRun;
use App\Services\Part\Release\ReleaseProgress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Spatie\Backup\Config\Config;
use Spatie\Backup\Tasks\Backup\BackupJobFactory;

/**
 * Runs the spatie backup for the databases only, the equivalent of `backup:run --only-db`.
 * Throws when the backup fails, so a chain waiting on it stops.
 */
#[Timeout(1800)]
#[Tries(1)]
#[Queue('release')]
class BackupDatabaseOnly implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?PartReleaseRun $releaseRun = null,
    ) {
    }

    public function handle(Config $config, ReleaseProgress $progress): void
    {
        $progress->stage($this->releaseRun, ReleaseStage::BackingUp);
        BackupJobFactory::createFromConfig($config)
            ->dontBackupFilesystem()
            ->disableSignals()
            ->run();
    }
}
