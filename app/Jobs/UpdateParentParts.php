<?php

namespace App\Jobs;

use App\Services\Part\ImageGenerator;
use App\Services\Part\SyncSubparts;
use App\Services\Part\Validator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Part\Part;

#[DeleteWhenMissingModels]
class UpdateParentParts implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected Part $part
    ) {
    }

    public function handle(SyncSubparts $subpartSync, ImageGenerator $imageGenerator, Validator $validator): void
    {
        if (!is_null($this->part->official_part)) {
            $this->part->official_part->parents()->official()->each(
                fn (Part $p) => $subpartSync->loadSubparts($p)
            );
        }
        Part::unofficial()
            ->whereJsonContains('missing_parts', $this->part->meta_name)
            ->each(function (Part $p) use ($subpartSync, $imageGenerator) {
                $subpartSync->loadSubparts($p);
                $imageGenerator->regenerateImage($p);
            });
        $this->part->ancestors()->each(
            function (Part $p) use ($validator, $imageGenerator) {
                $imageGenerator->regenerateImage($p);
                $validator->checkPart($p);
            }
        );
    }
}
