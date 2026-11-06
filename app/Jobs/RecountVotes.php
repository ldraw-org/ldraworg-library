<?php

namespace App\Jobs;

use App\Models\Part\Part;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
