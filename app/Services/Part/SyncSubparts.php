<?php

namespace App\Services\Part;

use App\Jobs\UpdateRebrickable;
use App\Models\Part\Part;
use App\Services\Parser\ParsedPartCollection;
use Illuminate\Database\Eloquent\Builder;

class SyncSubparts
{
    public function __construct(
        protected ImageGenerator $partImageService,
        protected Validator      $partCheckService,
    )
    {}

    public function loadSubparts(Part $part, bool $quiet = false): void
    {
        $missingBefore = $this->normalizeMissingParts($part->missing_parts);
        $parsed = new ParsedPartCollection($part->body->body);
        $part->setSubparts($parsed->subpartFilenames() ?? []);
        $missingAfter = $this->normalizeMissingParts($part->missing_parts);
        if (!$quiet  && $missingBefore !== $missingAfter) {
            $part->refresh();
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

    public function updateMissing(string $name): void
    {
        Part::unofficial()
            ->whereJsonContains('missing_parts', $name)
            ->each(function (Part $p) {
                $this->loadSubparts($p);
            });
    }

    public function updateUnofficialWithOfficialFix(Part $officialPart): void
    {
        Part::unofficial()->whereHas('subparts', function (Builder $query) use ($officialPart) {
            return $query->where('id', $officialPart->id);
        })->each(function (Part $p) {
            $this->loadSubparts($p);
        });
    }

}
