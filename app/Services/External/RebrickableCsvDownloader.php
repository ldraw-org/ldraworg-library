<?php

namespace App\Services\External;

use App\Enums\RebrickableCsvFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Downloads every Rebrickable CSV file and swaps them in only once all have arrived intact, so a failed
 * download never leaves the imports reading partial or mixed data.
 *
 * Rebrickable allows each download at most once per day, so a download within 24 hours of the last one
 * is skipped unless forced.
 */
class RebrickableCsvDownloader
{
    protected const int MIN_SECONDS_BETWEEN_DOWNLOADS = 86400;

    protected const int CONNECT_TIMEOUT_SECONDS = 30;

    protected const int REQUEST_TIMEOUT_SECONDS = 600;

    /** Records when the files were last downloaded, since copied-in files have no download time of their own */
    protected const string MARKER = '.downloaded_at';

    protected string $directory;

    protected string $baseUrl;

    public function __construct(?string $directory = null, ?string $baseUrl = null)
    {
        $this->directory = rtrim($directory ?? config('ldraw.rebrickable_csv_path'), '/');
        $this->baseUrl = rtrim($baseUrl ?? config('ldraw.rebrickable_csv_url'), '/') . '/';
    }

    /**
     * @return bool false when skipped because the files were downloaded within the last 24 hours
     * @throws RuntimeException when any file fails, leaving the current files in place
     */
    public function download(bool $force = false): bool
    {
        if (!$force && $this->downloadedRecently()) {
            return false;
        }

        $staging = "{$this->directory}/.staging";
        File::deleteDirectory($staging);
        File::ensureDirectoryExists($staging);

        try {
            foreach (RebrickableCsvFile::cases() as $file) {
                $this->fetch($file, $staging);
            }

            foreach (RebrickableCsvFile::cases() as $file) {
                rename("{$staging}/{$file->value}.csv", "{$this->directory}/{$file->value}.csv");
                // An older gzipped copy would otherwise linger beside the new file
                File::delete("{$this->directory}/{$file->value}.csv.gz");
            }

            file_put_contents("{$this->directory}/" . self::MARKER, (string) now()->getTimestamp());
        } finally {
            File::deleteDirectory($staging);
        }

        return true;
    }

    public function downloadedRecently(): bool
    {
        $marker = "{$this->directory}/" . self::MARKER;

        return is_file($marker)
            && now()->getTimestamp() - (int) file_get_contents($marker) < self::MIN_SECONDS_BETWEEN_DOWNLOADS;
    }

    protected function fetch(RebrickableCsvFile $file, string $staging): void
    {
        $gz = "{$staging}/{$file->value}.csv.gz";
        $csv = "{$staging}/{$file->value}.csv";

        $response = Http::connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
            ->timeout(self::REQUEST_TIMEOUT_SECONDS)
            ->sink($gz)
            ->get("{$this->baseUrl}{$file->value}.csv.gz");

        if (!$response->successful()) {
            throw new RuntimeException("Downloading Rebrickable {$file->value}.csv.gz failed with HTTP {$response->status()}");
        }

        // Unzipped, since finding a set's inventory needs a file it can seek in. The zlib wrapper passes
        // anything that isn't gzip through untouched, such as an error page, so the magic bytes are checked.
        if (file_get_contents($gz, length: 2) !== "\x1f\x8b" || @copy("compress.zlib://{$gz}", $csv) === false) {
            throw new RuntimeException("Rebrickable {$file->value}.csv.gz is not a valid gzip file");
        }
        unlink($gz);

        $this->assertHasRows($file, $csv);
    }

    /**
     * An empty mapping file would otherwise wipe every part's external ids.
     */
    protected function assertHasRows(RebrickableCsvFile $file, string $csv): void
    {
        $handle = fopen($csv, 'r');
        $header = fgets($handle);
        $firstRow = fgets($handle);
        fclose($handle);

        if ($header === false || !str_contains($header, ',') || $firstRow === false || trim($firstRow) === '') {
            throw new RuntimeException("Rebrickable {$file->value}.csv has no rows");
        }
    }
}
