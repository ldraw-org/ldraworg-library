<?php

namespace App\Console\Commands;

use App\Jobs\UpdateLibraryCsv;
use App\Services\LDraw\SupportFiles;
use Illuminate\Console\Command;

class RefreshLibraryCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:refresh-csv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the library CSV file';
    
    public function handle(SupportFiles $support)
    {
        $support->setLibraryCsv();
    }
}
