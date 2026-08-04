<?php

namespace App\Services\Part;

use App\Collections\PartCollection;
use App\Jobs\CheckPart;
use App\Jobs\GeneratePartImage;
use App\Jobs\UpdateLibraryCsv;
use App\Jobs\UpdateParentParts;
use App\Jobs\UpdateRebrickable;
use App\Models\Part\Part;

class Finalizer
{
    public function __construct(
        protected SyncSubparts   $subpartSync,
        protected ImageGenerator $imageGenerator,
        protected Validator      $validator,
        protected BasePartSync   $basePartSync,
    ) {}

    public function handle(PartCollection $parts): void
    {
        $parts->load('official_part', 'ancestors');

        $parts->each(fn (Part $p) => $this->subpartSync->loadSubparts($p, true));

        $ancestors = collect();

        $parts->each(function (Part $p) use ($ancestors) {
            $p->updatePartStatus();
            if ($p->official_part !== null) {
                $this->subpartSync->updateUnofficialWithOfficialFix($p->official_part);
            }
            $this->basePartSync->syncBasePart($p);
            $this->validator->checkPart($p);
            $p->updateReadyForAdmin();
            $this->imageGenerator->regenerateImage($p);
            $ancestors->push(...$p->ancestors);
            UpdateParentParts::dispatch($p);
            UpdateRebrickable::dispatch($p);
            CheckPart::dispatch($p->id);
        });

        $partIds = $parts->pluck('id');

        $ancestors
            ->unique('id')
            ->reject(fn (Part $ancestor) => $partIds->contains($ancestor->id))
            ->each(fn (Part $ancestor) => GeneratePartImage::dispatch($ancestor->withoutRelations()));

        UpdateLibraryCsv::dispatch();
    }

}
