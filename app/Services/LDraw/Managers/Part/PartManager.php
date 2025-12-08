<?php

namespace App\Services\LDraw\Managers\Part;

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Events\PartSubmitted;
use App\Jobs\UpdateRebrickable;
use App\Jobs\UpdateParentParts;
use App\Jobs\UpdateZip;
use App\Services\LDraw\LDrawFile;
use App\Services\LDraw\Managers\StickerSheetManager;
use App\Services\LDraw\Render\LDView;
use App\Models\Part\Part;
use App\Models\Part\UnknownPartNumber;
use App\Models\RebrickablePart;
use App\Models\User;
use App\Services\Check\CheckMessageCollection;
use App\Services\Check\PartChecker;
use App\Services\LDraw\Managers\RebrickablePartManager;
use App\Services\LDraw\Rebrickable;
use App\Services\Parser\ParsedPartCollection;
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
        protected PartChecker $checker,
        protected Rebrickable $rebrickable,
        protected StickerSheetManager $stickerManager,
        protected RebrickablePartManager $rebrickablePartManager,
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
        $preview = $part->preview() == '16 0 0 0 1 0 0 0 1 0 0 0 1' ? null : $part->preview();
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
            'preview' => $preview,
            'help' => $part->help(),
            'header' => ''
        ];
        $upart = $this->makePart($values);
        $preview_vals = $upart->previewValues();
        if ($preview_vals['color'] != 16 ||
            $preview_vals['x'] != 0 ||
            $preview_vals['y'] != 0 ||
            $preview_vals['z'] != 0
        ) {
            $upart->preview = '16 0 0 0 ' . $preview_vals['rotation'];
            $upart->preview = $upart->preview == '16 0 0 0 1 0 0 0 1 0 0 0 1' ? null : $upart->preview;
            $upart->save();
        }
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
            $this->loadSubparts($p);
            $p->generateHeader();
        });
        $parts->load('official_part');
        $parts->each(function (Part $p) {
            $p->updatePartStatus();
            if (!is_null($p->official_part)) {
                $this->updateUnofficialWithOfficialFix($p->official_part);
            };
            $this->updateBasePart($p);
            $this->updateImage($p);
            $this->checkPart($p);
            $p->updateReadyForAdmin();
            $this->addUnknownNumber($p);
            UpdateParentParts::dispatch($p);
            UpdateRebrickable::dispatch($p);
        });
    }

    protected function imageOptimize(string $path, string $newPath = ''): void
    {
        $optimizerChain = (new OptimizerChain())->addOptimizer(new Optipng([]));
        if ($newPath !== '') {
            $optimizerChain->optimize($path, $newPath);
        } else {
            $optimizerChain->optimize($path);
        }
    }

    public function updateImage(Part $part): void
    {
        if ($part->isTexmap()) {
            $image = imagecreatefromstring($part->get());
        } else {
            $image = $this->render->render($part);
        }
        $dir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $imagePath = $dir->path(substr($part->filename, 0, -4) . '.png');
        imagepng($image, $imagePath);
        $this->imageOptimize($imagePath);
        $part->clearMediaCollection('image');
        $part->addMedia($imagePath)
            ->toMediaCollection('image');
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
        $this->updateImage($part);
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
        $this->checkPart($part);
        $part->updateReadyForAdmin();
        $this->addUnknownNumber($part);
        $this->updateBasePart($part);
        UpdateParentParts::dispatch($part);
        UpdateRebrickable::dispatch($part);
        return true;
    }

    public function loadSubparts(Part $part): void
    {
        $hadMissing = is_array($part->missing_parts) && count($part->missing_parts) > 0;
        $part->setSubparts((new ParsedPartCollection($part->body->body))->subpartFilenames() ?? []);
        if ($hadMissing) {
            $part->refresh();
            $this->updateImage($part);
            $this->checkPart($part);
            UpdateRebrickable::dispatch($part);
            $part->updateReadyForAdmin();
        }
    }

    public function checkPart(Part $part, ?string $filename = null): void
    {
        if ($part->isText()) {
            $part->check_messages = $this->checker->run($part);
        } else {
            $part->check_messages = new CheckMessageCollection();
        }
        $part->can_release = $part->isOfficial() || ($part->check_messages->doesntHaveHoldableIssues());
        $part->updateReadyForAdmin();
        $part->save();
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

    public function updateRebrickable(Part $part, bool $updateOfficial = false): void
    {
        if ($part->canSetRebrickablePart()) {
            if ($part->category == PartCategory::Sticker || $part->category == PartCategory::StickerShortcut) {
                $rbPart = $this->stickerManager->getStickerPart($part);
                $part->rebrickable_part()->associate($rbPart);
                $part->setExternalSiteKeywords($updateOfficial);
                $part->save();
                return;
            }
            $this->rebrickablePartManager->findOrCreateFromPart($part, $this->rebrickable);
            $part->setExternalSiteKeywords($updateOfficial);
        }
    }

}
