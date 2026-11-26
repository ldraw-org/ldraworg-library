<?php

namespace App\Console\Commands;

use App\Jobs\DeleteOrphanMedia as DeleteOrphanMediaJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lib:delete-orphan-media')]
#[Description('Delete media whose owning model no longer exists.')]
class DeleteOrphanMedia extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        DeleteOrphanMediaJob::dispatch();
        $this->info('Orphaned media deletion queued');
    }
}
