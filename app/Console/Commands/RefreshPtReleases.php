<?php

namespace App\Console\Commands;

use App\Jobs\RefreshPtReleases as RefreshPtReleasesJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lib:refresh-ptreleases')]
#[Description('Refresh ptreleases files.')]
class RefreshPtReleases extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        RefreshPtReleasesJob::dispatch();
    }
}
