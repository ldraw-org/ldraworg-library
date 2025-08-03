<?php

namespace App\Jobs;

use App\LDraw\PartManager;
use App\Models\Part\Part;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Nightwatch\Facades\Nightwatch;

class CheckPart implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Part|Collection $p
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Nightwatch::sample(rate: 0.2);
        
        $pm = app(PartManager::class);

        if ($this->p instanceof Part) {
            $pm->checkPart($this->p);
        } else {
            $this->p->load('user', 'history', 'body');
            $this->p->each(fn (Part $part) => $pm->checkPart($part));
        }
    }
}
