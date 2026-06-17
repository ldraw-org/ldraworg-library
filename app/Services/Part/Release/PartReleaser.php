<?php

namespace App\Services\Part\Release;

use App\Events\PartReleased;
use App\Models\Part\Part;
use App\Models\Part\PartEvent;
use App\Models\Part\PartHistory;
use App\Models\Part\PartRelease;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class PartReleaser
{
    public function __construct(
        protected AddViewImage $addViewImage,
    )
    {}

    public function releaseAllMarkedUnofficialParts(PartRelease $release, int $actorId, string $stagingPath): void
    {
        Part::where('marked_for_release', true)
            ->lazy()
            ->each(fn (Part $part) => $this->releaseUnofficialPart($part, $release, $actorId, $stagingPath));
    }

    public function releaseMinorEdits(PartRelease $release, int $actorId): void
    {
        Part::official()
            ->where('has_minor_edit', true)
            ->whereDoesntHave('unofficial_part')
            ->each(fn (Part $part) => $this->updateOfficialMinorEdit($part, $release, $actorId));
    }

    protected function updateOfficialMinorEdit(Part $part, PartRelease $release, int $actorId): void
    {
        PartHistory::create([
            'user_id' => $actorId,
            'part_id' => $part->id,
            'comment' => "Minor header edits"
        ]);
        PartHistory::create([
            'user_id' => $actorId,
            'part_id' => $part->id,
            'comment' => "Official Update {$release->name}"
        ]);
        $part->part_release_id = $release->id;
        $part->has_minor_edit = false;
        $part->save();
    }

    protected function releaseUnofficialPart(Part $part, PartRelease $release, int $actorId, string $stagingPath): void
    {
        // Only unofficial parts can be released
        if ($part->isOfficial()) {
            return;
        }

        if ($part->isFix()) {
            $this->releaseUnofficialFix($part, $release, $actorId);
        } else {
            $this->releaseUnofficialPart($part, $release, $actorId, $stagingPath);
        }

        PartReleased::dispatch($part->id, $actorId, $release->id, $release->name);

    }

    protected function releaseUnofficialFix(Part $part, PartRelease $release, int $actorId): void
    {
        $officialPart = $part->official_part;

        $values = [
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
        ];
        $officialPart->fill($values);
        $officialPart->setKeywords($part->keywords->pluck('keyword')->values()->all());
        $officialPart->setHistory($part->history);
        $officialPart->setBody($part->body);
        $this->addReleaseHistoryLine($officialPart, $release, $actorId);

        PartEvent::where('part_id', $part->id)
            ->update([
                'part_id' => $officialPart->id,
                'part_release_id' => $release->id,
            ]);
        $officialPart->save();

        $part->deleteQuietly();

    }

    protected function releaseNewPart(Part $part, PartRelease $release, int $actorId, string $stagingPath): void
    {
        $part->part_release_id = $release->id;
        if ($part->type->inPartsFolder()) {
            $this->addViewImage->handle($part, $release, $stagingPath);
        }
        PartEvent::where('part_id', $part->id)
            ->update([
                'part_release_id' => $release->id,
            ]);
        $part->clearMediaCollection('image');
        $this->addReleaseHistoryLine($part, $release, $actorId);
        $part->save();
    }
    protected function addReleaseHistoryLine(Part $part, PartRelease $release, int $actorId): void
    {
        PartHistory::create([
            'user_id' => $actorId,
            'part_id' => $part->id,
            'comment' => "Official Update {$release->name}"
        ]);
    }
}
