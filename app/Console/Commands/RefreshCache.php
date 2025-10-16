<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RefreshCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh app cache after code update';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->call('down');
        try {
            $this->call('optimize:clear');
            $this->call('optimize');
          
        } finally {
            $this->call('up');
        }
        $this->call('queue:restart');
    }
}
