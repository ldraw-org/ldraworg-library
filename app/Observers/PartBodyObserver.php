<?php

namespace App\Observers;

use App\Jobs\CheckPart;
use App\Jobs\GeneratePartImage;
use App\Jobs\UpdateImage;
use App\Models\Part\Part;
use App\Models\Part\PartBody;
use App\Services\Part\ImageGenerator;
use App\Services\Part\SyncSubparts;
use App\Services\Part\SyncUnknownPartNumber;
use Illuminate\Support\Facades\Log;

class PartBodyObserver
{
    public function __construct(
        protected ImageGenerator $imageGenerator,
        protected SyncSubparts   $subpartSync,
    ) {}
    public function saved(PartBody $body): void
    {
        if ($body->wasRecentlyCreated || $body->wasChanged('body')) {
            $this->subpartSync->loadSubparts($body->part);
            $this->imageGenerator->regenerateImage($body->part);
            $body->part->ancestors->each(fn (Part $part) => GeneratePartImage::dispatch($part->id));
            CheckPart::dispatch($body->part->id);
        }
    }
}
