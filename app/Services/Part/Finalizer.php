<?php

namespace App\Services\Part;

use App\Collections\PartCollection;
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
        $parts->loadMissing('keywords', 'history', 'body', 'user');
        $parts->each(function (Part $p) {
//            $this->subpartSync->loadSubparts($p);
            $p->generateHeader();
        });
        $parts->load('official_part');
        $parts->each(function (Part $p) {
            $p->updatePartStatus();
            if (!is_null($p->official_part)) {
                $this->subpartSync->updateUnofficialWithOfficialFix($p->official_part);
            };
            $this->basePartSync->syncBasePart($p);
//            $this->imageGenerator->regenerateImage($p);
            $this->validator->checkPart($p);
            $p->updateReadyForAdmin();
            UpdateParentParts::dispatch($p->id);
            UpdateRebrickable::dispatch($p->id);
        });
    }

}
