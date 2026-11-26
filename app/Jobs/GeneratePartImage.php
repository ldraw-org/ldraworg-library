<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Services\Part\ImageGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Support\Facades\Log;
use Throwable;

#[DeleteWhenMissingModels]
#[Timeout(1800)]
#[Queue('library')]
class GeneratePartImage implements ShouldQueue
{
    use Queueable;

    protected Collection $parts;

    public function __construct(Part|Collection $parts)
    {
        $this->parts = $parts instanceof Part
            ? new Collection([$parts])
            : $parts;
    }

    /**
     * Render each part's image. A part that fails is logged and skipped so it can't block the rest of the batch.
     */
    public function handle(ImageGenerator $imageGenerator): void
    {
        $this->parts->each(function (Part $part) use ($imageGenerator): void {
            try {
                $imageGenerator->regenerateImage($part);
            } catch (Throwable $e) {
                Log::error('Part image generation failed', ['part_id' => $part->id, 'filename' => $part->filename, 'exception' => $e]);
            }
        });
    }
}
