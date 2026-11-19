<?php

namespace App\Services\RebrickablePart;

use App\Enums\RebrickableCsvFile;
use App\Models\RebrickablePart;
use App\Services\External\RebrickableCsv;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

/**
 * Creates or updates every Rebrickable part from the CSV downloads. Only new or changed rows are written.
 *
 * The lookups are held as tab-joined strings and the stored parts are read a chunk at a time,
 * which keeps memory well under what nested arrays or a full table load would need.
 */
class ImportRebrickableParts
{
    protected const int CHUNK = 1000;

    protected const int STICKER_CATEGORY = 58;

    /** CSV ext_system => rebrickable_parts column */
    protected const array MAPPED_COLUMNS = [
        'BL' => 'bricklink',
        'BO' => 'brickowl',
        'LE' => 'lego',
        'LD' => 'ldraw',
        'BS' => 'brickset',
    ];

    /** Columns that feed a part's external site keywords */
    protected const array EXTERNAL_ID_COLUMNS = ['bricklink', 'brickowl', 'lego', 'brickset'];

    protected const array JSON_COLUMNS = ['bricklink', 'brickowl', 'lego', 'ldraw', 'brickset'];

    protected const array COMPARED_COLUMNS = ['id', 'number', 'name', 'rb_part_category_id', 'url', 'element', 'print_of', 'is_local', ...self::JSON_COLUMNS];

    public function __construct(
        protected RebrickableCsv $rebrickableCsv,
    ) {
    }

    /**
     * @return array{created: int, updated: int, retired_stickers: int, external_ids_changed: array<int, int>}
     *     external_ids_changed holds the ids of existing parts whose external site ids changed
     */
    public function handle(): array
    {
        $mappings = $this->mappings();
        $printOf = $this->printOf();
        $elements = $this->elements();

        $result = ['created' => 0, 'updated' => 0, 'retired_stickers' => 0, 'external_ids_changed' => []];
        $seen = [];
        $now = now();

        $this->rebrickableCsv->rows(RebrickableCsvFile::Parts)
            ->chunk(self::CHUNK)
            ->each(function (LazyCollection $rows) use ($mappings, $printOf, $elements, &$result, &$seen, $now) {
                $existing = $this->existing($rows->pluck('part_num')->all());
                $upserts = [];

                foreach ($rows as $row) {
                    $number = $row['part_num'];
                    $seen[$number] = true;
                    $current = $existing->get($number);
                    $values = $this->values($row, $current, $mappings, $printOf[$number] ?? null, $elements[$number] ?? null);

                    if (is_null($current)) {
                        $result['created']++;
                    } elseif ($this->isUnchanged($current, $values)) {
                        continue;
                    } else {
                        $result['updated']++;
                        if ($this->externalIdsChanged($current, $values)) {
                            $result['external_ids_changed'][] = $current->id;
                        }
                    }

                    $upserts[] = [
                        ...$values,
                        ...array_map(
                            fn (?array $ids) => is_null($ids) ? null : json_encode($ids),
                            array_intersect_key($values, array_flip(self::JSON_COLUMNS))
                        ),
                        'refreshed_at' => $now,
                    ];
                }

                RebrickablePart::upsert($upserts, ['number']);
            });

        // Sticker sheets Rebrickable no longer lists are kept as local sheets, as their LDraw stickers still point at them
        $retired = RebrickablePart::where('rb_part_category_id', self::STICKER_CATEGORY)
            ->where('is_local', false)
            ->pluck('id', 'number')
            ->reject(fn (int $id, string $number) => isset($seen[$number]));
        $retired->chunk(self::CHUNK)->each(fn (Collection $ids) => RebrickablePart::whereKey($ids->values())->update(['is_local' => true]));
        $result['retired_stickers'] = $retired->count();

        return $result;
    }

    /**
     * @param array<string, array<string, string>> $mappings
     */
    protected function values(array $row, ?object $current, array $mappings, ?string $printOf, ?string $elements): array
    {
        $number = $row['part_num'];
        $ids = fn (string $column): array => $this->split($mappings[$column][$number] ?? null, SORT_STRING);
        $partElements = $this->split($elements, SORT_NUMERIC);

        return [
            'number' => $number,
            'name' => $row['name'],
            'rb_part_category_id' => (int) $row['part_cat_id'],
            'url' => $current->url ?? "https://rebrickable.com/parts/{$number}/",
            // Keep the stored element while Rebrickable still lists it for the part, else take the newest
            'element' => in_array($current?->element, $partElements, true) ? $current->element : (end($partElements) ?: null),
            'print_of' => $printOf,
            'is_local' => false,
            'bricklink' => $ids('bricklink'),
            'brickowl' => $ids('brickowl'),
            'lego' => $ids('lego'),
            'ldraw' => $ids('ldraw'),
            // The downloads don't map parts to Brickset, so keep any stored ids unless a mapping appears
            'brickset' => isset($mappings['brickset'][$number]) ? $ids('brickset') : $current?->brickset,
        ];
    }

    /**
     * The stored parts with the given numbers as plain rows keyed by number.
     *
     * @param array<int, string> $numbers
     * @return Collection<string, object>
     */
    protected function existing(array $numbers): Collection
    {
        return DB::table('rebrickable_parts')
            ->whereIn('number', $numbers)
            ->get(self::COMPARED_COLUMNS)
            ->each(function (object $part) {
                $part->rb_part_category_id = (int) $part->rb_part_category_id;
                $part->is_local = (bool) $part->is_local;
                foreach (self::JSON_COLUMNS as $column) {
                    $part->{$column} = is_null($part->{$column}) ? null : json_decode($part->{$column}, true);
                }
            })
            ->keyBy('number');
    }

    /**
     * Tab-joined external ids keyed by column then part.
     *
     * @return array<string, array<string, string>>
     */
    protected function mappings(): array
    {
        $mappings = [];
        foreach ($this->rebrickableCsv->rows(RebrickableCsvFile::PartMappings) as $row) {
            $column = self::MAPPED_COLUMNS[$row['ext_system']] ?? null;
            if (!is_null($column) && !is_null($row['ext_id'])) {
                $this->append($mappings[$column][$row['part_num']], $row['ext_id']);
            }
        }

        return $mappings;
    }

    /**
     * The part each print is of. The few prints with several parents take the lowest.
     *
     * @return array<string, string>
     */
    protected function printOf(): array
    {
        $printOf = [];
        foreach ($this->rebrickableCsv->rows(RebrickableCsvFile::PartRelationships) as $row) {
            if ($row['rel_type'] !== 'P') {
                continue;
            }
            $child = $row['child_part_num'];
            if (!isset($printOf[$child]) || strcmp($row['parent_part_num'], $printOf[$child]) < 0) {
                $printOf[$child] = $row['parent_part_num'];
            }
        }

        return $printOf;
    }

    /**
     * Tab-joined element ids keyed by part.
     *
     * @return array<string, string>
     */
    protected function elements(): array
    {
        $elements = [];
        foreach ($this->rebrickableCsv->rows(RebrickableCsvFile::Elements) as $row) {
            $this->append($elements[$row['part_num']], $row['element_id']);
        }

        return $elements;
    }

    protected function append(?string &$joined, string $id): void
    {
        $joined = is_null($joined) ? $id : "{$joined}\t{$id}";
    }

    /**
     * Unique ids from a tab-joined string, sorted the way the Rebrickable API returns them.
     *
     * @return array<int, string>
     */
    protected function split(?string $joined, int $sort): array
    {
        if (is_null($joined)) {
            return [];
        }

        $ids = array_values(array_unique(explode("\t", $joined)));
        sort($ids, $sort);

        return $ids;
    }

    protected function isUnchanged(object $current, array $values): bool
    {
        foreach ($values as $column => $value) {
            if ($current->{$column} !== $value) {
                return false;
            }
        }

        return true;
    }

    protected function externalIdsChanged(object $current, array $values): bool
    {
        foreach (self::EXTERNAL_ID_COLUMNS as $column) {
            // No ids is stored as either null or an empty list
            if (($current->{$column} ?? []) !== ($values[$column] ?? [])) {
                return true;
            }
        }

        return false;
    }
}
