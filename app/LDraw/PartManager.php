<?php

namespace App\LDraw;

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Jobs\UpdateRebrickable;
use App\Jobs\UpdateParentParts;
use App\LDraw\Parse\Parser;
use App\LDraw\Render\LDView;
use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
use App\Models\Part\UnknownPartNumber;
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

class PartManager
{
    public function __construct(
        public Parser $parser,
        public LDView $render,
        protected LibrarySettings $settings
    ) {
    }

    public function submit(LDrawFile|SupportCollection|array $files, User $user): Collection
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
        $upart->setBody(Str::toBase64($file->contents));
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
            'header' => ''
        ];
        $upart = $this->makePart($values);
        $upart->setKeywords($part->keywords ?? []);
        $upart->setHelp($part->help ?? []);
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
            'header' => $part->header,
        ];
        $upart = Part::create($values);
        $upart->setKeywords($part->keywords);
        $upart->setHelp($part->help);
        $upart->setHistory($part->history);
        $upart->setBody($part->body);
        $upart->save();
        $upart->refresh();
        $this->finalizePart($upart);
        return $upart;
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

    public function finalizePart(Part|Collection $parts): void
    {
        if ($parts instanceof Part) {
            $parts = (new Collection())->add($parts);
        }
        $parts->loadMissing('keywords', 'help', 'history', 'body', 'user');
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

    public function updateImage(Part $part): void
    {
        if ($part->isTexmap()) {
            $image = imagecreatefromstring($part->get());
        } else {
            $image = $this->render->render($part);
        }
        $lib = $part->isUnofficial() ? 'unofficial' : 'official';
        $imageFilename = substr($part->filename, 0, -4) . '.png';
        $imagePath = Storage::disk('images')->path("library/{$lib}/{$imageFilename}");
        $imageThumbPath = substr($imagePath, 0, -4) . '_thumb.png';
        imagepng($image, $imagePath);
        $this->imageOptimize($imagePath);
        Image::load($imagePath)->fit(Fit::Contain, $this->settings->max_thumb_width, $this->settings->max_thumb_height)->save($imageThumbPath);
        $this->imageOptimize($imageThumbPath);
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
            !$newPart->isUnofficial() ||
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
        if (!$part->isUnofficial() || !is_null($upart)) {
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

    public function checkPart(Part $part): void
    {
        $messages = $part->part_check_messages ?? [];
        $pc = app(\App\LDraw\Check\PartChecker::class);
        $can_release = $pc->checkCanRelease($part);
        $messages['errors'] = $pc->getErrorStorageArray();

        if (!$part->isUnofficial()) {
            $part->can_release = true;
            $messages['warnings'] = [];
        } else {
            $messages['warnings'] = [];
            if (!is_null($part->category) && $part->category == PartCategory::Minifig) {
                $messages['warnings'] = "Check Minifig category: {$part->category->value}";
            }
            $part->can_release = $can_release;
        }
        $part->part_check_messages = $messages;
        $part->updateReadyForAdmin();
        $part->save();
    }

    protected function addUnknownNumber(Part $p)
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

    public function addStickerSheet(Part $p)
    {
        $sticker = $p->descendantsAndSelf()->where('category', PartCategory::Sticker)->partsFolderOnly()->first();
        if (is_null($sticker)) {
            return;
        }
        if (!is_null($sticker->sticker_sheet)) {
            $p->ancestorsAndSelf()->update(['sticker_sheet_id' => $sticker->sticker_sheet->id]);
        } else {
            $m = preg_match('#^([0-9]+)[a-z]+(?:c[0-9]{2})?\.dat$#iu', $sticker->name(), $s);
            if ($m === 1) {
                $sheet = StickerSheet::firstWhere('number', $s[1]);
                if (is_null($sheet)) {
                    $sheet = StickerSheet::create([
                        'number' => $s[1],
                        'part_colors' => [],
                        'rebrickable' => null,
                    ]);
                    $sheet->rebrickable = app(\App\LDraw\StickerSheetManager::class)->getRebrickableData($sheet);
                    $sheet->save();
                }
                $p->ancestorsAndSelf()->update(['sticker_sheet_id' => $sheet->id]);
            } else {
                $p->sticker_sheet_id = null;
            }
        }
        if (!is_null($p->sticker_sheet_id) && $p->category != PartCategory::Sticker) {
            $p->category = PartCategory::StickerShortcut;
            $p->generateHeader();
        }
        $p->save();
        $p->refresh();
    }

    public function updateRebrickable(Part $part): void
    {
        if ($part->canSetExternalData()) {
            $rb = new Rebrickable();
            $part_rb = $part->rebrickable;
            $part_rb['data'] = [];
            $part_num = basename($part->filename, '.dat');

            $rb_data = $rb->getParts(['ldraw_id' => $part_num]);
            $part_rb['data'] = $rb_data->all();
            if (!$rb_data->isEmpty() && $part->isUnofficial()) {
                $rb_part = Arr::first($rb_data);
                $okws = $part->keywords
                    ->filter(fn (PartKeyword $key) =>
                        Str::of($key->keyword)->lower()->startsWith('rebrickable') ||
                        Str::of($key->keyword)->lower()->startsWith('bricklink') ||
                        Str::of($key->keyword)->lower()->startsWith('brickset') ||
                        Str::of($key->keyword)->lower()->startsWith('brickowl')
                    )
                    ->pluck('id');
                if (!$okws->isEmpty()) {
                    $part->keywords()->detach($okws->all());
                    $part->load('keywords');
                }
                $kws = $part->keywords->pluck('keyword')->all();

                $rb_num = Arr::get($rb_part, 'part_num');
                if ($rb_num != $part_num) {
                    $kws[] = "Rebrickable {$rb_num}";
                }
                $bl_num = Arr::get($rb_part, 'external_ids.BrickLink.0');
                if (!is_null($bl_num) && $bl_num != $part_num) {
                    $kws[] = "BrickLink {$bl_num}";
                }
                $part->setKeywords($kws);
                $part->load('keywords');
                $part->generateHeader();
            }
            $part_rb['updated_at'] = time();
            $part->rebrickable = $part_rb;
            $part->save();
        }
    }
}
