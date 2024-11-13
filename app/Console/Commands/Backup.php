<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class Backup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:backup {--db-only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Makes a backup package';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('db-only')) {
            $this->call('backup:run');
        } else {
            $this->call('backup:run --only-db');
        }
    }
}
