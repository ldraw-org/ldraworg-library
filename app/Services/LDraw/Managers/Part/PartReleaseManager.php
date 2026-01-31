<?php

namespace App\Services\LDraw\Managers\Part;

use App\Enums\PartCategory;
use App\Enums\PartStatus;
use App\Enums\PartType;
use App\Events\PartReleased;
use App\Jobs\CheckPart;
use App\Jobs\UpdateImage;
use App\Services\LDraw\ZipFiles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Part\PartRelease;
use App\Models\Part\Part;
use App\Models\Part\PartEvent;
use App\Models\Part\PartHistory;
use App\Models\User;
use App\Settings\LibrarySettings;
use Illuminate\Support\Facades\Log;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class PartReleaseManager
{
    protected PartRelease $release;
    protected PartManager $manager;
    protected LibrarySettings $settings;
    protected ZipFiles $zipfiles;
    protected string $stagingFolder = "release-staging";
    protected Collection $parts;
  
    public function __construct(
        protected array $partIds,
        protected User $user,
        protected bool $includeLdconfig = false,
        protected array $extraFiles = []
    ) {
        $this->manager = app(PartManager::class);
        $this->settings = app(LibrarySettings::class);
        $this->zipfiles = app(ZipFiles::class);
        $this->parts = Part::where('marked_for_release', true)->get();
    }

    public function createNextRelease(): PartRelease
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
        return PartRelease::create([
            'name' => $now->format('Y')."-{$update}",
            'short' => $now->format('y')."{$update}",
        ]);
    }


    public function createRelease(): void
    {
        $this->settings->tracker_locked = true;
        $this->settings->save();
        Log::debug('Creating Release');
        $this->makeNextRelease();
        Log::debug('Releasing Parts');
        $this->releaseParts();
        Part::canHaveRebrickablePart()
            ->where(
                fn ($q) => $q->where('part_release_id', $this->release->id)->orWhere('has_minor_edit', true)
            )
            ->each(fn (Part $p) => $p->setExternalSiteKeywords(true));
        $this->settings->tracker_locked = false;
        $this->settings->save();
        $this->copyReleaseFiles();
        Log::debug('Post Release Cleanup');
        $this->postReleaseCleanup();
        Log::debug('Release Complete');
    }

    protected function makeNextRelease(): void
    {
        //Figure out next update number
        $next = $this->getNextUpdateNumber();

        // Get part data
        $part_data = $this->getReleaseData();

        // create release
        $this->release = PartRelease::create([
            'name' => $next['name'],
            'short' => $next['short'],
            'total' => $part_data['total'],
            'new' => $part_data['new'],
            'new_of_type' => $part_data['new_of_type'],
        ]);
        // Make notes
        $notes = $this->makeNotes($part_data);
        $notesFile = Storage::put("{$this->stagingFolder}/Note{$this->release->short}CA.txt");
        file_put_contents($notesFile, $notes);
        $this->release->save();
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
        $data['total'] = $this->parts->count();
        $data['new'] = $this->parts->whereNull('official_part')->count();
        $data['new_of_type'] = [];
        foreach (PartType::cases() as $type) {
            $count = $this->parts
                ->whereNull('official_part')
                ->where('type', $type)
                ->count();
            $data['new_of_type'][$type->value] = $count;
        }
        $data['new_of_type'][PartType::Part->value] =
            $data['new_of_type'][PartType::Part->value] + $data['new_of_type'][PartType::Shortcut->value];
        unset($data['new_of_type'][PartType::Shortcut->value]);
        $data['moved_parts'] = [];
        $moved = $this->parts->where('category', PartCategory::Moved);
        foreach ($moved as $part) {
            /** @var Part $part */
            $data['moved_parts'][] = ['name' => $part->meta_name,  'movedto' => $part->description];
        }
        $data['fixes'] = [];
        $data['rename'] = [];
        $notMoved = $this->parts
            ->whereNotNull('official_part')
            ->where('category', '!=', PartCategory::Moved);
        foreach ($notMoved as $part) {
            /** @var Part $part */
            if ($part->description != $part->official_part->description) {
                $data['rename'][] = ['name' => $part->meta_name, 'decription' => $part->description, 'old_description' => $part->official_part->description];
            } else {
                $data['fixed'][] = ['name' => $part->meta_name, 'decription' => $part->description];
            }
        }
        $data['minor_edits'] = Part::official()->where('has_minor_edit', true)->count();
        return $data;
    }

    protected function makeNotes(array $data): string
    {
        $notes = "ldraw.org Parts Update {$this->release->name}\n" .
            str_repeat('-', 76) . "\n\n" .
            "Redistributable Parts Library - Core Library\n" .
            str_repeat('-', 76) . "\n\n" .
            "Notes created " . $this->release->created_at->format("r") . " by the Parts Tracker\n\n" .
            "Release statistics:\n" .
            "   Total files: {$data['total']}\n" .
            "   New files: {$data['new']}\n";
        foreach ($data as $cat => $value) {
            switch ($cat) {
                case 'new_of_type':
                    foreach ($value as $type => $count) {
                        if ($count > 0) {
                            $notes .= "   New {$type}s: {$count}\n";
                        }
                    }
                    break;
                case 'moved_parts':
                    $notes .= "\nMoved Parts\n";
                    foreach ($value as $m) {
                        $notes .= "   {$m['name']}" . str_repeat(' ', max(27 - strlen($m['name']), 0)) . "{$m['movedto']}\n";
                    }
                    break;
                case 'rename':
                    $notes .= "\nRenamed Parts\n";
                    foreach ($value as $m) {
                        $notes .= "   {$m['name']}" . str_repeat(' ', max(27 - strlen($m['name']), 0)) . "{$m['old_description']}\n" .
                        "   changed to    {$m['decription']}\n";
                    }
                    break;
                case 'fixed':
                    $notes .= "\nOther Fixed Parts\n";
                    foreach ($value as $m) {
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
        // Release marked parts
        $this->parts
            ->each(fn (Part $part) => $this->releasePart($part));

        // Release minor edits
        Part::official()
            ->where('has_minor_edit', true)
            ->whereDoesntHave('unofficial_part')
            ->each(function (Part $part) {
                // Add history line
                PartHistory::create([
                    'user_id' => $this->user->id,
                    'part_id' => $part->id,
                    'comment' => "Minor header edits"
                ]);
                PartHistory::create([
                    'user_id' => $this->user->id,
                    'part_id' => $part->id,
                    'comment' => "Official Update {$this->release->name}"
                ]);
                $part->part_release_id = $this->release->id;
                $part->has_minor_edit = false;
                $part->save();
                $part->refresh();
                $part->generateHeader();
                $part->save();
            });
    }

    protected function releasePart(Part $part): void
    {
        if ($part->isOfficial()) {
            return;
        }
        // Add history line
        PartHistory::create([
            'user_id' => $this->user->id,
            'part_id' => $part->id,
            'comment' => "Official Update {$this->release->name}"
        ]);

        $imagePath = "{$this->stagingFolder}/view/" . substr($part->filename, 0, -4) . '.png';
        Storage::put($imagePath);
        file_put_contents($imagePath, file_get_contents($part->getFirstMediaPath('image')));

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
            $part->clearMediaCollection('image');
            $part->save();
            $part->refresh();
            $part->generateHeader();
            $part->save();
            if ($part->type->inPartsFolder()) {
                $this->release
                    ->addMedia(Storage::path($imagePath), 'image')
                    ->withCustomProperties([
                        'description' => $part->description,
                        'filename' => $part->filename,
                        'id' => $part->id,
                    ])
                    ->toMediaCollection('view');
            }
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
            'help' => $upart->help,
            'header' => $upart->header,
            'rebrickable_part_id' => $upart->rebrickable_part_id,
            'preview' => $upart->preview,
        ];
        $opart->fill($values);
        $opart->setSubparts($upart->subparts);
        $opart->setKeywords($upart->keywords->pluck('keyword')->values()->all());
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
        Storage::move('library/updates/complete.zip', "library/updates/complete-{$previousRelease->short}.zip");
        Storage::move('library/updates/LDrawParts.exe', "library/updates/LDraw{$previousRelease->short}.exe");

        // Make and copy the new archives to the library
        Log::debug('Making Zips');
        $this->zipfiles->releaseZips($this->release, $this->extraFiles, Storage::path("{$this->stagingFolder}/Note{$this->release->short}CA.txt")), $this->includeLdconfig, Storage::path($this->stagingFolder));
        Storage::move("{$this->stagingFolder}/lcad{$this->release->short}.zip", "library/updates/lcad{$this->release->short}.zip");
        Storage::move("{$this->stagingFolder}/complete.zip", "library/updates/complete.zip");

        //Copy release notes
        Storage::move("{$this->stagingFolder}/Note{$this->release->short}CA.txt", "library/official/models/Note{$this->release->short}CA.txt");

        // Copy the new non-Part files to the library
        foreach ($this->extraFiles as $filename => $contents) {
            Storage::put("library/official/ldraw/{$filename}", $contents);
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
        Storage::delete('library/unofficial/ldrawunf.zip');
        $this->zipfiles->unofficialZip(Part::unofficial()->first());

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
