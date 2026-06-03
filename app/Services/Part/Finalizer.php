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

        $ancestorIds = collect();

        $parts->each(function (Part $p) use ($ancestorIds) {
            $p->updatePartStatus();
            if ($p->official_part !== null) {
                $this->subpartSync->updateUnofficialWithOfficialFix($p->official_part);
            }
            $this->basePartSync->syncBasePart($p);
            $this->validator->checkPart($p);
            $p->updateReadyForAdmin();
            $this->imageGenerator->regenerateImage($p);
            $ancestorIds->push(...$p->ancestors->pluck('id'));
            UpdateParentParts::dispatch($p->id);
            UpdateRebrickable::dispatch($p->id);
            CheckPart::dispatch($p->id);
        });

        $ancestorIds
            ->diff($parts->pluck('id'))
            ->unique()
            ->each(fn (int $id) => GeneratePartImage::dispatch($id));

        UpdateLibraryCsv::dispatch();
    }

}
