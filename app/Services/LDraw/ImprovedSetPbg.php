<?php

namespace App\Services\LDraw;

use App\Enums\PartCategory;
use App\Enums\PartTypeQualifier;
use App\Models\Part\Part;
use App\Models\StickerSheet;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Cache;

class ImprovedSetPbg
{
    protected array $parts = [];
    protected array $set = [];

    public function __construct(
        public MessageBag $messages,
        protected Rebrickable $rb
    ) {}

    public function pbg(string $set_number): string|false
    {
        if (!$set_number && count($this->set) === 0) {
            $this->messages->add('errors', 'Set number empty');
            return false;
        }

        if ($set_number && ($this->set['set_num'] ?? null) !== $set_number) {
            $this->parts = [];
            if (!$this->loadSet($set_number)) {
                return false;
            }
        }

        $rb_parts = $this->rb->getSetParts($this->set['set_num']);

        $this->resolveUnpatternedParts($rb_parts);

        $rb_parts->each(fn(array $part) => $this->processPart($part));

        return $this->makePbg();
    }

    protected function loadSet(string $set_number): bool
    {
        $this->set = $this->rb->getSet($set_number)->all();

        if (!isset($this->set['set_num'])) {
            $this->messages->add('errors', 'Set Not Found');
            return false;
        }

        return true;
    }

    protected function resolveUnpatternedParts(Collection $rb_parts): void
    {
        $unpatternedParts = $rb_parts
            ->whereNull('part.external_ids.LDraw')
            ->whereNotNull('part.print_of');

        if ($unpatternedParts->isEmpty()) {
            return;
        }

        $printOfNums = $unpatternedParts->pluck('part.print_of')->unique()->all();
        $unpatterned = $this->rb->getParts(['part_nums' => $printOfNums]);

        $rb_parts->transform(function ($part) use ($unpatterned) {
            if (!$this->hasLdrawId($part) && $this->hasPrintOf($part)) {
                $replacement = $unpatterned->firstWhere('part_num', $this->getPrintOf($part));
                if ($replacement && $this->hasReplacementLdrawId($replacement)) {
                    $this->addUnpatternedMessage($part, $replacement);
                    $part['part'] = $replacement;
                }
            }
            return $part;
        });
    }

    protected function processPart(array $part): void
    {
        $resolvedColor = $this->resolveColor($part);

        if (!$this->hasLdrawId($part)) {
            $this->handleMissingPart($part, $resolvedColor);
        } else {
            $this->addPart($part, null, $resolvedColor);
        }
    }

    protected function handleMissingPart(array $part, int $color): void
    {
        $partNum = $this->getPartNumber($part);
        $filename = $this->buildFilename($partNum);

        $p = Part::where('filename', $filename)->first();
        $stickerSheet = StickerSheet::whereRelation('rebrickable_part', 'number', $partNum)->first();

        if ($p) {
            $this->addPart($part, $this->getBasename($p->filename), $color);
        } elseif ($stickerSheet) {
            $stickerSheet->complete_set()->each(
                fn(Part $sticker) => $this->addPart($part, $this->getBasename($sticker->filename), $color)
            );
        } else {
            $this->addMissingPartMessage($part);
        }
    }

    protected function addPart(array $part, ?string $ldraw_number = null, ?int $color = null): void
    {
        $color = $color ?? $this->resolveColor($part);
        $rb_part_num = $this->getPartNumber($part);
        $ldraw_part = $ldraw_number ?? $this->getPartLdrawId($part);

        // Resolve aliases and moved parts
        $ldraw_part = $this->resolvePartAlias($ldraw_part);

        $quantity = $this->getPartQuantity($part);

        if (isset($this->parts[$rb_part_num]['colors'][$color])) {
            $this->parts[$rb_part_num]['colors'][$color] += $quantity;
        } elseif (isset($this->parts[$rb_part_num])) {
            $this->parts[$rb_part_num]['colors'][$color] = $quantity;
        } else {
            $this->parts[$rb_part_num] = [
                'ldraw_part' => $ldraw_part,
                'colors' => [$color => $quantity]
            ];
        }
    }

    protected function resolvePartAlias(string $ldraw_part): string
    {
        $filename = $this->buildFilename($ldraw_part);

        $p = Part::where('filename', $filename)
            ->with('subparts')
            ->doesntHave('unofficial_part')
            ->first();

        if ($p && ($p->category === PartCategory::Moved || $p->type_qualifier === PartTypeQualifier::Alias)) {
            $subpart = $p->subparts->first();
            if ($subpart) {
                return $this->getBasename($subpart->filename);
            }
        }

        return $ldraw_part;
    }

    protected function resolveColor(array $part, ?int $fallback = null): int
    {
        $rbId = $part['color']['id'];
        $colorId = collect(Cache::get('ldraw_colour_codes', []))
          ->search(fn($value, $key) => $value === $rbId); 
        
        if ($colorId !== null) {
            return $colorId;
        }

        $colorName = $this->getColorName($part);
        $this->messages->add('errors', "LDraw color not found for {$colorName}, using fallback");

        return $fallback ?? 16;
    }

    protected function makePbg(): string
    {
        $header = [
            "[options]",
            "kind=basic",
            "caption=Set {$this->set['set_num']} - {$this->set['name']}",
            "description=Parts in set {$this->set['set_num']}",
            "sortDesc=false",
            "sortOn=description",
            "sortCaseInSens=true",
            "<items>"
        ];

        $items = collect($this->parts)
            ->flatMap(fn($part) => collect($part['colors'])
                ->map(fn($qty, $color) => "{$part['ldraw_part']}.dat: [color={$color}][count={$qty}]")
            )->all();

        return implode("\n", array_merge($header, $items));
    }

    protected function addUnpatternedMessage(array $part, array $replacement): void
    {
        $partNum = $this->getPartNumber($part);
        $ldrawNum = $this->getReplacementLdrawId($replacement);
        $this->messages->add('unpatterned', "{$partNum} ({$ldrawNum})");
    }

    protected function addMissingPartMessage(array $part): void
    {
        $url = $this->getPartUrl($part);
        $partNum = $this->getPartNumber($part);
        $name = $this->getPartName($part);
        
        $message = "<a class=\"underline decoration-dotted hover:decoration-solid\" href=\"{$url}\">{$partNum} ({$name})</a>";
        $this->messages->add('missing', $message);
    }

    protected function buildFilename(string $partNum): string
    {
        return "parts/{$partNum}.dat";
    }

    protected function getBasename(string $filename): string
    {
        return basename($filename, '.dat');
    }

    protected function hasLdrawId(array $part): bool
    {
        return isset($part['part']['external_ids']['LDraw']);
    }

    protected function hasPrintOf(array $part): bool
    {
        return isset($part['part']['print_of']);
    }

    protected function hasReplacementLdrawId(array $replacement): bool
    {
        return isset($replacement['external_ids']['LDraw']);
    }

    protected function getPartNumber(array $part): string
    {
        return $part['part']['part_num'];
    }

    protected function getPartLdrawId(array $part): string
    {
        return $part['part']['external_ids']['LDraw'][0];
    }

    protected function getPartQuantity(array $part): int
    {
        return $part['quantity'];
    }

    protected function getPartUrl(array $part): string
    {
        return $part['part']['part_url'];
    }

    protected function getPartName(array $part): string
    {
        return $part['part']['name'];
    }

    protected function getPrintOf(array $part): string
    {
        return $part['part']['print_of'];
    }

    protected function getColorLdrawId(array $part): ?int
    {
        return $part['color']['external_ids']['LDraw']['ext_ids'][0] ?? null;
    }

    protected function getColorName(array $part): string
    {
        return $part['color']['name'] ?? 'Unknown';
    }

    protected function getReplacementLdrawId(array $replacement): string
    {
        return $replacement['external_ids']['LDraw'][0];
    }
}