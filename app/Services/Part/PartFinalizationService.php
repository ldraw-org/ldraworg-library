<?php

namespace App\Services\Part;

use App\Models\Part\Part;

class PartFinalizationService
{
    
    public function finalizePart(Part $part): void
    {
        $part->loadMissing('keywords', 'history', 'body', 'user', 'official_part');
        $this->loadSubparts($part);
        $part->generateHeader();
        $part->updatePartStatus();
        if (!is_null($part->official_part)) {
            $this->updateUnofficialWithOfficialFix($part->official_part);
        };
        $this->updateBasePart($part);
        $this->updateImage($part);
        $this->checkPart($part);
        $part->updateReadyForAdmin();
        $this->addUnknownNumber($part);
        UpdateImage::dispatch($part);
        UpdateParentParts::dispatch($part);
        UpdateRebrickable::dispatch($part);
    }

    public function updateImage(Part $part, bool $async = true): void
    {
        $dir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $imagePath = $dir->path(substr($part->filename, 0, -4) . '.png');

        if ($part->isTexmap()) {
            $image = imagecreatefromstring($part->get());
            imagepng($image, $imagePath);
            $this->imageOptimize($imagePath);
            return;
        }
        
        $image = $this->render->render($part, $async);
        if (!is_null($image)) {
            imagepng($image, $imagePath);
            $part->clearMediaCollection('image');
            $part->addMedia($imagePath)
                ->toMediaCollection('image');
        }
    }

    protected function updateMissing(string $name): void
    {
        Part::unofficial()
            ->whereJsonContains('missing_parts', $name)
            ->each(function (Part $p) {
                $this->loadSubparts($p);
            });
    }

    protected function updateUnofficialWithOfficialFix(Part $officialPart): void
    {
        Part::unofficial()->whereHas('subparts', function (Builder $query) use ($officialPart) {
            return $query->where('id', $officialPart->id);
        })->each(function (Part $p) {
            $this->loadSubparts($p);
        });
    }

    public function updateBasePart(Part $part): void
    {
        if (!$part->type->inPartsFolder() || $part->category == PartCategory::Moved || $part->isObsolete()) {
            return;
        }
        $name = new ParsedPartCollection("0 Name: {$part->meta_name}\n" . $part->type->ldrawString(true));
        $base = $name->basepart();
        if (is_null($base) || ("{$base}.dat" == $part->meta_name || "{$base}-f1.dat" == $part->meta_name)) {
            $part->base_part()->disassociate();
            $part->is_pattern = false;
            $part->is_composite = false;
            $part->save();
            return;
        }

        $bp = Part::doesntHave('official_part')
            ->where(
                fn ($q) => $q
                    ->orWhere('filename', "parts/{$base}.dat")
                    ->orWhere('filename', "parts/{$base}-f1.dat")
            )
            ->first();

        if (!is_null($bp)) {
            $part->base_part()->associate($bp);
        }

        $part->is_pattern = $name->isPattern();
        $part->is_composite = $name->isComposite();
        if ($part->isDirty()) {
            $part->save();
        }
    }

}