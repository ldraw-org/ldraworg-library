<?php

namespace App\Console\Commands;

use App\LDraw\ZipFiles;
use App\Models\Part;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RefreshZip extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pt:refresh-zip';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the unofficial zip file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Storage::disk('library')->delete('unofficial/ldrawunf.zip');
        ZipFiles::unofficialZip(Part::unofficial()->first());
    }
}
