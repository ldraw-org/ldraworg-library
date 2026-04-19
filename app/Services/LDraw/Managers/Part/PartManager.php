<?php

namespace App\Services\LDraw\Managers\Part;

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Enums\PreviewRotation;
use App\Events\PartSubmitted;
use App\Jobs\UpdateRebrickable;
use App\Jobs\UpdateParentParts;
use App\Jobs\UpdateZip;
use App\Services\LDraw\LDrawFile;
use App\Services\LDraw\Managers\StickerSheetManager;
use App\Services\LDraw\Render\LDView;
use App\Models\Part\Part;
use App\Models\Part\UnknownPartNumber;
use App\Models\User;
use App\Services\Check\CheckMessageCollection;
use App\Services\Check\PartChecker;
use App\Services\LDraw\Managers\RebrickablePartManager;
use App\Services\LDraw\Rebrickable;
use App\Services\Parser\ParsedPartCollection;
use App\Services\Part\ImageGenerator;
use App\Services\Part\SubpartSync;
use App\Services\Part\Validator;
use App\Settings\LibrarySettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class PartManager
{
    public function __construct(
        public LDView $render,
        protected LibrarySettings $settings,
        protected Rebrickable $rebrickable,
        protected StickerSheetManager $stickerManager,
        protected RebrickablePartManager $rebrickablePartManager,
        protected SubpartSync $subpartSync,
        protected ImageGenerator $imageGenerator,
        protected Validator $validator,
    ) {
    }

    public function submit(LDrawFile|SupportCollection|array $files, User $user, ?string $comments = null): Collection
    {
        if (!$files instanceof SupportCollection) {
            $files = is_array($files) ? $files : [$files];
            $files = collect($files);
        }
        // Parse each part into the tracker
        $parts = new Collection($files->map(function (LDrawFile $file, int $key) use ($files, $user) {
            if ($file->mimetype == 'image/png') {
                return $this->makePartFromImage($file, $user, $this->guessPartType($file->filename, $files));
            } elseif ($file->mimetype == 'text/plain') {
                return $this->makePartFromText($file);
            }
            return null;
        })->all());
        $this->finalizePart($parts);
        $parts->each(function (Part $part) use ($user, $comments) {
            $user->notification_parts()->syncWithoutDetaching([$part->id]);
            UpdateZip::dispatch($part);
            PartSubmitted::dispatch($part, $user, $comments);
        });

        return $parts;
    }

    public function guessPartType(string $filename, SupportCollection $files): PartType
    {
        // Check if part exists and return that type
        $p = Part::firstWhere('filename', 'LIKE', "%/textures%/{$filename}");
        if (!is_null($p)) {
            return $p->type;
        }
        // See if it is used by the group of submitted files
        $p = $files->first(
            fn (LDrawFile $f, int $key) =>
                $f->mimetype == 'text/plain' && Str::containsAll($f->contents, ['!TEXMAP', $filename])
        );
        if (!is_null($p)) {
            $p = (new ParsedPartCollection($p->content));
            $type = $p->type();
            return is_null($type) ? PartType::PartTexmap : PartType::tryfrom($type->value . '_Texmap');
        }

        return PartType::PartTexmap;
    }

    protected function makePartFromImage(LDrawFile $file, User $user, PartType $type): Part
    {
        $filename = $type->folder() . '/' . basename(str_replace('\\', '/', $file->filename));
        $attributes = [
            'user_id' => $user->id,
            'license' => $user->license,
            'filename' => $filename,
            'description' => "{$type->description()} {$filename}",
            'type' => $type,
            'header' => '',
        ];
        $upart = $this->makePart($attributes);
        $upart->setBody(base64_encode($file->contents));
        return $upart;
    }

    protected function makePartFromText(LDrawFile $file): Part
    {
        $part = new ParsedPartCollection($file->contents);

        $user = $part->authorUser();
        $filename = $part->type()->folder() . '/' . basename(str_replace('\\', '/', $part->name()));
        $values = [
            'description' => $part->description(),
            'filename' => $filename,
            'user_id' => $user->id,
            'type' => $part->type(),
            'type_qualifier' => $part->type_qualifier(),
            'license' => $user->license,
            'bfc' => $part->headerBfc(),
            'category' => $part->category(),
            'cmdline' => $part->cmdline(),
            'preview' => $part->previewRotation() ?? PreviewRotation::Default,
            'help' => $part->help(),
            'header' => ''
        ];
        $upart = $this->makePart($values);
        $upart->setKeywords($part->keywords() ?? []);
        $upart->setHistory($part->history() ?? []);
        $upart->setBody($part->bodyText());
        $upart->refresh();
        return $upart;
    }


    protected function makePart(array $values): Part
    {
        $upart = Part::unofficial()->firstWhere('filename', $values['filename']);
        $opart = Part::official()->firstWhere('filename', $values['filename']);
        if (!is_null($upart)) {
            store_backup(str_replace('/', '-', $upart->filename), $upart->get());
            $upart->votes()->delete();
            $upart->fill($values);
            $upart->save();
        } elseif (!is_null($opart)) {
            $upart = Part::create($values);
            $opart->unofficial_part()->associate($upart);
            $opart->save();
        } else {
            $upart = Part::create($values);
        }
        return $upart;
    }

    public function copyOfficialToUnofficialPart(Part $part): Part
    {
        $values = [
            'description' => $part->description,
            'filename' => $part->filename,
            'user_id' => $part->user_id,
            'type' => $part->type,
            'type_qualifier' => $part->type_qualifier,
            'license' => $part->license,
            'bfc' => $part->bfc,
            'category' => $part->category,
            'cmdline' => $part->cmdline,
            'help' => $part->help,
            'header' => $part->header,
            'preview' => $part->preview,
        ];
        $upart = Part::create($values);
        $upart->setKeywords($part->keywords);
        $upart->setHistory($part->history);
        $upart->setBody($part->body);
        $upart->save();
        $upart->refresh();
        $this->finalizePart($upart);
        return $upart;
    }

    public function finalizePart(Part|Collection $parts): void
    {
        if ($parts instanceof Part) {
            $parts = (new Collection())->add($parts);
        }
        $parts->loadMissing('keywords', 'history', 'body', 'user');
        $parts->each(function (Part $p) {
            $this->subpartSync->loadSubparts($p);
            $p->generateHeader();
        });
        $parts->load('official_part');
        $parts->each(function (Part $p) {
            $p->updatePartStatus();
            if (!is_null($p->official_part)) {
                $this->updateUnofficialWithOfficialFix($p->official_part);
            };
            $this->updateBasePart($p);
            $this->imageGenerator->regenerateImage($p);
            $this->validator->checkPart($p);
            $p->updateReadyForAdmin();
            $this->addUnknownNumber($p);
            UpdateParentParts::dispatch($p->id);
            UpdateRebrickable::dispatch($p->id);
        });
    }

    protected function updateMissing(string $name): void
    {
        Part::unofficial()
            ->whereJsonContains('missing_parts', $name)
            ->each(function (Part $p) {
                $this->subpartSync->loadSubparts($p);
            });
    }

    protected function updateUnofficialWithOfficialFix(Part $officialPart): void
    {
        Part::unofficial()->whereHas('subparts', function (Builder $query) use ($officialPart) {
            return $query->where('id', $officialPart->id);
        })->each(function (Part $p) {
            $this->subpartSync->loadSubparts($p);
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

    public function addMovedTo(Part $oldPart, Part $newPart): ?Part
    {
        if (
            $oldPart->isUnofficial() ||
            $newPart->isOfficial() ||
            !is_null($oldPart->unofficial_part) ||
            !$oldPart->type->inPartsFolder()
        ) {
            return null;
        }

        $values = [
            'description' => "~Moved to " . str_replace(['.dat', '.png'], '', $newPart->meta_name),
            'filename' => $oldPart->filename,
            'user_id' => Auth::user()->id,
            'type' => $oldPart->type,
            'type_qualifier' => $oldPart->type_qualifier,
            'license' => Auth::user()->license,
            'bfc' => $newPart->bfc,
            'category' => PartCategory::Moved,
            'header' => '',
        ];
        $upart = Part::create($values);
        $upart->setBody("1 16 0 0 0 1 0 0 0 1 0 0 0 1 {$newPart->meta_name}\n");
        $oldPart->unofficial_part()->associate($upart);
        $oldPart->save();
        $upart->refresh();
        $this->finalizePart($upart);
        return $upart;
    }

    public function movePart(Part $part, string $newName, PartType $newType): bool
    {
        $oldname = $part->meta_name;
        if ($newName == '.dat' || $newName == '.png') {
            $newName = basename($part->filename);
        }
        if ($part->isTexmap()) {
            $part->description = "{$newType->description()} {$newName}";
        }
        $newName = "{$newType->folder()}/{$newName}";
        $upart = Part::unofficial()->where('filename', $newName)->first();
        if ($part->isOfficial() || !is_null($upart)) {
            return false;
        }
        if (!$part->type->inPartsFolder() && $newType->inPartsFolder()) {
            $part->category = (new ParsedPartCollection($part->header))->category();
        }
        if ($part->type->folder() !== $newType->folder()) {
            $part->type = $newType;
        }
        $part->filename = $newName;
        $part->save();
        $part->generateHeader();
        $this->updateBasePart($part);
        $this->imageGenerator->regenerateImage($part);
        foreach ($part->parents()->unofficial()->get() as $p) {
            if ($part->type->inPartsFolder() && $p->category === PartCategory::Moved) {
                $p->description = str_replace($oldname, $part->meta_name, $p->description);
                $p->save();
            }
            if ($part->isTexmap()) {
                $toldname = str_replace('textures\\', '', $oldname);
                $tnewname = str_replace('textures\\', '', $part->meta_name);
                $p->body->body = str_replace($toldname, $tnewname, $p->body->body);
            } else {
                $p->body->body = str_replace($oldname, $part->meta_name, $p->body->body);
            }
            $p->body->save();
        }
        $this->updateMissing($part->meta_name);
        $this->validator->checkPart($part);
        $part->updateReadyForAdmin();
        $this->addUnknownNumber($part);
        $this->updateBasePart($part);
        UpdateParentParts::dispatch($part->id);
        UpdateRebrickable::dispatch($part->id);
        return true;
    }

    protected function addUnknownNumber(Part $p): void
    {
        $result = preg_match('/parts\/u([0-9]{4}).*\.dat/', $p->filename, $matches);
        if ($result) {
            $number = $matches[1];
            $unk = UnknownPartNumber::firstOrCreate(
                ['number' => $number],
                ['user_id' => $p->user->id]
            );
            $p->unknown_part_number()->associate($unk);
        } else {
            $p->unknown_part_number_id = null;
        }
        $p->save();
    }

}
