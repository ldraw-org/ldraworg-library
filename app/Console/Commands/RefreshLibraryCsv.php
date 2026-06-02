<?php

namespace App\Console\Commands;

use App\Services\Support\MakeLibraryCsv;
use Illuminate\Console\Command;

class RefreshLibraryCsv extends Command
{
    protected $signature = 'lib:refresh-csv';

    protected $description = 'Refresh the library CSV file';

    public function handle(MakeLibraryCsv $libraryCsv): void
    {
        $libraryCsv->handle();
    }
}
