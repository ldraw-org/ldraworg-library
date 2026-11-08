<?php

namespace App\Jobs;

use App\Services\Support\MakeLibraryCsv;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;

#[Timeout(1800)]
#[Queue('maintenance')]
class UpdateLibraryCsv implements ShouldQueue
{
    use Queueable;

    public function handle(MakeLibraryCsv $libraryCsv): void
    {
        $libraryCsv->handle();
    }
}
