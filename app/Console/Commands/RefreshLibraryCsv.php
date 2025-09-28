<?php

namespace App\Console\Commands;

use App\LDraw\SupportFiles;
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

    /**
     * Execute the console command.
     */
    public function handle()
    {
        app(SupportFiles::class)->setLibraryCsv();
    }
}
