<?php

namespace App\Console\Commands;

use App\Services\LDraw\SupportFiles;
use Illuminate\Console\Command;

class RefreshLibraryCsv extends Command
{
    protected $signature = 'lib:refresh-csv';

    protected $description = 'Refresh the library CSV file';

    public function handle(SupportFiles $support): void
    {
        $support->setLibraryCsv();
    }
}
