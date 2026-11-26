<?php

namespace App\Services\Part;

use App\Enums\ExternalSite;
use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
use App\Models\User;
use App\Services\Parser\ParsedPartCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Builds minifig torso shortcut files from an official torso assembly template and a 973 torso.
 *
 * @phpstan-type TemplatePart array{name: string, description: string, position: string, variations: array<string, string>}
 * @phpstan-type Template array{id: int, name: string, description: string, parts: list<TemplatePart>}
 */
class TorsoShortcutMaker
{
    /**
     * Parses a template part into its subfile references, with the official pattern variations of each.
     *
     * @return Template
     */
    public function template(Part $template): array
    {
        $template->loadMissing([
            'body',
            'subparts.patterns' => fn ($query) => $query->whereNull('unofficial_part_id')->activeParts(),
        ]);

        $parts = [];
        foreach ((new ParsedPartCollection($template->body->body))->where('linetype', 1) as $line) {
            $subpart = $template->subparts->firstWhere('meta_name', $line['file']);
            $variations = $this->isTorso($line['file']) || is_null($subpart)
                ? []
                : $subpart->patterns
                    ->mapWithKeys(fn (Part $p) => [$p->meta_name => "{$p->meta_name} - {$p->description}"])
                    ->toArray();

            $parts[] = [
                'name' => $line['file'],
                'description' => $subpart->description ?? '',
                'position' => implode(' ', [
                    $line['x1'], $line['y1'], $line['z1'],
                    $line['a'], $line['b'], $line['c'],
                    $line['d'], $line['e'], $line['f'],
                    $line['g'], $line['h'], $line['i'],
                ]),
                'variations' => $variations,
            ];
        }

        return [
            'id' => $template->id,
            'name' => $template->meta_name,
            'description' => $template->description,
            'parts' => $parts,
        ];
    }

    public function isTorso(string $file): bool
    {
        return Str::startsWith($file, '973');
    }

    public function defaultName(string $templateName, Part $torso): string
    {
        return basename($templateName, '.dat') . Str::chopStart(basename($torso->meta_name), '973');
    }

    public function defaultDescription(string $templateDescription, Part $torso): string
    {
        return $templateDescription . Str::chopStart($torso->description, 'Minifig Torso');
    }

    /**
     * The torso's keywords, minus the external site numbers which are entered separately.
     */
    public function defaultKeywords(Part $torso): string
    {
        $pattern = '/^(' . implode('|', ExternalSite::prefixes()) . ')/i';

        return $torso->keywords
            ->reject(fn (PartKeyword $kw) => preg_match($pattern, $kw->keyword))
            ->sortBy('keyword')
            ->implode('keyword', ', ');
    }

    public function normalizeName(string $name): string
    {
        return Str::finish($name, '.dat');
    }

    public function header(string $description, string $name, string $keywords, User $user): string
    {
        return implode("\n", [
            "0 {$description}",
            '0 Name: ' . $this->normalizeName($name),
            $user->toString(),
            PartType::Part->ldrawString(true),
            $user->license->ldrawString(),
            '',
            '0 BFC CERTIFY CCW',
            '',
            PartCategory::MinifigUpper->ldrawString(),
            "0 !KEYWORDS {$keywords}",
            '',
        ]);
    }

    /**
     * @param Template $template
     * @param array<string, mixed> $data form state: description, name, keywords, external site numbers and parts.{index}.variation/color
     */
    public function text(array $template, array $data, Part $torso, User $user): string
    {
        $keywords = [];
        foreach (ExternalSite::cases() as $site) {
            $number = Arr::get($data, $site->value);
            if (filled($number)) {
                $keywords[] = "{$site->name} {$number}";
            }
        }
        $keywords[] = Arr::get($data, 'keywords');

        $text = [$this->header($data['description'], $data['name'], implode(', ', $keywords), $user)];
        foreach ($template['parts'] as $index => $part) {
            $color = Arr::get($data, "parts.{$index}.color") ?? 16;
            $file = $this->isTorso($part['name'])
                ? $torso->meta_name
                : Arr::get($data, "parts.{$index}.variation") ?? $part['name'];
            $text[] = "1 {$color} {$part['position']} {$file}";
        }
        $text[] = '';

        return implode("\n", $text);
    }
}
