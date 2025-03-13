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
        if ($this->p instanceof Part) {
            $this->p = new Collection([$this->p]);
        }
        $pm = app(PartManager::class);
        $this->p->each(fn (Part $part) => $pm->checkPart($part));
    }
}
