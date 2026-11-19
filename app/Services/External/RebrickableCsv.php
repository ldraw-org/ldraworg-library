<?php

namespace App\Services\External;

use App\Enums\RebrickableCsvFile;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\LazyCollection;
use RuntimeException;

/**
 * Streams Rebrickable's CSV downloads row by row so even the largest file
 * (inventory_parts, ~1.5M rows) never has to be held in memory.
 */
class RebrickableCsv
{
    protected string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = rtrim($directory ?? config('ldraw.rebrickable_csv_path'), '/');
    }

    /**
     * Rows keyed by the file's header. Values are the raw strings with empty values as null;
     * booleans are left as Rebrickable's "True"/"False".
     *
     * @return LazyCollection<int, array<string, ?string>>
     * @throws FileNotFoundException when neither the .csv nor the .csv.gz exists
     */
    public function rows(RebrickableCsvFile $file): LazyCollection
    {
        $path = $this->path($file);

        return LazyCollection::make(function () use ($path) {
            $handle = fopen($path, 'r');
            if ($handle === false) {
                throw new RuntimeException("Unable to open {$path}");
            }

            try {
                $header = $this->readLine($handle);
                if ($header === null) {
                    return;
                }

                $line = 1;
                while (($values = $this->readLine($handle)) !== null) {
                    $line++;
                    if ($values === [null]) {
                        continue;
                    }
                    yield $this->combine($header, $values, "line {$line} of {$path}");
                }
            } finally {
                fclose($handle);
            }
        });
    }

    /**
     * The inventory_parts rows of the given inventories, keyed by inventory id. The file is sorted by
     * inventory id, so each inventory is found by a binary search instead of reading the whole file.
     * A gzipped file can't seek, so it is read through instead.
     *
     * @param array<int, int|string> $inventoryIds
     * @return array<string, array<int, array<string, ?string>>>
     * @throws FileNotFoundException
     */
    public function inventoryParts(array $inventoryIds): array
    {
        $path = $this->path(RebrickableCsvFile::InventoryParts);
        $wanted = array_flip(array_map('strval', $inventoryIds));

        if (str_starts_with($path, 'compress.zlib://')) {
            return $this->rows(RebrickableCsvFile::InventoryParts)
                ->filter(fn (array $row) => isset($wanted[$row['inventory_id']]))
                ->groupBy('inventory_id')
                ->map(fn ($rows) => $rows->values()->all())
                ->all();
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException("Unable to open {$path}");
        }

        try {
            $header = $this->readLine($handle) ?? [];
            $dataStart = ftell($handle);
            $size = filesize($path);
            $found = [];

            foreach (array_keys($wanted) as $id) {
                $offset = $this->firstLineAtLeast($handle, (int) $id, $dataStart, $size);
                fseek($handle, $offset);
                while (($values = $this->readLine($handle)) !== null && (string) $values[0] === (string) $id) {
                    $found[$id][] = $this->combine($header, $values, "inventory {$id} of {$path}");
                }
            }

            return $found;
        } finally {
            fclose($handle);
        }
    }

    /**
     * The offset of the first line whose leading inventory id is at least $id, or the end of the file.
     *
     * @param resource $handle
     */
    protected function firstLineAtLeast($handle, int $id, int $low, int $high): int
    {
        $dataStart = $low;
        while ($low < $high) {
            $mid = intdiv($low + $high, 2);
            [$start, $lineId] = $this->lineFrom($handle, $mid, $dataStart);
            if (is_null($lineId) || $lineId >= $id) {
                $high = $mid;
            } else {
                $low = $start + 1;
            }
        }

        return $this->lineFrom($handle, $low, $dataStart)[0];
    }

    /**
     * The start and leading id of the first line beginning at or after $offset.
     *
     * @param resource $handle
     * @return array{0: int, 1: ?int} the id is null at the end of the file
     */
    protected function lineFrom($handle, int $offset, int $dataStart): array
    {
        if ($offset > $dataStart) {
            // Reading on from the byte before lands on the next line start, or stays put if $offset is one
            fseek($handle, $offset - 1);
            fgets($handle);
        } else {
            fseek($handle, $dataStart);
        }

        $start = ftell($handle);
        $line = fgets($handle);

        return [$start, $line === false ? null : (int) $line];
    }

    /**
     * @param array<int, string> $header
     * @param array<int, ?string> $values
     * @return array<string, ?string>
     */
    protected function combine(array $header, array $values, string $where): array
    {
        if (count($values) !== count($header)) {
            throw new RuntimeException("Malformed row at {$where}");
        }

        return array_combine($header, array_map(fn (?string $value) => $value === '' ? null : $value, $values));
    }

    /**
     * @throws FileNotFoundException
     */
    public function path(RebrickableCsvFile $file): string
    {
        $base = "{$this->directory}/{$file->value}.csv";

        if (is_file($base)) {
            return $base;
        }

        if (is_file("{$base}.gz")) {
            return "compress.zlib://{$base}.gz";
        }

        throw new FileNotFoundException("Rebrickable CSV file not found: {$base}[.gz]");
    }

    /**
     * Rebrickable escapes quotes by doubling them, never with a backslash, so escaping is disabled.
     *
     * @param resource $handle
     * @return ?array<int, ?string>
     */
    protected function readLine($handle): ?array
    {
        $values = fgetcsv($handle, escape: '');

        return $values === false ? null : $values;
    }
}
