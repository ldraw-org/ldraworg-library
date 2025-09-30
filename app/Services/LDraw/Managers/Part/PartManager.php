<?php

namespace App\Services\LDraw\Managers\Part;

use App\Enums\PartCategory;
use App\Enums\PartError;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Events\PartSubmitted;
use App\Jobs\UpdateRebrickable;
use App\Jobs\UpdateParentParts;
use App\Jobs\UpdateZip;
use App\Services\LDraw\Check\PartChecker;
use App\Services\LDraw\LDrawFile;
use App\Services\LDraw\Managers\StickerSheetManager;
use App\Services\LDraw\Parse\Parser;
use App\Services\LDraw\Render\LDView;
use App\Models\Part\Part;
use App\Models\Part\UnknownPartNumber;
use App\Models\RebrickablePart;
use App\Models\StickerSheet;
use App\Models\User;
use App\Settings\LibrarySettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Image;
use Spatie\Image\Enums\Fit;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class PartManager
{
    public function __construct(
        public Parser $parser,
        public LDView $render,
        protected LibrarySettings $settings
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

        return PartType::tryFrom(Arr::get($this->parser->getType($p?->contents ?? ''), 'type') . '_Texmap') ?? PartType::PartTexmap;
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
        $part = $this->parser->parse($file->contents);

        $user = User::fromAuthor($part->username, $part->realname)->first();
        $cat = $part->metaCategory ?? $part->descriptionCategory;
        $filename = $part->type->folder() . '/' . basename(str_replace('\\', '/', $part->name));
        $part->preview = $part->preview == '16 0 0 0 1 0 0 0 1 0 0 0 1' ? null : $part->preview;
        $values = [
            'description' => $part->description,
            'filename' => $filename,
            'user_id' => $user->id,
            'type' => $part->type,
            'type_qualifier' => $part->type_qualifier,
            'license' => $user->license,
            'bfc' => $part->bfc ?? null,
            'category' => $cat,
            'cmdline' => $part->cmdline,
            'preview' => $part->preview,
            'help' => $part->help,
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
        $upart->setKeywords($part->keywords ?? []);
        $upart->setHistory($part->history ?? []);
        $upart->setBody($part->body);
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
            $this->addStickerSheet($p);
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

        $base = $this->parser->basepart(basename($part->filename));
        if (is_null($base) || ("{$base}.dat" == $part->name() || "{$base}-f1.dat" == $part->name())) {
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

        $part->is_pattern = $this->parser->patternName(basename($part->filename));
        $part->is_composite = $this->parser->compositeName(basename($part->filename));
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
            'description' => "~Moved to " . str_replace(['.dat', '.png'], '', $newPart->name()),
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
        $upart->setBody("1 16 0 0 0 1 0 0 0 1 0 0 0 1 {$newPart->name()}\n");
        $oldPart->unofficial_part()->associate($upart);
        $oldPart->save();
        $upart->refresh();
        $this->finalizePart($upart);
        return $upart;
    }

    public function movePart(Part $part, string $newName, PartType $newType): bool
    {
        $oldname = $part->name();
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
            $dcat = $this->parser->getDescriptionCategory($part->header);
            $part->category = $dcat;
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
                $p->description = str_replace($oldname, $part->name(), $p->description);
                $p->save();
            }
            if ($part->isTexmap()) {
                $toldname = str_replace('textures\\', '', $oldname);
                $tnewname = str_replace('textures\\', '', $part->name());
                $p->body->body = str_replace($toldname, $tnewname, $p->body->body);
            } else {
                $p->body->body = str_replace($oldname, $part->name(), $p->body->body);
            }
            $p->body->save();
        }
        $this->updateMissing($part->name());
        $this->checkPart($part);
        $part->updateReadyForAdmin();
        $this->addUnknownNumber($part);
        $this->updateBasePart($part);
        UpdateParentParts::dispatch($part);
        return true;
    }

    public function loadSubparts(Part $part): void
    {
        $hadMissing = is_array($part->missing_parts) && count($part->missing_parts) > 0;
        $part->setSubparts($this->parser->getSubparts($part->body->body) ?? []);
        if ($hadMissing) {
            $part->refresh();
            $this->updateImage($part);
            $this->checkPart($part);
            $this->addStickerSheet($part);
            $part->updateReadyForAdmin();
        }
    }

    public function checkPart(Part $part, bool $checkFileErrors = true): void
    {
        $part->can_release = true;
        $pc = new PartChecker($part);
        $can_release = $pc->checkCanRelease($checkFileErrors);
        $part->part_check = $pc->getPartCheckBag();
        
        // Set Minifig warning but only for unofficial parts
        if ($part->isUnofficial() && $part->type->inPartsFolder() && $part->category == PartCategory::Minifig) {
            $part->part_check->add(PartError::WarningMinifigCategory);
        }
        
        // Set Sticker color warning.
        if ($part->type->inPartsFolder() && $part->category == PartCategory::StickerShortcut) {
            foreach (explode("\n", $part->body->body) as $line) {
                if (Str::startsWith($line, '1 ') && Str::doesntStartWith($line, '1 16')) {
                    $part->part_check->add(PartError::WarningStickerColor);
                    break;
                }
            }  
        }
        
        $part->can_release = $can_release;
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

    public function addStickerSheet(Part $p): void
    {
        $sticker = $p->category == PartCategory::Sticker ? $p : $p->children()->where('category', PartCategory::Sticker)->first();
        if (is_null($sticker) || in_array($p->category, [PartCategory::Moved, PartCategory::Obsolete])) {
            return;
        }
        if (!is_null($sticker->sticker_sheet)) {
            $p->parents()->whereIn('category', [PartCategory::Sticker, PartCategory::StickerShortcut])->update(['sticker_sheet_id' => $sticker->sticker_sheet->id]);
            $p->sticker_sheet()->associate($sticker->sticker_sheet);
        } else {
            $m = preg_match('/^[\d]+/', $sticker->name(), $s);
            if ($m) {
                $sheet = StickerSheet::firstOrCreate([
                    'number' => $s[0]
                ]);
                if (!$sheet->rebrickable_part) {
                    app(StickerSheetManager::class)->updateRebrickablePart($sheet);
                    $sheet->load('rebrickable_part');
                }
                $p->parents()->update(['sticker_sheet_id' => $sheet->id]);
                $p->sticker_sheet()->associate($sheet);
            } else {
                $p->sticker_sheet_id = null;
            }
        }
        if (!is_null($p->sticker_sheet_id) && !in_array($p->category, [PartCategory::Sticker, PartCategory::Moved, PartCategory::Obsolete])) {
            $p->category = PartCategory::StickerShortcut;
            $p->generateHeader();
        }
        $p->save();
        $p->refresh();
    }

    public function updateRebrickable(Part $part, bool $updateOfficial = false): void
    {
        if ($part->canSetRebrickablePart()) {
            RebrickablePart::findOrCreateFromPart($part);
        }
        if (is_null($part->sticker_sheet_id) && $part->type_qualifier == PartTypeQualifier::Alias && is_null($part->getRebrickablePart())) {
            $part->rebrickable_part()->associate($part->subparts->first()->rebrickable_part);
            $part->save();
        }
        $part->load('rebrickable_part', 'sticker_rebrickable_part');
        $part->setExternalSiteKeywords($updateOfficial);
    }

}
