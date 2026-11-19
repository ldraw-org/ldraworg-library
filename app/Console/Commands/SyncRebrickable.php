<?php

namespace App\Console\Commands;

use App\Jobs\SyncRebrickableData;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lib:sync-rebrickable
    {--force-download : Download even if the CSVs were downloaded in the last 24 hours (Rebrickable allows once a day)}
    {--skip-download : Resync from the CSVs already on disk}')]
#[Description('Queue a download of the Rebrickable CSVs and a resync of the data taken from them')]
class SyncRebrickable extends Command
{
    public function handle(): void
    {
        SyncRebrickableData::dispatch(
            forceDownload: (bool) $this->option('force-download'),
            skipDownload: (bool) $this->option('skip-download'),
        );
        $this->info('Rebrickable sync queued');
    }
}
