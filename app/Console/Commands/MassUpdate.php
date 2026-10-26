<?php

namespace App\Console\Commands;

use App\Services\Part\Release\ReleaseImageGenerator;
use Illuminate\Console\Command;

class MassUpdate extends Command
{
    protected $signature = 'lib:mass-update';

    protected $description = 'A command to do focused mass updates';

    public function handle(ReleaseImageGenerator $makeReleaseImage): void
    {
    }
}
