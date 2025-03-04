<?php

namespace App\LDraw;

use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Jobs\UpdateParentParts;
use App\LDraw\Parse\Parser;
use App\LDraw\Render\LDView;
use App\Models\Part\Part;
use App\Models\Part\PartCategory;
use App\Models\Part\UnknownPartNumber;
use App\Models\StickerSheet;
use App\Models\User;
use App\Settings\LibrarySettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
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

    public function submit(LDrawFile|array $files, User $user): Collection
    {
        if ($files instanceof LDrawFile) {
            $files = [$files];
        }
        // Parse each part into the tracker
        $parts = new Collection(Arr::map($files, function (LDrawFile $file, int $key) use ($files, $user) {
            if ($file->mimetype == 'image/png') {
                return $this->makePartFromImage($file->filename, $file->contents, $user, $this->guessPartType($file->filename, $files));
            } elseif ($file->mimetype == 'text/plain') {
                return $this->makePartFromText($file->contents);
            }
            return null;
        }));
        $parts->load('category', 'user', 'history', 'help');
        $parts->each(function (Part $p) {
            $p->generateHeader();
            $this->updateMissing($p->name());
            $this->loadSubparts($p);
        });
        $parts->load('descendantsAndSelf', 'descendants', 'ancestors', 'official_part');
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
        });
        return $parts;
    }

    protected function guessPartType(string $filename, array $partfiles): PartType
    {
        $p = Part::firstWhere('filename', 'LIKE', "%{$filename}");
        //Texmap exists, use that type
        if (!is_null($p)) {
            return $p->type;
        }
        // Texmap is used in one of the submitted files, use the type appropriate for that part
        foreach ($partfiles as $file) {
            if ($file->mimetype == 'text/plain' && $filename !== $file->filename && stripos($filename, $file->contents) !== false) {
                $type = $this->parser->getType($file->contents);
                $pt = PartType::from(Arr::get($type, 'type'));
                $textype = PartType::tryFrom("{$pt->value}_Texmap");
                if (!is_null($textype)) {
                    return $textype;
                }
            }
        }
        return PartType::PartTexmap;
    }

    protected function makePartFromImage(string $filename, string $contents, User $user, PartType $type): Part
    {
        $attributes = [
            'user_id' => $user->id,
            'license' => $user->license,
            'filename' => "{$type->folder()}/{$filename}",
            'description' => "{$type->description()} {$filename}",
            'type' => $type,
            'header' => '',
        ];
        $upart = $this->makePart($attributes);
        $upart->setBody(base64_encode($contents));
        $upart->refresh();
        return $upart;
    }

    protected function makePartFromText(string $text): Part
    {
        $part = $this->parser->parse($text);

        $user = User::fromAuthor($part->username, $part->realname)->first();
        $cat = PartCategory::firstWhere('category', $part->metaCategory ?? $part->descriptionCategory);
        $filename = $part->type->folder() . '/' . basename(str_replace('\\', '/', $part->name));
        if ($part->preview == '16 0 0 0 1 0 0 0 1 0 0 0 1') {
            $part->preview = null;
        }
        $values = [
            'description' => $part->description,
            'filename' => $filename,
            'user_id' => $user->id,
            'type' => $part->type,
            'type_qualifier' => $part->type_qualifier,
            'license' => $user->license,
            'bfc' => $part->bfc ?? null,
            'part_category_id' => $cat->id ?? null,
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
            'part_category_id' => $part->part_category_id,
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

    public function finalizePart(Part $part): void
    {
        $part->updatePartStatus();
        $part->generateHeader();
        $this->updateMissing($part->name());
        $this->loadSubparts($part);
        if (!is_null($part->official_part)) {
            $this->updateUnofficialWithOfficialFix($part->official_part);
        };
        $this->updateBasePart($part);
        $this->updateImage($part);
        $this->checkPart($part);
        $this->addStickerSheet($part);
        $part->updateReadyForAdmin();
        $this->addUnknownNumber($part);
        UpdateParentParts::dispatch($part);
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

    public function updateBasePart(Part $part, bool $force = false): void
    {
        if (!$force && ($part->is_pattern || $part->is_composite || $part->is_dual_mould || !is_null($part->base_part))) {
            return;
        }

        $result = preg_match(config('ldraw.patterns.base'), basename($part->filename), $matches);
        if (!$result) {
            return;
        }

        $bp = null;
        for ($i = 7, $j = 2; $i >= 5; $i--, $j++) {
            if ($matches[$i] != '') {
                $bp = Part::doesntHave('official_part')
                    ->where(
                        fn ($q) => $q
                            ->orWhere('filename', "parts/{$matches[$j]}.dat")
                            ->orWhere('filename', "parts/{$matches[$j]}-f1.dat")
                    )->first();
                if (!is_null($bp)) {
                    break;
                }
            }
        }
        if (!is_null($bp) && $matches[5] != '') {
            $part->base_part()->associate($bp);
        }
        if ($matches[5] != '') {
            $part->is_pattern = mb_substr($matches[5], 0, 1) == 'p' ||
                mb_substr($matches[6], 0, 1) == 'p' ||
                mb_substr($matches[7], 0, 1) == 'p';
            $part->is_composite = mb_substr($matches[5], 0, 1) == 'c' ||
                mb_substr($matches[6], 0, 1) == 'c' ||
                mb_substr($matches[7], 0, 1) == 'c';
        }
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
            'part_category_id' => PartCategory::firstWhere('category', 'Moved')->id,
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
        if ($newName == '.dat') {
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
            $dcat = PartCategory::firstWhere('category', $this->parser->getDescriptionCategory($part->header));
            $part->category()->associate($dcat);
        }
        if ($part->type->folder() !== $newType->folder()) {
            $part->type = $newType;
        }
        $part->filename = $newName;
        $part->save();
        $part->generateHeader();
        $this->updateBasePart($part, true);
        $this->updateImage($part);
        foreach ($part->parents()->unofficial()->get() as $p) {
            if ($part->type->inPartsFolder() && $p->category->category === "Moved") {
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
        $check = app(\App\LDraw\Check\PartChecker::class)->checkCanRelease($part);
        $messages['errors'] = $check['errors'];

        if (!$part->isUnofficial()) {
            $part->can_release = true;
            $messages['warnings'] = [];
        } else {
            $messages['warnings'] = [];
            if (isset($part->category) && $part->category->category == "Minifig") {
                $messages['warnings'] = "Check Minifig category: {$part->category->category}";
            }
            $part->can_release = $check['can_release'];
        }
        $part->part_check_messages = $messages;
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
        $p->refresh();
        $sticker = $p->descendantsAndSelf->where('category.category', 'Sticker')->partsFolderOnly()->first();
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
        if (!is_null($p->sticker_sheet_id) && $p->category->category != 'Sticker') {
            $p->category()->associate(PartCategory::firstWhere('category', 'Sticker Shortcut'));
            $p->generateHeader();
        }
        $p->save();
        $p->refresh();
    }
}
