<?php

namespace App\Console\Commands;

use App\Services\DB\DatabaseBackup;
use Illuminate\Console\Command;

class RefreshDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:refresh-db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the local DB';

    /**
     * Execute the console command.
     */
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
