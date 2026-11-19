<?php

namespace App\Services\RebrickablePart;

use App\Enums\RebrickableCsvFile;
use App\Models\Part\Part;
use App\Models\RebrickablePart;
use App\Services\External\RebrickableCsv;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class Writer
{
    public function __construct(
        protected RebrickableCsv $rebrickableCsv,
    ) {
    }

    /**
     * Ids of the given Rebrickable parts keyed by number: stored ones first, then any others created from
     * parts.csv. Created parts have no external ids yet; the next full import adds them. Numbers in neither
     * are left out.
     *
     * @param array<int, string> $numbers
     * @return array<string, int>
     */
    public function idsFromDatabaseOrCsv(array $numbers): array
    {
        $ids = RebrickablePart::whereIn('number', $numbers)->pluck('id', 'number')->all();
        $missing = array_flip(array_diff($numbers, array_keys($ids)));
        if ($missing === []) {
            return $ids;
        }

        try {
            $this->rebrickableCsv->rows(RebrickableCsvFile::Parts)
                ->filter(fn (array $row) => isset($missing[$row['part_num']]))
                ->each(function (array $row) use (&$ids) {
                    $ids[$row['part_num']] = RebrickablePart::firstOrCreate(
                        ['number' => $row['part_num']],
                        [
                            'name' => $row['name'],
                            'rb_part_category_id' => (int) $row['part_cat_id'],
                            'url' => "https://rebrickable.com/parts/{$row['part_num']}/",
                        ]
                    )->id;
                });
        } catch (FileNotFoundException) {
            // Without the downloads only the stored parts are known
        }

        return $ids;
    }

    /**
     * Finds the imported Rebrickable part the way the API's ldraw_id lookup does: a part mapped to the
     * LDraw number (the same number first, then the lowest), else a part with the same number.
     */
    public function findForPart(Part $part): ?RebrickablePart
    {
        $partNum = basename($part->filename, '.dat');

        $mapped = RebrickablePart::where('is_local', false)
            ->whereJsonContains('ldraw', $partNum)
            ->get();

        return $mapped->firstWhere('number', $partNum)
            ?? $mapped->sortBy('number', SORT_STRING)->first()
            ?? RebrickablePart::where('is_local', false)->firstWhere('number', $partNum);
    }

    /**
     * Links the part to its Rebrickable part, leaving the existing link alone when nothing matches.
     */
    public function linkPart(Part $part): ?RebrickablePart
    {
        $rb = $this->findForPart($part);

        if (is_null($rb)) {
            return null;
        }

        $part->rebrickable_part()->associate($rb);
        $part->save();

        return $rb;
    }
}
