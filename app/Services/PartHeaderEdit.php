<?php

namespace App\Services;

use App\Enums\ExternalSite;
use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Events\PartHeaderEdited;
use App\Jobs\UpdateRebrickable;
use App\Jobs\UpdateZip;
use App\Services\LDraw\Managers\Part\PartManager;
use App\Models\Part\Part;
use App\Models\Part\PartHistory;
use App\Models\Part\PartKeyword;
use App\Models\User;
use App\Services\Parser\ParsedPartCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PartHeaderEdit
{
    /**
     * Setup data for storing header info
     */
    public function setupHeaderData(Part $part, array $data): array
    {
        $data['help'] = implode("\n", $part->help ?? []);
        $data['keywords'] = $this->getPartKeywords($part);
        $preview = $part->previewValues();
        $data['preview_rotation'] = $preview['rotation'];
        $data['history'] = $this->getPartHistory($part);
        return $data;
    }

    /**
     * Store the header data by applying changes
     */
    public function storeHeaderData(Part $part, array $data): Part
    {
        $manager = app(PartManager::class);
        $changes = ['old' => [], 'new' => []];
    
        // Merge description and category changes
        $changes = $this->mergeChanges($changes, $this->updateDescriptionAndCategory($part, $data));
    
        // Merge category changes
        $changes = $this->mergeChanges($changes, $this->updateCategory($part, $data));
    
        // Merge type changes
        $changes = $this->mergeChanges($changes, $this->updateType($part, $data));
    
        // Merge type qualifier changes
        $changes = $this->mergeChanges($changes, $this->updateTypeQualifier($part, $data));
    
        // Merge help changes
        $changes = $this->mergeChanges($changes, $this->updateHelp($part, $data));
    
        // Merge keywords changes
        $changes = $this->mergeChanges($changes, $this->updateKeywords($part, $data));
    
        // Merge history changes
        $changes = $this->mergeChanges($changes, $this->updateHistory($part, $data));
    
        // Merge cmdline changes
        $changes = $this->mergeChanges($changes, $this->updateCmdline($part, $data));
    
        // Merge preview changes
        list($part, $previewChanged, $previewChanges) = $this->updatePreview($part, $data);
        if ($previewChanged) {
            $changes = $this->mergeChanges($changes, $previewChanges);
        }
    
        if (count($changes['new']) > 0) {
            $part->save();
            $part->refresh();
            $part->generateHeader();
            if ($previewChanged) {
                $manager->updateImage($part);
            }
            $manager->checkPart($part);
    
            // Post an event
            PartHeaderEdited::dispatch($part, Auth::user(), $changes, $data['editcomment'] ?? null);
            Auth::user()->notification_parts()->syncWithoutDetaching([$part->id]);
            UpdateZip::dispatch($part);
        }
    
        return $part;
    }

    // ---------------------------
    // Helper methods

    /**
     * Get the part's keywords as a sorted and cleaned list
     */
    private function mergeChanges(array $currentChanges, array $newChanges): array
    {
        $currentChanges['old'] = array_merge($currentChanges['old'], $newChanges['old']);
        $currentChanges['new'] = array_merge($currentChanges['new'], $newChanges['new']);
        return $currentChanges;
    }
    
      private function getPartKeywords(Part $part): string
    {
        if (is_null($part->rebrickable_part)) {
            $kws = $part->keywords;
        } else {
            $pattern = '/^(' . implode('|', ExternalSite::prefixes()) . ')/i';
            $kws = $part->keywords->filter(fn (PartKeyword $kw) =>
                !preg_match($pattern, $kw->keyword)
            );
        }
        return $kws->sortBy('keyword')->implode('keyword', ', ');
    }

    /**
     * Get the part's history formatted
     */
    private function getPartHistory(Part $part): array
    {
        return $part->history->sortBy('created_at')->map->only('created_at', 'user_id', 'comment')->all();
    }

    /**
     * Update description and category
     */
    private function updateDescriptionAndCategory(Part $part, array $data): array
    {
        $changes = ['old' => [], 'new' => []];

        if ($data['description'] !== $part->description) {
            $changes['old']['description'] = $part->description;
            $changes['new']['description'] = $data['description'];
            $part->description = $data['description'];
            
            if ($part->type->inPartsFolder()) {
                $cat = (new ParsedPartCollection($part->description))->category();
                if ($cat && $part->category !== $cat) {
                    $part->category = $cat;
                    $changes['old']['category'] = $part->category?->value;
                    $changes['new']['category'] = $cat->value;
                }
            }
        }

        return $changes;
    }

    /**
     * Update category based on user input
     */
    private function updateCategory(Part $part, array $data): array
    {
        $changes = ['old' => [], 'new' => []];

        if ($part->type->inPartsFolder() && Arr::has($data, 'category')) {
            $cat = PartCategory::tryFrom($data['category']);
            if ($cat && $part->category !== $cat) {
                $changes['old']['category'] = $part->category?->value;
                $changes['new']['category'] = $cat->value;
                $part->category = $cat;
            }
        }

        return $changes;
    }

    /**
     * Update type based on user input
     */
    private function updateType(Part $part, array $data): array
    {
        $changes = ['old' => [], 'new' => []];

        if ($part->type->inPartsFolder() && Arr::has($data, 'type')) {
            $pt = PartType::tryFrom($data['type']);
            if ($pt && $pt !== $part->type) {
                $changes['old']['type'] = $part->type->value;
                $changes['new']['type'] = $pt->value;
                $part->type = $pt;
            }
        }

        return $changes;
    }

    /**
     * Update type qualifier
     */
    private function updateTypeQualifier(Part $part, array $data): array
    {
        $changes = ['old' => [], 'new' => []];

        $pq = Arr::has($data, 'type_qualifier')
            ? PartTypeQualifier::tryFrom($data['type_qualifier'])
            : null;

        if ($part->type_qualifier !== $pq) {
            $changes['old']['qual'] = $part->type_qualifier?->value ?? '';
            $changes['new']['qual'] = $pq?->value ?? '';
            $part->type_qualifier = $pq;
        }

        return $changes;
    }

    /**
     * Update help text
     */
    private function updateHelp(Part $part, array $data): array
    {
        $changes = ['old' => [], 'new' => []];
    
        // Get the current help value from the part model, or set to an empty array
        $oldHelp = $part->help ?? [];
    
        // Normalize the incoming help data
        $newHelp = [];
        if (filled($data['help'] ?? null)) {
            // Normalize and prefix the help text
            $normalized = preg_replace("/\r\n|\r/", "\n", $data['help']);
            $prefixed = "0 !HELP " . str_replace("\n", "\n0 !HELP ", $normalized);
            $newHelp = (new ParsedPartCollection($prefixed))->help() ?? [];
        }
    
        // Compare the old and new help data after trimming and removing excess whitespace
        $normalizedOldHelp = implode("\n", array_map('trim', $oldHelp));
        $normalizedNewHelp = implode("\n", array_map('trim', $newHelp));
    
        // Only consider changes if there is an actual difference in the trimmed/normalized help
        if ($normalizedOldHelp !== $normalizedNewHelp) {
            $changes['old']['help'] = $oldHelp ? "0 !HELP " . implode("\n0 !HELP ", $oldHelp) : '';
            $changes['new']['help'] = $newHelp ? "0 !HELP " . implode("\n0 !HELP ", $newHelp) : '';
            $part->help = $newHelp;  // Update the part's help
        }
    
        return $changes;
    }

    /**
     * Update keywords based on user input
     */
    private function updateKeywords(Part $part, array $data): array
    {
        $changes = ['old' => [], 'new' => []];

        $new_kws = collect(explode(',', Str::of($data['keywords'] ?? '')->trim()->squish()->replace(["\n", ', ',' ,'], ',')->toString()))->filter();

        if (!is_null($part->rebrickable_part)) {
            $pattern = '/^(' . implode('|', ExternalSite::prefixes()) . ')/i';
            $external_kws = $part->keywords
                ->filter(fn (PartKeyword $kw) => preg_match($pattern, $kw->keyword))
                ->pluck('keyword');
            $clean_user_kws = $new_kws->filter(fn (string $kw) => !preg_match($pattern, $kw))->values();
            $new_kws = $clean_user_kws->merge($external_kws)->unique()->values();
        }

        $partKeywords = collect($part->keywords->pluck('keyword')->all());
        if ($partKeywords->diff($new_kws)->isNotEmpty() || $new_kws->diff($partKeywords)->isNotEmpty()) {
            $changes['old']['keywords'] = implode(", ", $partKeywords->all());
            $changes['new']['keywords'] = implode(", ", $new_kws->all());
            $part->setKeywords($new_kws->all());
            UpdateRebrickable::dispatch($part);
        }

        return $changes;
    }

    /**
     * Update history section
     */
    private function updateHistory(Part $part, array $data): array
    {
        $changes = ['old' => [], 'new' => []];

        $old_hist = collect($part->history->sortBy('created_at')->map(fn (PartHistory $h) => $h->toString()));

        $new_hist = collect($data['history'])
            ->map(
                fn (array $state): string =>
                '0 !HISTORY ' .
                (new Carbon(Arr::get($state, 'created_at')))->toDateString() .
                ' ' .
                (User::find(Arr::get($state, 'user_id'))?->historyString() ?? '') .
                ' ' .
                Str::of(Arr::get($state, 'comment'))->squish()->trim()->toString()
            );

        if ($new_hist->diff($old_hist)->isNotEmpty() || $old_hist->diff($new_hist)->isNotEmpty()) {
            $changes['old']['history'] = $old_hist->implode("\n");
            $part->setHistory((new ParsedPartCollection($new_hist->implode("\n")))->history() ?? []);
            $part->load('history');
            $changes['new']['history'] = collect($part->history->sortBy('created_at')->map(fn (PartHistory $h): string => $h->toString()))->implode("\n");
        }

        return $changes;
    }

    /**
     * Update cmdline section
     */
    private function updateCmdline(Part $part, array $data): array
    {
        $changes = ['old' => [], 'new' => []];

        $newCmd = Arr::get($data, 'cmdline');
        if ($part->cmdline !== $newCmd) {
            $changes['old']['cmdline'] = $part->cmdline ?? '';
            $changes['new']['cmdline'] = $newCmd ?? '';
            $part->cmdline = $newCmd;
        }

        return $changes;
    }

    /**
     * Update preview section
     */
    private function updatePreview(Part $part, array $data): array
    {
        $changes = ['old' => [], 'new' => []];
        $previewChanged = false;

        $preview = '16 0 0 0 ' . Str::of(Arr::get($data, 'preview_rotation'))->squish();
        $preview = $preview === '16 0 0 0 1 0 0 0 1 0 0 0 1' ? null : $preview;

        if ($part->preview !== $preview) {
            $changes['old']['preview'] = $part->preview ?? '16 0 0 0 1 0 0 0 1 0 0 0 1';
            $changes['new']['preview'] = $preview ?? '16 0 0 0 1 0 0 0 1 0 0 0 1';
            $part->preview = $preview;
            $previewChanged = true;
        }

        return [$part, $previewChanged, $changes];
    }
}
