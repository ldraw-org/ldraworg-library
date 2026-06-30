<?php

namespace App\Console\Commands;

use App\Services\DB\DatabaseBackup;
use Illuminate\Console\Command;

class RefreshDB extends Command
{
    protected $signature = 'lib:refresh-db';

    protected $description = 'Refresh the local DB';

    public function handle(DatabaseBackup $databaseBackup): void
    {
        if (app()->environment('local')) {
            $databaseBackup->restore();
            $this->call('migrate');
        } else {
            $this->info('This command cannot be run the the production environment');
        }
    }
}
