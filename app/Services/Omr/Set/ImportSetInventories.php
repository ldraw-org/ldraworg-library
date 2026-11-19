<?php

namespace App\Services\Omr\Set;

use App\Enums\RebrickableCsvFile;
use App\Models\Omr\Set;
use App\Models\Omr\Theme;
use App\Models\RebrickablePart;
use App\Services\External\RebrickableCsv;
use App\Services\LDraw\Managers\LDConfigManager;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds set inventories from the Rebrickable CSV downloads the way the API's set parts list does: the set's
 * latest inventory version plus the parts of its minifigs and sub-sets, spares included.
 *
 * Covers every stored set and adds the sets holding a sticker sheet, which sticker matching relies on.
 * Only sets whose inventory changed are rewritten.
 */
class ImportSetInventories
{
    protected const int SET_CHUNK = 200;

    protected const int INSERT_CHUNK = 1000;

    protected const int STICKER_CATEGORY = 58;

    /** Sub-sets and minifigs nest a few levels at most; this guards against a cycle */
    protected const int MAX_DEPTH = 5;

    /** @var array<string, string> latest inventory id keyed by set or minifig number */
    protected array $latest = [];

    /** @var array<string, array<int, array{0: string, 1: int}>> minifigs and sub-sets with their quantities, keyed by inventory id */
    protected array $children = [];

    /** @var array<string, string> newline-joined "part\tcolour\tquantity\tspare" rows keyed by inventory id */
    protected array $inventoryParts = [];

    public function __construct(
        protected RebrickableCsv $rebrickableCsv,
        protected LDConfigManager $configManager,
    ) {
    }

    /**
     * @return array{added_sets: int, updated_sets: int, unchanged_sets: int, missing_parts: array<int, string>}
     *     missing_parts holds the Rebrickable part numbers that aren't imported, so were left out
     */
    public function handle(): array
    {
        $this->latest = $this->latestInventories();
        $this->children = $this->children();
        $stickerSets = $this->readInventoryParts();

        $result = ['added_sets' => $this->addSets($stickerSets), 'updated_sets' => 0, 'unchanged_sets' => 0, 'missing_parts' => []];
        $partIds = DB::table('rebrickable_parts')->pluck('id', 'number')->all();
        $colours = $this->configManager->rebrickableColoursToLdrawCodes();
        $missing = [];

        Set::withoutEagerLoads()
            ->whereIn('number', array_keys($this->latest))
            ->select(['id', 'number'])
            ->chunkById(self::SET_CHUNK, function (Collection $sets) use ($partIds, $colours, &$result, &$missing) {
                $stored = $this->storedPivots($sets->modelKeys());
                $changed = [];

                foreach ($sets as $set) {
                    $pivots = $this->pivots($this->expand($this->latest[$set->number]), $partIds, $colours, $missing);
                    if ($pivots === ($stored[$set->id] ?? [])) {
                        $result['unchanged_sets']++;
                    } else {
                        $changed[$set->id] = $pivots;
                    }
                }

                $this->writePivots($changed);
                $result['updated_sets'] += count($changed);
                DB::table('sets')->whereIn('id', $sets->modelKeys())->update(['refreshed_at' => now()]);
            });

        $result['missing_parts'] = array_keys($missing);

        return $result;
    }

    /**
     * Builds one set's inventory, reading only the inventories it needs, so a set missing from the database
     * can be added during a request without the Rebrickable API.
     *
     * @return bool false when the downloads have no inventory for the set
     * @throws FileNotFoundException when the downloads are missing
     */
    public function importSet(Set $set): bool
    {
        $this->latest = $this->latestInventories();
        if (!isset($this->latest[$set->number])) {
            return false;
        }

        $this->children = $this->children();
        $needed = $this->inventoryTree($this->latest[$set->number]);
        $this->inventoryParts = array_map(
            fn (array $rows) => implode("\n", array_map(fn (array $row) => $this->line($row), $rows)),
            $this->rebrickableCsv->inventoryParts($needed)
        );

        $quantities = $this->expand($this->latest[$set->number]);
        $numbers = array_unique(array_map(fn (string $key) => strstr($key, "\t", true), array_keys($quantities)));
        $partIds = DB::table('rebrickable_parts')->whereIn('number', $numbers)->pluck('id', 'number')->all();
        $missing = [];
        $pivots = $this->pivots($quantities, $partIds, $this->configManager->rebrickableColoursToLdrawCodes(), $missing);

        $this->writePivots([$set->id => $pivots]);
        $set->refreshed_at = now();
        $set->save();

        return true;
    }

    /**
     * The inventory with its minifig and sub-set inventories, as deep as expand() goes.
     *
     * @return array<int, string>
     */
    protected function inventoryTree(string $inventory, int $depth = 0): array
    {
        $tree = [$inventory];
        if ($depth < self::MAX_DEPTH) {
            foreach ($this->children[$inventory] ?? [] as [$number]) {
                if (isset($this->latest[$number])) {
                    $tree = [...$tree, ...$this->inventoryTree($this->latest[$number], $depth + 1)];
                }
            }
        }

        return array_values(array_unique($tree));
    }

    /**
     * @param array<string, ?string> $row an inventory_parts row
     */
    protected function line(array $row): string
    {
        return "{$row['part_num']}\t{$row['color_id']}\t{$row['quantity']}\t" . ($row['is_spare'] === 'True' ? 1 : 0);
    }

    /**
     * @return array<string, string>
     */
    protected function latestInventories(): array
    {
        $latest = [];
        $versions = [];
        foreach ($this->rebrickableCsv->rows(RebrickableCsvFile::Inventories) as $row) {
            $version = (int) $row['version'];
            if ($version > ($versions[$row['set_num']] ?? 0)) {
                $versions[$row['set_num']] = $version;
                $latest[$row['set_num']] = $row['id'];
            }
        }

        return $latest;
    }

    /**
     * @return array<string, array<int, array{0: string, 1: int}>>
     */
    protected function children(): array
    {
        $current = array_flip($this->latest);
        $children = [];
        foreach ([[RebrickableCsvFile::InventoryMinifigs, 'fig_num'], [RebrickableCsvFile::InventorySets, 'set_num']] as [$file, $column]) {
            foreach ($this->rebrickableCsv->rows($file) as $row) {
                if (isset($current[$row['inventory_id']])) {
                    $children[$row['inventory_id']][] = [$row[$column], (int) $row['quantity']];
                }
            }
        }

        return $children;
    }

    /**
     * Keeps the rows of every latest inventory and returns the sets whose own inventory holds a sticker sheet.
     *
     * @return array<string, true>
     */
    protected function readInventoryParts(): array
    {
        $current = array_flip($this->latest);
        $stickers = array_flip(RebrickablePart::where('rb_part_category_id', self::STICKER_CATEGORY)
            ->where('is_local', false)
            ->pluck('number')
            ->all());
        $stickerSets = [];

        foreach ($this->rebrickableCsv->rows(RebrickableCsvFile::InventoryParts) as $row) {
            $inventory = $row['inventory_id'];
            if (!isset($current[$inventory])) {
                continue;
            }

            $line = $this->line($row);
            $this->inventoryParts[$inventory] = isset($this->inventoryParts[$inventory])
                ? "{$this->inventoryParts[$inventory]}\n{$line}"
                : $line;

            $number = $current[$inventory];
            if (isset($stickers[$row['part_num']]) && !str_starts_with($number, 'fig-')) {
                $stickerSets[$number] = true;
            }
        }

        return $stickerSets;
    }

    /**
     * Creates the given sets that aren't stored yet from sets.csv, skipping any whose theme is unknown.
     *
     * @param array<string, true> $numbers
     */
    protected function addSets(array $numbers): int
    {
        $missing = array_diff_key($numbers, array_flip(Set::pluck('number')->all()));
        if ($missing === []) {
            return 0;
        }

        $themeIds = array_flip(Theme::pluck('id')->all());
        $now = now();

        $sets = $this->rebrickableCsv->rows(RebrickableCsvFile::Sets)
            ->filter(fn (array $row) => isset($missing[$row['set_num']]) && isset($themeIds[(int) $row['theme_id']]))
            ->map(fn (array $row) => [
                'number' => $row['set_num'],
                'name' => $row['name'],
                'year' => (int) $row['year'],
                'theme_id' => (int) $row['theme_id'],
                'rb_url' => "https://rebrickable.com/sets/{$row['set_num']}/",
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values();

        $sets->chunk(self::INSERT_CHUNK)->each(fn ($chunk) => Set::insert($chunk->all()));

        return $sets->count();
    }

    /**
     * Quantities keyed by "part\tcolour\tspare" for the inventory, its minifigs and its sub-sets.
     *
     * @param array<string, int> $quantities
     * @return array<string, int>
     */
    protected function expand(string $inventory, int $multiplier = 1, int $depth = 0, array $quantities = []): array
    {
        if (isset($this->inventoryParts[$inventory])) {
            foreach (explode("\n", $this->inventoryParts[$inventory]) as $line) {
                [$part, $colour, $quantity, $spare] = explode("\t", $line);
                $key = "{$part}\t{$colour}\t{$spare}";
                $quantities[$key] = ($quantities[$key] ?? 0) + (int) $quantity * $multiplier;
            }
        }

        if ($depth < self::MAX_DEPTH) {
            foreach ($this->children[$inventory] ?? [] as [$number, $quantity]) {
                if (isset($this->latest[$number])) {
                    $quantities = $this->expand($this->latest[$number], $multiplier * $quantity, $depth + 1, $quantities);
                }
            }
        }

        return $quantities;
    }

    /**
     * Pivot values keyed by "part id\tcolour code\tspare", sorted so they compare with the stored pivots.
     *
     * @param array<string, int> $quantities
     * @param array<string, int> $partIds
     * @param array<int, int> $colours
     * @param array<string, true> $missing
     * @return array<string, int>
     */
    protected function pivots(array $quantities, array $partIds, array $colours, array &$missing): array
    {
        $pivots = [];
        foreach ($quantities as $key => $quantity) {
            [$part, $colour, $spare] = explode("\t", $key);
            if (!isset($partIds[$part])) {
                $missing[$part] = true;
                continue;
            }
            $pivotKey = $partIds[$part] . "\t" . ($colours[(int) $colour] ?? '') . "\t{$spare}";
            $pivots[$pivotKey] = ($pivots[$pivotKey] ?? 0) + $quantity;
        }
        ksort($pivots);

        return $pivots;
    }

    /**
     * Stored pivot values per set, in the same form as pivots().
     *
     * @param array<int, int> $setIds
     * @return array<int, array<string, int>>
     */
    protected function storedPivots(array $setIds): array
    {
        $stored = [];
        DB::table('rebrickable_parts_sets')
            ->whereIn('set_id', $setIds)
            ->get(['set_id', 'rebrickable_part_id', 'ldraw_colour_id', 'quantity', 'is_spare'])
            ->each(function (object $row) use (&$stored) {
                $key = "{$row->rebrickable_part_id}\t{$row->ldraw_colour_id}\t" . (int) $row->is_spare;
                $stored[$row->set_id][$key] = ($stored[$row->set_id][$key] ?? 0) + (int) $row->quantity;
            });

        foreach ($stored as &$pivots) {
            ksort($pivots);
        }

        return $stored;
    }

    /**
     * @param array<int, array<string, int>> $changed pivots keyed by set id
     */
    protected function writePivots(array $changed): void
    {
        if ($changed === []) {
            return;
        }

        $rows = [];
        foreach ($changed as $setId => $pivots) {
            foreach ($pivots as $key => $quantity) {
                [$partId, $colour, $spare] = explode("\t", $key);
                $rows[] = [
                    'set_id' => $setId,
                    'rebrickable_part_id' => (int) $partId,
                    'ldraw_colour_id' => $colour === '' ? null : (int) $colour,
                    'quantity' => $quantity,
                    'is_spare' => $spare === '1',
                ];
            }
        }

        DB::transaction(function () use ($changed, $rows) {
            DB::table('rebrickable_parts_sets')->whereIn('set_id', array_keys($changed))->delete();
            foreach (array_chunk($rows, self::INSERT_CHUNK) as $chunk) {
                DB::table('rebrickable_parts_sets')->insert($chunk);
            }
        });
    }
}
