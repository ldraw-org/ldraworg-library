<?php

namespace App\Services\Part;

use App\Collections\PartCollection;
use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Models\User;
use App\Services\Parser\ParsedPartCollection;

class PartMover
{
    public function __construct(
        protected Finalizer    $finalizer,
        protected Writer       $writer,
    ) {}

    public function moveOfficialPart(Part $part, string $newName, User $actor): Part
    {
        if ($part->isUnofficial()) {
            throw new \Exception("Part {$part->id} is Unofficial");
        }

        if (!$part->type->inPartsFolder()) {
            throw new \Exception("Parts not in parts folder do not get moved but obsoleted");
        }

        $newPartName = $part->type->folder() . "/" . pathinfo($newName, PATHINFO_FILENAME) . $part->type->extension();

        if ($this->unofficialPartExists($newPartName)) {
            throw new \Exception("Part of name {$newPartName} already exists");
        }

        if ($this->unofficialPartExists($part->filename)) {
            throw new \Exception("A fix for Part {$part->id} already exists");
        }

        $upart = $this->copyOfficialToUnofficialPart($part, $newPartName);
        $upart->history()->create([
            'part_id' => $upart->id,
            'user_id' => $actor->id,
            'comment' => 'Moved from ' . $part->meta_name,
        ]);
        $this->updatePartReferences($upart, $part->meta_name);
        $mpart = $this->addMovedTo($part, $upart, $actor);
        $this->finalizer->handle(new PartCollection([$upart, $mpart]));
        $part->unofficial_part()->associate($mpart);
        $part->save();

        return $upart;
    }

    public function moveUnofficialPart(Part $part, ?PartType $newType = null, string $newName = ''): Part
    {
        if ($newType === null && $newName === '') {
            throw new \Exception("No part move defined");
        }

        if ($part->isOfficial()) {
            throw new \Exception("Part {$part->id} is Official");
        }

        $newPartType = $newType ?? $part->type;

        if ($part->type->isImageFormat() && $newPartType->isDatFormat() ||
            $part->type->isDatFormat() && $newPartType->isImageFormat()) {
            throw new \Exception("Parts cannot be moved to a different format folder");
        }

        $oldMetaName = $part->meta_name;

        if ($newName === '') {
            $newPartName = basename($part->filename);
        } else {
            $newPartName = pathinfo($newName, PATHINFO_FILENAME);
        }
        $newPartName = $newPartType->folder() . "/" . $newPartName . $newPartType->extension();

        if ($this->unofficialPartExists($newPartName)) {
            throw new \Exception("Part of name {$newPartName} already exists");
        }

        if ($part->isTexmap()) {
            $part->description = "{$newPartType->description()} {$newPartName}";
        }

        if (!$part->type->inPartsFolder() && $newPartType->inPartsFolder()) {
            $part->category = (new ParsedPartCollection($part->header))->category();
        } else {
            $part->category = null;
        }
        if ($part->type->folder() !== $newPartType->folder()) {
            $part->type = $newPartType;
        }

        $part->filename = $newPartName;
        $part->save();
        $this->updatePartReferences($part, $oldMetaName);
        $this->finalizer->handle(new PartCollection([$part]));

        return $part;
    }

    protected function unofficialPartExists(string $filename): bool
    {
        return Part::unofficial()->where('filename', $filename)->exists();
    }

    protected function updatePartReferences(Part $part, string $oldMetaName): void
    {
        $newMetaName = $part->meta_name;
        $oldBase = pathinfo($oldMetaName, PATHINFO_FILENAME);
        $quotedOldBase = preg_quote($oldBase, '/');
        $newBase = pathinfo($newMetaName, PATHINFO_FILENAME);
        $quotedOldMetaName = preg_quote($oldMetaName, '/');
        $part->parents()
            ->unofficial()
            ->with('body')
            ->each(function (Part $p) use ($newMetaName, $newBase, $quotedOldMetaName, $quotedOldBase) {
                if ($p->category === PartCategory::Moved) {
                    $newDescription = preg_replace(
                        "/(?<=~Moved to\s){$quotedOldBase}\b/",
                        $newBase,
                        $p->description
                    );
                    if ($newDescription !== null && $newDescription !== $p->description) {
                        $p->description = $newDescription;
                        $p->save();
                    }
                }
                $newBody = preg_replace(
                    "/(?<=\s){$quotedOldMetaName}(?=\s|$)/m",
                    $newMetaName,
                    $p->body->body
                );
                if ($newBody !== null && $newBody !== $p->body->body) {
                    $p->body->body = $newBody;
                    $p->body->save();
                }
            });
    }

    protected function copyOfficialToUnofficialPart(Part $part, ?string $newName = null): Part
    {
        $attributes = [
                'user_id' => $part->user_id,
                'filename' => $part->filename,
                'description' => $part->description,
                'cmdline' => $part->cmdline,
                'bfc' => $part->bfc,
                'type' => $part->type,
                'type_qualifier' => $part->type_qualifier,
                'license' => $part->license,
                'preview' => $part->preview,
                'category' => $part->category,
                'help' => $part->help,
            ];
        if ($newName !== null && $newName !== $part->filename) {
            $attributes['filename'] = $newName;
        }
        $body = $part->body->body;
        $keywords = $part->keywords->pluck('keyword')->values()->all();
        $history = $part->history->all();
        return $this->writer->createOrUpdate($attributes, $body, $keywords, $history);
    }

    protected function addMovedTo(Part $oldPart, Part $newPart, User $user): Part
    {
        $values = [
            'description' => "~Moved to " . pathinfo($newPart->filename, PATHINFO_FILENAME),
            'filename' => $oldPart->filename,
            'user_id' => $user->id,
            'type' => $oldPart->type,
            'type_qualifier' => $oldPart->type_qualifier,
            'license' => $user->license,
            'bfc' => $newPart->bfc,
            'category' => PartCategory::Moved,
            'header' => '',
            'preview' => $oldPart->preview,
        ];
        $bodyText = "1 16 0 0 0 1 0 0 0 1 0 0 0 1 {$newPart->meta_name}\n";
        return $this->writer->createOrUpdate($values, $bodyText);
    }
}
