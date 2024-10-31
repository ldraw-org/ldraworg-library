<?php

namespace App\LDraw;

use App\Jobs\UpdateParentParts;
use App\LDraw\Parse\Parser;
use App\LDraw\Render\LDView;
use App\Models\Part;
use App\Models\PartCategory;
use App\Models\PartType;
use App\Models\PartTypeQualifier;
use App\Models\Rebrickable\RebrickablePart;
use App\Models\StickerSheet;
use App\Models\User;
use App\Settings\LibrarySettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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

    public function submit(array $files, User $user): Collection
    {
        $parts = new Collection();
        // Parse each part into the tracker
        foreach ($files as $file) {
            if ($file['type'] == 'image') {
                $parts->add($this->makePartFromImage($file['filename'], $file['contents'], $user, $this->guessPartType($file['filename'], $files)));
            } elseif ($file['type'] == 'text') {
                $parts->add($this->makePartFromText($file['contents']));
            }
        }

        $parts->each(function (Part $p) {
            $this->finalizePart($p);
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
            if ($file['type'] == 'text' && $filename !== $file['filename'] && stripos($filename, $file['contents']) !== false) {
                $type = $this->parser->parse($file['contents'])->type;
                $pt = PartType::firstWhere('type', $type);
                $textype = PartType::firstWhere('type', "{$pt->type}_Texmap");
                if (!is_null($textype)) {
                    return $textype;
                }
            }
        }
        return PartType::firstWhere('type', 'Part_Texmap');
    }

    protected function makePartFromImage(string $filename, string $contents, User $user, PartType $type): Part
    {
        $attributes = [
            'user_id' => $user->id,
            'part_license_id' => $user->license->id,
            'filename' => $type->folder . $filename,
            'description' => "{$type->name} {$filename}",
            'part_type_id' => $type->id,
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
        $type = PartType::firstWhere('type', $part->type);
        $qual = PartTypeQualifier::firstWhere('type', $part->qual);
        $cat = PartCategory::firstWhere('category', $part->metaCategory ?? $part->descriptionCategory);
        $filename = $type->folder . basename(str_replace('\\', '/', $part->name));
        $values = [
            'description' => $part->description,
            'filename' => $filename,
            'user_id' => $user->id,
            'part_type_id' => $type->id,
            'part_type_qualifier_id' => $qual->id ?? null,
            'part_license_id' => $user->license->id,
            'bfc' => $part->bfcwinding ?? null,
            'part_category_id' => $cat->id ?? null,
            'cmdline' => $part->cmdline,
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
            'part_type_id' => $part->part_type_id,
            'part_type_qualifier_id' => $part->part_type_qualifier_id,
            'part_license_id' => $part->part_license_id,
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
        $part->updateVoteSort();
        $part->generateHeader();
        $this->updateMissing($part->name());
        $this->loadSubpartsFromBody($part);
        if (!is_null($part->official_part)) {
            $this->updateUnofficialWithOfficialFix($part->official_part);
        };
        $this->updateBasePart($part);
        $this->updateImage($part);
        $this->checkPart($part);
        $this->addStickerSheet($part);
        $part->updateReadyForAdmin();
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

    protected function updateMissing(string $filename): void
    {
        Part::unofficial()->whereJsonContains('missing_parts', $filename)->each(function (Part $p) {
            $this->loadSubpartsFromBody($p);
        });
    }

    protected function updateUnofficialWithOfficialFix(Part $officialPart): void
    {
        Part::unofficial()->whereHas('subparts', function (Builder $query) use ($officialPart) {
            return $query->where('id', $officialPart->id);
        })->each(function (Part $p) {
            $this->loadSubpartsFromBody($p);
        });
    }

    function updateBasePart(Part $part): void {
        $pattern = '#^(((([0-9a-z]+?)((?:p[0-9a-z]{2,3}|p\d{4}|d[0-9a-z]{2}|d\d{4}|c[0-9a-z]{2}|c\d{4})?))((?:p[0-9a-z]{2,3}|p\d{4}|d[0-9a-z]{2}|d\d{4}|c[0-9a-z]{2}|c\d{4})?))((?:p[0-9a-z]{2,3}|p\d{4}|d[0-9a-z]{2}|d\d{4}|c[0-9a-z]{2}|c\d{4})?))(?:-f\d)?\.(?:dat|png)$#i';
        $result = preg_match($pattern, basename($part->filename), $matches);
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
        if($matches[5] != '') {
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
            $oldPart->type->folder != 'parts/'
        ) {
            return null;
        }

        $values = [
            'description' => "~Moved To " . str_replace(['.dat', '.png'], '', $newPart->name()),
            'filename' => $oldPart->filename,
            'user_id' => Auth::user()->id,
            'part_type_id' => $oldPart->type->id,
            'part_type_qualifier_id' => $oldPart->qualifier->id ?? null,
            'part_license_id' => Auth::user()->license->id,
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
            $part->description = "{$newType->name} {$newName}";
        }
        $newName = "{$newType->folder}{$newName}";
        $upart = Part::unofficial()->where('filename', $newName)->first();
        if (!$part->isUnofficial() || !is_null($upart)) {
            return false;
        }
        if ($part->type->folder !== 'parts/' && $newType->folder == 'parts/') {
            $dcat = PartCategory::firstWhere('category', $this->parser->getDescriptionCategory($part->header));
            $part->category()->associate($dcat);
        }
        if ($part->type->folder !== $newType->folder) {
            $part->type()->associate($newType);
        }
        $part->filename = $newName;
        $part->save();
        $part->generateHeader();
        $this->updateBasePart($part);
        $this->updateImage($part);
        foreach ($part->parents()->unofficial()->get() as $p) {
            if ($p->type->folder === 'parts/' && $p->category->category === "Moved") {
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
        UpdateParentParts::dispatch($part);
        return true;
    }

    public function loadSubpartsFromBody(Part $part): void
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
        if (!$part->isUnofficial()) {
            $part->can_release == true;
            $check = app(\App\LDraw\Check\PartChecker::class)->checkCanRelease($part);
            $messages['errors'] = $check['errors'];
            $messages['warnings'] = [];
            $part->part_check_messages = $messages;
            $part->save();
            return;
        }
        $check = app(\App\LDraw\Check\PartChecker::class)->checkCanRelease($part);
        $messages['warnings'] = [];
        if (isset($part->category) && $part->category->category == "Minifig") {
            $messages['warnings'] = "Check Minifig category: {$part->category->category}";
        }
        $part->can_release = $check['can_release'];
        $messages['errors'] = $check['errors'];
        $part->part_check_messages = $messages;
        $part->save();
    }

    public function addStickerSheet(Part $p)
    {
        $p->refresh();
        $sticker = $p->descendantsAndSelf->where('category.category', 'Sticker')->where('type.folder', 'parts/')->first();
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
                    $part = app(Rebrickable::class)->getPartBySearch($s[1]);
                    if (is_null($part)) {
                        $part = app(Rebrickable::class)->getPart($s[1]);
                    }
                    $sheet = StickerSheet::create([
                        'number' => $s[1],
                        'rebrickable_part_id' => null
                    ]);
                    if (!is_null($part)) {
                        $rb_part = RebrickablePart::create([
                            'part_num' => $part['rb_part_number'],
                            'name' => $part['rb_part_name'],
                            'part_url' => $part['rb_part_url'],
                            'part_img_url' => $part['rb_part_img_url'],
                            'part_id' => null
                        ]);
                        $sheet->rebrickable_part()->associate($rb_part);
                    }
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
