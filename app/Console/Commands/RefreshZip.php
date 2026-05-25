<?php

namespace App\Console\Commands;

use App\Services\LDraw\ZipFiles;
use App\Models\Part\Part;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RefreshZip extends Command
{
    protected $signature = 'lib:refresh-zip';

    protected $description = 'Refresh the unofficial zip file';

    public function handle(ZipFiles $zipfiles): void
    {
        Storage::delete('library/unofficial/ldrawunf.zip');
        $zipfiles->unofficialZip(Part::unofficial()->first());
    }
}
