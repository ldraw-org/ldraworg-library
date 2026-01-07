<?php

namespace App\Services\Part;

use App\Jobs\CheckPart;
use App\Jobs\UpdateImage;
use App\Jobs\UpdateRebrickable;
use App\Models\Part\Part;
use App\Services\Parser\ParsedPartCollection;

class PartSubpartService
{
    public function loadSubparts(Part $part): void
    {
        $hadMissing = is_array($part->missing_parts) && count($part->missing_parts) > 0;
        $part->setSubparts((new ParsedPartCollection($part->body->body))->subpartFilenames() ?? []);
        if ($hadMissing) {
            UpdateImage::dispatch($part);
            CheckPart::dispatch($part);
            UpdateRebrickable::dispatch($part);
            $part->updateReadyForAdmin();
        }
    }
}