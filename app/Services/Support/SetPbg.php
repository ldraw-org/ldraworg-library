<?php

namespace App\Services\Support;

use App\Enums\PartCategory;
use App\Enums\PartTypeQualifier;
use App\Models\Omr\Set;
use App\Models\Part\Part;
use App\Models\RebrickablePart;
use App\Services\Omr\Set\Writer;
use App\Services\RebrickablePart\StickerSheetStickers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

class SetPbg
{
    protected const int STICKER_SHEET_CATEGORY = 58;
    protected const int MAIN_COLOUR = 16;

    public function __construct(
        public MessageBag $messages,
        protected StickerSheetStickers $stickerSheetStickers,
        protected Writer $setWriter,
    ) {
    }

    public function pbg(string $setNumber, bool $addIfMissing = false): string|false
    {
        $set = $this->setWriter->findFromSetNumber($setNumber, $addIfMissing);

        if ($set === null) {
            $this->messages->add('errors', 'Set Not Found');
            return false;
        }

        // Only a never synced set needs Rebrickable, and a newly created one has already had its attempt
        $partsAvailable = $set->refreshed_at !== null
            || (! $set->wasRecentlyCreated && $this->setWriter->syncParts($set));

        if (! $partsAvailable) {
            $this->messages->add('errors', 'Unable to load the set parts from Rebrickable');
            return false;
        }

        $items = $this->collectItems($set->rebrickable_parts()->with('parts')->get());

        return $this->makePbg($set, $this->resolveAliases($items));
    }

    /**
     * @return array<string, array<int, int>> quantities keyed by LDraw filename, then colour code
     */
    protected function collectItems(Collection $setParts): array
    {
        $printOfNumbers = $setParts->pluck('print_of')->filter()->unique();
        $printParents = RebrickablePart::with('parts')
            ->whereIn('number', $printOfNumbers)
            ->get()
            ->keyBy('number');
        $knownFilenames = Part::whereIn(
            'filename',
            $setParts->pluck('number')
                ->concat($printOfNumbers)
                ->unique()
                ->map(fn (string $number) => $this->buildFilename($number))
        )->pluck('filename')->flip();

        $items = [];
        foreach ($setParts as $part) {
            $filenames = $this->partFilenames($part, $printParents, $knownFilenames);

            if ($filenames->isEmpty()) {
                $this->addMissingPartMessage($part);
                continue;
            }

            $colour = $this->resolveColour($part);
            foreach ($filenames as $filename) {
                $items[$filename][$colour] = ($items[$filename][$colour] ?? 0) + $part->pivot->quantity;
            }
        }

        return $items;
    }

    /**
     * @return Collection<int, string>
     */
    protected function partFilenames(RebrickablePart $part, Collection $printParents, Collection $knownFilenames): Collection
    {
        if ($this->isStickerSheet($part)) {
            $stickers = $this->stickerSheetStickers->completeSet($part)->pluck('filename')->values();
            if ($stickers->isNotEmpty()) {
                return $stickers;
            }
        }

        $filename = $this->bestFilename($part->number, $part->parts, $knownFilenames);

        if ($filename === null && $part->print_of !== null) {
            $filename = $this->bestFilename(
                $part->print_of,
                $printParents->get($part->print_of)->parts ?? collect(),
                $knownFilenames
            );

            if ($filename !== null) {
                $this->messages->add('unpatterned', "{$part->number} ({$this->basename($filename)})");
            }
        }

        return collect($filename === null ? [] : [$filename]);
    }

    /**
     * A Rebrickable part can be linked to several LDraw parts, so prefer the one sharing its number
     */
    protected function bestFilename(string $number, Collection $linkedParts, Collection $knownFilenames): ?string
    {
        $exact = $this->buildFilename($number);
        $linked = $linkedParts->pluck('filename')->sort();

        if ($linked->contains($exact)) {
            return $exact;
        }

        return $linked->first() ?? ($knownFilenames->has($exact) ? $exact : null);
    }

    /**
     * @param array<string, array<int, int>> $items
     * @return array<string, array<int, int>>
     */
    protected function resolveAliases(array $items): array
    {
        $aliases = Part::whereIn('filename', array_keys($items))
            ->whereNull('unofficial_part_id')
            ->where(
                fn (Builder $query) => $query
                    ->where('category', PartCategory::Moved)
                    ->orWhere('type_qualifier', PartTypeQualifier::Alias)
            )
            ->with('subparts')
            ->get()
            ->keyBy('filename');

        $resolved = [];
        foreach ($items as $filename => $colours) {
            $target = $aliases->get($filename)?->subparts->value('filename') ?? $filename;
            foreach ($colours as $colour => $quantity) {
                $resolved[$target][$colour] = ($resolved[$target][$colour] ?? 0) + $quantity;
            }
        }

        return $resolved;
    }

    protected function resolveColour(RebrickablePart $part): int
    {
        $colour = $part->pivot->ldraw_colour_id;

        if ($colour !== null) {
            return $colour;
        }

        // Sticker sheets have no colour of their own on Rebrickable
        if (! $this->isStickerSheet($part)) {
            $this->messages->add(
                'errors',
                'LDraw color not found for part ' . e($part->number) . ', using ' . self::MAIN_COLOUR
            );
        }

        return self::MAIN_COLOUR;
    }

    /**
     * @param array<string, array<int, int>> $items
     */
    protected function makePbg(Set $set, array $items): string
    {
        $lines = [
            '[options]',
            'kind=basic',
            "caption=Set {$set->number} - {$set->name}",
            "description=Parts in set {$set->number}",
            'sortDesc=false',
            'sortOn=description',
            'sortCaseInSens=true',
            '<items>',
        ];

        foreach ($items as $filename => $colours) {
            foreach ($colours as $colour => $quantity) {
                $lines[] = "{$this->basename($filename)}.dat: [color={$colour}][count={$quantity}]";
            }
        }

        return implode("\n", $lines);
    }

    protected function addMissingPartMessage(RebrickablePart $part): void
    {
        $url = e($part->url ?? "https://rebrickable.com/parts/{$part->number}/");
        $label = e("{$part->number} ({$part->name})");

        $this->messages->add(
            'missing',
            "<a class=\"underline decoration-dotted hover:decoration-solid\" href=\"{$url}\">{$label}</a>"
        );
    }

    protected function isStickerSheet(RebrickablePart $part): bool
    {
        return (int) $part->rb_part_category_id === self::STICKER_SHEET_CATEGORY;
    }

    protected function buildFilename(string $number): string
    {
        return 'parts/' . strtolower($number) . '.dat';
    }

    protected function basename(string $filename): string
    {
        return basename($filename, '.dat');
    }
}
