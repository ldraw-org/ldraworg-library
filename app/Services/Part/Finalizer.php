<?php

namespace App\Services\Part;

use App\Collections\PartCollection;
use App\Jobs\GeneratePartImage;
use App\Jobs\UpdateLibraryCsv;
use App\Jobs\UpdateParentParts;
use App\Jobs\UpdateRebrickable;
use App\Models\Part\Part;

class Finalizer
{
    public function __construct(
        protected SyncSubparts   $subpartSync,
        protected Validator      $validator,
        protected BasePartSync   $basePartSync,
        protected GenerateHeader $generateHeader,
    ) {
    }

    /**
     * Rebuilds everything derived from the parts' saved header data and body. Safe to run again on the same parts.
     */
    public function handle(PartCollection $parts): void
    {
        $parts->load('official_part', 'ancestors');

        $parts->each(fn (Part $p) => $this->subpartSync->loadSubparts($p, true));

        $imageParts = collect();

        $parts->each(function (Part $p) use ($imageParts) {
            $p->updatePartStatus();
            if ($p->official_part !== null) {
                $this->subpartSync->updateUnofficialWithOfficialFix($p->official_part);
            }
            $this->basePartSync->syncBasePart($p);
            // Regenerated before checking so the checked file text has the now-attached keywords and history
            $this->generateHeader->updatePartHeader($p);
            $this->validator->checkPart($p);
            $imageParts->push($p, ...$p->ancestors);
            UpdateParentParts::dispatch($p->withoutRelations());
            UpdateRebrickable::dispatch($p->withoutRelations());
        });

        $imageParts
            ->unique('id')
            ->each(fn (Part $part) => GeneratePartImage::dispatch($part->withoutRelations()));

        UpdateLibraryCsv::dispatch();
    }

}
