<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Part\Part;
use App\LDraw\Managers\Part\PartManager;

class UpdateParentParts implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        protected Part $part
    ) {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $pm = app(PartManager::class);
        if (!is_null($this->part->official_part)) {
            $this->part->official_part->parents()->official()->each(
                fn (Part $p) => $pm->loadSubparts($p)
            );
        }
        Part::unofficial()
            ->whereJsonContains('missing_parts', $this->part->name())
            ->each(function (Part $p) use ($pm) {
                $pm->loadSubparts($p);
                $pm->updateImage($p);
            });
        $this->part->ancestors()->each(
            function (Part $p) use ($pm) {
                $pm->updateImage($p);
                $pm->checkPart($p);
            }
        );
    }
}
