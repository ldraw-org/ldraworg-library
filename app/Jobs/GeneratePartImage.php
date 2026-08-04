<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Services\Part\ImageGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Timeout;

#[DeleteWhenMissingModels]
#[Timeout(1800)]
class GeneratePartImage implements ShouldQueue
{
    use Queueable;

    protected Collection $parts;

    public function __construct(
        Part|Collection $parts,
        protected bool $onlyMissing = false
    )
    {
        $this->parts = $parts instanceof Part
            ? new Collection([$parts])
            : $parts;
    }

    /**
     * Execute the job.
     */
    public function handle(ImageGenerator $imageGenerator): void
    {
        $this->parts
            ->reject(fn (Part $part) => $this->onlyMissing && file_exists($part->getFirstMediaPath('image')))
            ->each(fn (Part $part) => $imageGenerator->regenerateImage($part));
    }
}
