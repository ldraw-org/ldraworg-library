<?php

namespace App\Services\Part;

use App\Enums\PreviewRotation;
use App\Jobs\GeneratePartImage;
use App\Models\Part\Part;

class PreviewSync
{
    public function __construct(
        protected GenerateHeader $generateHeader,
    ) {
    }

    public function updatePartPreview(Part $part, ?PreviewRotation $preview): void
    {
        $preview ??= PreviewRotation::Default;

        if ($part->preview === $preview) {
            return;
        }
        $part->preview = $preview;
        $part->has_minor_edit = true;
        $this->generateHeader->updatePartHeader($part);
        $part->save();
        GeneratePartImage::dispatch($part->withoutRelations());
    }
}
