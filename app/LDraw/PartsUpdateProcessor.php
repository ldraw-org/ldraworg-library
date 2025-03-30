<?php

namespace App\LDraw;

use App\Enums\PartCategory;
use App\Enums\PartStatus;
use App\Enums\PartType;
use App\Events\PartReleased;
use App\Jobs\CheckPart;
use App\Jobs\UpdateImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Part\PartRelease;
use App\Models\Part\Part;
use App\Models\Part\PartEvent;
use App\Models\Part\PartHistory;
use App\Models\User;
use App\Settings\LibrarySettings;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class PartsUpdateProcessor
{
    protected PartRelease $release;
    protected PartManager $manager;
    protected TemporaryDirectory $tempDir;
    protected LibrarySettings $settings;

    public function __construct(
        protected Collection $parts,
        protected User $user,
        protected bool $includeLdconfig = false,
        protected array $extraFiles = []
    ) {
        $this->manager = app(PartManager::class);
        $this->tempDir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $this->settings = app(LibrarySettings::class);
    }

    public function createRelease(): void
    {
        $this->settings->tracker_locked = true;
        $this->settings->save();
        $this->makeNextRelease();
        $this->releaseParts();
        Part::canHaveRebrickablePart()
            ->doesntHave('sticker_sheet')
            ->where(
                    fn ($q) => $q->where('part_release_id', $this->release->id)->orWhere('has_minor_edit', true)
            )
            ->each(fn (Part $p) => $p->setExternalSiteKeywords(true));
        $this->settings->tracker_locked = false;
        $this->settings->save();
        $this->copyReleaseFiles();
        $this->postReleaseCleanup();
    }

    protected function makeNextRelease(): void
    {
        //Figure out next update number
        $next = $this->getNextUpdateNumber();
        // create release
        $this->release = PartRelease::create([
            'name' => $next['name'],
            'short' => $next['short'],
            'part_data' => $this->getReleaseData(),
        ]);
        $notes = $this->tempDir->path("Note{$this->release->short}CA.txt");
        file_put_contents($notes, $this->makeNotes());
    }

    protected function getNextUpdateNumber(): array
    {
        $current = PartRelease::latest()->first();
        $now = now();
        if ($now->format('Y') !== $current->created_at->format('Y')) {
            $update = '01';
        } else {
            $num = (int) substr($current->name, -2) + 1;
            if ((int) $num <= 9) {
                $update = "0{$num}";
            } else {
                $update = $num;
            }
        }
        $name = $now->format('Y')."-{$update}";
        $short = $now->format('y')."{$update}";
        return compact('name', 'short');
    }

    protected function getReleaseData(): array
    {
        $data = [];
        $data['total_files'] = $this->parts->count();
        $data['new_files'] = $this->parts->whereNull('official_part')->count();
        $data['new_types'] = [];
        foreach (PartType::cases() as $type) {
            if ($type == PartType::Shortcut) {
                continue;
            }
            if ($type->inPartsFolder()) {
                $count = $this->parts
                    ->whereNull('official_part')
                    ->partsFolderOnly()
                    ->count();
            } else {
                $count = $this->parts
                    ->whereNull('official_part')
                    ->where('type', $type)
                    ->count();
            }
            if ($count > 0) {
                $data['new_types'][] = ['name' => $type->description(), 'count' => $count];
            }
        }
        $data['moved_parts'] = [];
        $moved = $this->parts->where('category', PartCategory::Moved);
        foreach ($moved as $part) {
            /** @var Part $part */
            $data['moved_parts'][] = ['name' => $part->name(),  'movedto' => $part->description];
        }
        $data['fixes'] = [];
        $data['rename'] = [];
        $notMoved = $this->parts
            ->whereNotNull('official_part')
            ->where('category', '!=', PartCategory::Moved);
        foreach ($notMoved as $part) {
            /** @var Part $part */
            if ($part->description != $part->official_part->description) {
                $data['rename'][] = ['name' => $part->name(), 'decription' => $part->description, 'old_description' => $part->official_part->description];
            } else {
                $data['fixed'][] = ['name' => $part->name(), 'decription' => $part->description];
            }
        }
        $data['minor_edits'] = Part::official()->where('has_minor_edit', true)->count();
        return $data;
    }

    protected function makeNotes(): string
    {
        $data = $this->release->part_data;
        $notes = "ldraw.org Parts Update {$this->release->name}\n" .
            str_repeat('-', 76) . "\n\n" .
            "Redistributable Parts Library - Core Library\n" .
            str_repeat('-', 76) . "\n\n" .
            "Notes created " . $this->release->created_at->format("r") . " by the Parts Tracker\n\n" .
            "Release statistics:\n" .
            "   Total files: {$data['total_files']}\n" .
            "   New files: {$data['new_files']}\n";
        foreach ($data as $cat => $value) {
            switch ($cat) {
                case 'new_types':
                    foreach ($data['new_types'] as $t) {
                        $notes .= "   New {$t['name']}s: {$t['count']}\n";
                    }
                    break;
                case 'moved_parts':
                    $notes .= "\nMoved Parts\n";
                    foreach ($data['moved_parts'] as $m) {
                        $notes .= "   {$m['name']}" . str_repeat(' ', max(27 - strlen($m['name']), 0)) . "{$m['movedto']}\n";
                    }
                    break;
                case 'rename':
                    $notes .= "\nRenamed Parts\n";
                    foreach ($data['rename'] as $m) {
                        $notes .= "   {$m['name']}" . str_repeat(' ', max(27 - strlen($m['name']), 0)) . "{$m['old_description']}\n" .
                        "   changed to    {$m['decription']}\n";
                    }
                    break;
                case 'fixed':
                    $notes .= "\nOther Fixed Parts\n";
                    foreach ($data['fixed'] as $m) {
                        $notes .= "   {$m['name']}" . str_repeat(' ', max(27 - strlen($m['name']), 0)) . "{$m['decription']}\n";
                    }
                    break;
                case 'minor_edits':
                    $notes .= "\nMinor Edits\n";
                    $notes .=  "   {$data['minor_edits']} Parts with minor administrative edits and/or license changes\n";
                    break;
            }
        }
        return $notes;
    }

    protected function releaseParts(): void
    {
        foreach ($this->parts as $part) {
            /** @var Part $part */
            $this->updatePartsList($part);
            $this->releasePart($part);
        }

        if (!is_null($this->release->part_list)) {
            $partslist = $this->release->part_list;
            usort($partslist, function (array $a, array $b) {
                return $a[0] <=> $b[0];
            });
            $this->release->part_list = $partslist;
            $this->release->save();
        }
    }

    protected function updatePartsList(Part $part): void
    {
        if (is_null($part->official_part) && $part->type->inPartsFolder()) {
            $pl = $this->release->part_list ?? [];
            $pl[] = [$part->description, $part->filename];
            $f = substr($part->filename, 0, -4);
            $this->tempDir->path("view{$this->release->short}");
            if ($part->isTexmap()) {
                $tempPath = $this->tempDir->path("view{$this->release->short}/{$part->filename}");
                $contents = $part->get();
                file_put_contents($tempPath, $contents);
            } elseif (Storage::disk('images')->exists("library/unofficial/{$f}.png")) {
                $tempPath = $this->tempDir->path("view{$this->release->short}/{$f}.png");
                $contents = Storage::disk('images')->get("library/unofficial/{$f}.png");
                file_put_contents($tempPath, $contents);
            }
            if (Storage::disk('images')->exists("library/unofficial/{$f}_thumb.png")) {
                $tempPath = $this->tempDir->path("view{$this->release->short}/{$f}_thumb.png");
                $contents = Storage::disk('images')->get("library/unofficial/{$f}_thumb.png");
                file_put_contents($tempPath, $contents);
            }
            $this->release->part_list = $pl;
        }
    }

    protected function releasePart(Part $part): void
    {
        if (!$part->isUnofficial()) {
            return;
        }
        // Add history line
        PartHistory::create([
            'user_id' => $this->user->id,
            'part_id' => $part->id,
            'comment' => "Official Update {$this->release->name}"
        ]);


        PartEvent::unofficial()->where('part_id', $part->id)->update(['part_release_id' => $this->release->id]);

        PartReleased::dispatch($part, $this->user, $this->release);

        if (!is_null($part->official_part)) {
            $opart = $this->updateOfficialWithUnofficial($part, $part->official_part);
            // Update events with official part id
            PartEvent::where('part_release_id', $this->release->id)
                ->where('part_id', $part->id)
                ->update(['part_id' => $opart->id]);
            $part->deleteQuietly();
        } else {
            $part->part_release_id = $this->release->id;
            $part->save();
            $part->refresh();
            $part->generateHeader();
            $part->save();
        }
    }

    protected function updateOfficialWithUnofficial(Part $upart, Part $opart): Part
    {
        $values = [
            'description' => $upart->description,
            'filename' => $upart->filename,
            'user_id' => $upart->user_id,
            'type' => $upart->type,
            'type_qualifier' => $upart->type_qualifier,
            'part_release_id' => $this->release->id,
            'license' => $upart->license,
            'bfc' => $upart->bfc,
            'category' => $upart->category,
            'cmdline' => $upart->cmdline,
            'header' => $upart->header,
            'rebrickable_part_id' => $upart->rebrickable_part_id,
            'sticker_sheet_id' => $upart->sticker_sheet_id,
        ];
        $opart->fill($values);
        $opart->setSubparts($upart->subparts);
        $opart->setKeywords($upart->keywords);
        $opart->setHelp($upart->help);
        $opart->setHistory($upart->history);
        $opart->setBody($upart->body);
        $opart->save();
        $opart->refresh();
        $opart->generateHeader();
        $this->manager->updateBasePart($opart);
        $opart->save();
        return $opart;
    }

    protected function copyReleaseFiles(): void
    {
        $previousRelease = PartRelease::where('id', '<>', $this->release->id)->latest()->first();

        // Archive the previous complete zip and exe
        Storage::disk('library')->move('updates/complete.zip', "updates/complete-{$previousRelease->short}.zip");
        Storage::disk('library')->move('updates/LDrawParts.exe', "updates/LDraw{$previousRelease->short}.exe");

        // Make and copy the new archives to the library
        ZipFiles::releaseZips($this->release, $this->extraFiles, file_get_contents($this->tempDir->path("Note{$this->release->short}CA.txt")), $this->includeLdconfig, $this->tempDir);
        Storage::disk('library')->put("updates/lcad{$this->release->short}.zip", file_get_contents($this->tempDir->path("lcad{$this->release->short}.zip")));
        Storage::disk('library')->put("updates/complete.zip", file_get_contents($this->tempDir->path("complete.zip")));

        //Copy release notes
        $notes = file_get_contents($this->tempDir->path("Note{$this->release->short}CA.txt"));
        Storage::disk('library')->put("official/models/Note{$this->release->short}CA.txt", $notes);

        // Copy the new non-Part files to the library
        foreach ($this->extraFiles as $filename => $contents) {
            Storage::disk('library')->put("official/ldraw/{$filename}", $contents);
        }

        // Copy the part preview images to images
        $dir = new RecursiveDirectoryIterator($this->tempDir->path("view{$this->release->short}"));
        foreach (new RecursiveIteratorIterator($dir) as $file) {
            if ($file->isFile()) {
                $image = file_get_contents($file->getPath() . "/" . $file->getFilename());
                $fn = str_replace($this->tempDir->path("view{$this->release->short}"), '', $file->getPath() . "/" . $file->getFilename());
                Storage::disk('images')->put("library/updates/view{$this->release->short}{$fn}", $image);
            }
        }

        $this->release->enabled = true;
        $this->release->save();
    }

    public function postReleaseCleanup()
    {
        // Zero/null out vote and flag data
        Part::official()->update([
            'part_status' => PartStatus::Official,
            'delete_flag' => 0,
            'has_minor_edit' => false,
            'missing_parts' => null,
            'manual_hold_flag' => 0,
            'marked_for_release' => false
        ]);
        Part::official()->each(function (Part $p) {
            $p->votes()->delete();
            $p->notification_users()->sync([]);
        });
        Part::unofficial()->where('part_status', PartStatus::Certified)->where('can_release', true)->update([
            'marked_for_release' => true
        ]);
        Part::unofficial()->where('can_release', false)->where('marked_for_release', true)->update([
            'marked_for_release' => false
        ]);

        // Reset the unofficial zip file
        Storage::disk('library')->delete('unofficial/ldrawunf.zip');
        ZipFiles::unofficialZip(Part::unofficial()->first());

        //Recheck and regenerate affected parts
        $affectedParts = new Collection();
        $this->release
            ->load('parts')
            ->parts
            ->load('ancestorsAndSelf')
            ->each(function (Part $p) use (&$affectedParts) {
                $affectedParts = $affectedParts->concat($p->ancestorsAndSelf->unique())->unique();
            });
        $affectedParts->each(function (Part $p) {
            UpdateImage::dispatch($p);
            $this->manager->loadSubparts($p);
        });
        CheckPart::dispatch($affectedParts);
   }
}
