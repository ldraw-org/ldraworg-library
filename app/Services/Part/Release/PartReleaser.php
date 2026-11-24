<?php

namespace App\Services\Part\Release;

use App\Events\PartReleased;
use App\Models\Part\Part;
use App\Models\Part\PartEvent;
use App\Models\Part\PartRelease;
use App\Models\User;
use App\Services\Part\BasePartSync;
use App\Services\Part\GenerateHeader;
use App\Services\Part\RebrickableSync;

class PartReleaser
{
    public function __construct(
        protected AddViewImage $addViewImage,
        protected GenerateHeader $generateHeader,
        protected BasePartSync $basePartSync,
        protected RebrickableSync $rebrickableSync,
    ) {
    }

    /**
     * Make every unofficial part marked for release official.
     * Iterates by id because fixes are deleted as they are released.
     */
    public function releaseMarkedParts(PartRelease $release, User $actor, string $stagingFolder): void
    {
        Part::unofficial()
            ->where('marked_for_release', true)
            ->lazyById()
            ->each(fn (Part $part) => $this->releasePart($part, $release, $actor, $stagingFolder));
    }

    /**
     * Fold official parts with pending header-only edits into the release.
     * Must run before releaseMarkedParts(): a part whose fix is in this update
     * is excluded only while that fix still exists.
     * Iterates by id because each part drops out of the query once released.
     */
    public function releaseMinorEdits(PartRelease $release, User $actor): void
    {
        Part::releasableMinorEdits()
            ->lazyById()
            ->each(function (Part $part) use ($release, $actor): void {
                $this->addHistoryLine($part, $actor, 'Minor header edits');
                $this->addHistoryLine($part, $actor, "Official Update {$release->name}");
                $part->part_release_id = $release->id;
                $part->has_minor_edit = false;
                $this->generateHeader->updatePartHeader($part);
                $part->save();
            });
    }

    /**
     * Refresh the external site keywords of every part about to be released, so the
     * regenerated release headers carry them. Unlinked marked parts are linked first,
     * as the Rebrickable sync leaves them to the release.
     */
    public function updateExternalSiteKeywords(): void
    {
        Part::canHaveRebrickablePart()
            ->where(
                fn ($query) => $query
                    ->where(fn ($marked) => $marked->unofficial()->where('marked_for_release', true))
                    ->orWhere(fn ($minorEdit) => $minorEdit->releasableMinorEdits())
            )
            ->lazyById()
            ->each(function (Part $part): void {
                if ($part->isUnofficial() && is_null($part->rebrickable_part_id) && $part->canSetRebrickablePart()) {
                    $this->rebrickableSync->link($part);
                }
                $part->setExternalSiteKeywords(true);
            });
    }

    protected function releasePart(Part $part, PartRelease $release, User $actor, string $stagingFolder): void
    {
        $this->addHistoryLine($part, $actor, "Official Update {$release->name}");

        PartEvent::unofficial()->where('part_id', $part->id)->update(['part_release_id' => $release->id]);

        PartReleased::dispatch($part->id, $actor->id, $release->id, $release->name);

        if ($part->isFix()) {
            $this->releaseFix($part, $release);
        } else {
            $this->releaseNewPart($part, $release, $stagingFolder);
        }
    }

    protected function releaseFix(Part $part, PartRelease $release): void
    {
        $officialPart = $part->official_part;
        $officialPart->fill([
            'description' => $part->description,
            'filename' => $part->filename,
            'user_id' => $part->user_id,
            'type' => $part->type,
            'type_qualifier' => $part->type_qualifier,
            'part_release_id' => $release->id,
            'license' => $part->license,
            'bfc' => $part->bfc,
            'category' => $part->category,
            'cmdline' => $part->cmdline,
            'help' => $part->help,
            'header' => $part->header,
            'rebrickable_part_id' => $part->rebrickable_part_id,
            'preview' => $part->preview,
        ]);
        $officialPart->setSubparts($part->subparts);
        $officialPart->setKeywords($part->keywords->pluck('keyword')->values()->all());
        $officialPart->setHistory($part->history);
        $officialPart->setBody($part->body);
        $this->basePartSync->syncBasePart($officialPart);
        $this->generateHeader->updatePartHeader($officialPart);
        $officialPart->save();

        PartEvent::where('part_release_id', $release->id)
            ->where('part_id', $part->id)
            ->update(['part_id' => $officialPart->id]);

        $part->deleteQuietly();
    }

    protected function releaseNewPart(Part $part, PartRelease $release, string $stagingFolder): void
    {
        if ($part->type->inPartsFolder()) {
            $this->addViewImage->handle($part, $release, $stagingFolder);
        }
        $part->part_release_id = $release->id;
        $part->clearMediaCollection('image');
        $this->generateHeader->updatePartHeader($part);
        $part->save();
    }

    protected function addHistoryLine(Part $part, User $actor, string $comment): void
    {
        $part->history()->create([
            'user_id' => $actor->id,
            'comment' => $comment,
        ]);
    }
}
