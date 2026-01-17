<?php

namespace App\Jobs;

use App\Models\Part\Part;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BuildSearchText implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Part::with(['history', 'keywords'])->cursor()->each(fn (Part $p) => $p->setSearchText());
    }
}
