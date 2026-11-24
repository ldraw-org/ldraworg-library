<?php

namespace App\Services\Part\Release;

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use Illuminate\Support\Facades\DB;

class ReleaseStatistics
{
    /**
     * The name and short code for the next release of the current year.
     *
     * @return array{name: string, short: string}
     */
    public function nextReleaseNumber(): array
    {
        $now = now();
        $number = (PartRelease::whereYear('created_at', $now->year)
            ->max(DB::raw('CAST(RIGHT(name,2) AS UNSIGNED)')) ?? 0) + 1;
        $number = str_pad((string) $number, 2, '0', STR_PAD_LEFT);

        return [
            'name' => "{$now->year}-{$number}",
            'short' => $now->format('y') . $number,
        ];
    }

    /**
     * Summarise the parts marked for release and pending official minor edits.
     *
     * @return array{
     *     total: int,
     *     new: int,
     *     new_of_type: array<string, int>,
     *     moved: list<array{name: string, moved_to: string}>,
     *     renamed: list<array{name: string, description: string, old_description: string}>,
     *     fixed: list<array{name: string, description: string}>,
     *     minor_edits: int
     * }
     */
    public function collect(): array
    {
        $newOfType = [];
        foreach (PartType::cases() as $type) {
            $newOfType[$type->value] = Part::where('marked_for_release', true)
                ->doesntHave('official_part')
                ->where('type', $type)
                ->count();
        }
        $newOfType[PartType::Part->value] += $newOfType[PartType::Shortcut->value];
        unset($newOfType[PartType::Shortcut->value]);

        $moved = Part::where('marked_for_release', true)
            ->where('category', PartCategory::Moved)
            ->get()
            ->map(fn (Part $part): array => ['name' => $part->meta_name, 'moved_to' => $part->description])
            ->all();

        $renamed = [];
        $fixed = [];
        Part::with('official_part')
            ->where('marked_for_release', true)
            ->has('official_part')
            ->where('category', '!=', PartCategory::Moved)
            ->each(function (Part $part) use (&$renamed, &$fixed): void {
                if ($part->description != $part->official_part->description) {
                    $renamed[] = [
                        'name' => $part->meta_name,
                        'description' => $part->description,
                        'old_description' => $part->official_part->description,
                    ];
                } else {
                    $fixed[] = ['name' => $part->meta_name, 'description' => $part->description];
                }
            });

        return [
            'total' => Part::where('marked_for_release', true)->count(),
            'new' => Part::where('marked_for_release', true)->doesntHave('official_part')->count(),
            'new_of_type' => $newOfType,
            'moved' => $moved,
            'renamed' => $renamed,
            'fixed' => $fixed,
            'minor_edits' => Part::releasableMinorEdits()->count(),
        ];
    }
}
