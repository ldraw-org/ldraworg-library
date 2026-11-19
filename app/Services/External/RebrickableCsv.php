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
                    if (count($values) !== count($header)) {
                        throw new RuntimeException("Malformed row at line {$line} of {$path}");
                    }

                    yield array_combine(
                        $header,
                        array_map(fn (?string $value) => $value === '' ? null : $value, $values)
                    );
                }
            } finally {
                fclose($handle);
            }
        });
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
