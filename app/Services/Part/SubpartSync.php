<?php

namespace App\Services\Part;

use App\Jobs\UpdateRebrickable;
use App\Models\Part\Part;
use App\Services\Parser\ParsedPartCollection;

class SubpartSync
{
    public function __construct(
        protected ImageGenerator $partImageService,
        protected Validator      $partCheckService,
    )
    {}

    public function loadSubparts(Part $part): void
    {
        $missingBefore = $this->normalizeMissingParts($part->missing_parts);
        $parsed = new ParsedPartCollection($part->body->body);
        $part->setSubparts($parsed->subpartFilenames() ?? []);
        $missingAfter = $this->normalizeMissingParts($part->missing_parts);
        if ($missingBefore !== $missingAfter) {
            $part->refresh();
            $this->partImageService->regenerateImage($part);
            $this->partCheckService->checkPart($part);
            UpdateRebrickable::dispatch($part->id);
            $part->updateReadyForAdmin();
        }
    }

    protected function normalizeMissingParts(?array $missing): array
    {
        return collect($missing)
            ->sort()
            ->values()
            ->all();
    }
}
