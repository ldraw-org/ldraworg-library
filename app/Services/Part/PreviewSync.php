<?php

namespace App\Services\Part;

use App\Enums\PreviewRotation;
use App\Models\Part\Part;

class PreviewSync
{
    public function __construct(
        protected ImageGenerator $imageGenerator,
    ) {}

    public function updatePartPreview(Part $part, ?PreviewRotation $preview): void
    {
        if ($part->preview === $preview) {
            return;
        }
        $part->preview = $preview;
        $part->has_minor_edit = true;
        $part->save();
        $part->generateHeader();
        $this->imageGenerator->regenerateImage($part);
    }
}
