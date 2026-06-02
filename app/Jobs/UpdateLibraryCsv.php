<?php

namespace App\Jobs;

use App\Services\LDraw\SupportFiles;
use App\Services\Support\MakeLibraryCsv;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateLibraryCsv implements ShouldQueue
{
    use Queueable;

    public function handle(MakeLibraryCsv $libraryCsv): void
    {
        $libraryCsv->handle();
    }
}
