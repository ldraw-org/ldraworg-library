<?php

namespace App\Jobs;

use App\Models\Part\Part;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;

#[Timeout(1800)]
#[Queue('maintenance')]
class RecountVotes implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        Part::unofficial()->lazy()->each->updatePartStatus();
    }
}
