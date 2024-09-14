<?php

namespace App\Jobs;

use App\Models\Part;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;

class UpdatePartHeader implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Collection $parts
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->parts->each(function (Part $part) {
            $part->generateHeader();
            if (!$part->isUnofficial()) {
                $md = $part->minor_edit_data;
                $md['header'] = 'Header edited';
                $part->minor_edit_data = $md;
                $part->save();
            }
        });
    }
}
