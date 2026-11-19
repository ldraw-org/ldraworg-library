<?php

namespace App\Services\External;

use App\Enums\RebrickableCsvFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PharData;
use RuntimeException;
use SplFileInfo;
use Throwable;

/**
 * Downloads Rebrickable's archive of every CSV file and swaps the files in only once all have arrived intact,
 * so a failed download never leaves the imports reading partial or mixed data.
 *
 * Rebrickable allows the download at most once per day, so a download within 24 hours of the last one
 * is skipped unless forced.
 */
class RebrickableCsvDownloader
{
    protected const int MIN_SECONDS_BETWEEN_DOWNLOADS = 86400;

    protected const int CONNECT_TIMEOUT_SECONDS = 30;

    protected const int REQUEST_TIMEOUT_SECONDS = 900;

    /** Records when the files were last downloaded, since copied-in files have no download time of their own */
    protected const string MARKER = '.downloaded_at';

    protected string $directory;

    protected string $url;

    public function __construct(?string $directory = null, ?string $url = null)
    {
        $this->directory = rtrim($directory ?? config('ldraw.rebrickable_csv_path'), '/');
        $this->url = $url ?? config('ldraw.rebrickable_csv_url');
    }

    /**
     * @return bool false when skipped because the files were downloaded within the last 24 hours
     * @throws RuntimeException when the download fails or lacks a file, leaving the current files in place
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
            $files = $this->extract($this->fetch($staging), "{$staging}/extracted");

            foreach (RebrickableCsvFile::cases() as $file) {
                rename($files[$file->value], "{$this->directory}/{$file->value}.csv");
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

    /**
     * @return string the downloaded archive
     */
    protected function fetch(string $staging): string
    {
        // Phar caches an archive's contents by path for the life of the process, so a long-running queue
        // worker would reread an earlier download if the name were reused
        $archive = "{$staging}/all_downloads-" . uniqid() . '.tar.gz';

        $response = Http::connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
            ->timeout(self::REQUEST_TIMEOUT_SECONDS)
            ->sink($archive)
            ->get($this->url);

        if (!$response->successful()) {
            throw new RuntimeException("Downloading the Rebrickable CSVs failed with HTTP {$response->status()}");
        }

        // An error page served with a success status would otherwise be passed on as an archive
        if (file_get_contents($archive, length: 2) !== "\x1f\x8b") {
            throw new RuntimeException('The Rebrickable download is not a gzip archive');
        }

        return $archive;
    }

    /**
     * Extracts the archive and finds every CSV in it, wherever it sits, unzipping any that are gzipped since
     * finding a set's inventory needs a file it can seek in.
     *
     * @return array<string, string> the extracted CSV keyed by base file name
     */
    protected function extract(string $archive, string $to): array
    {
        try {
            $tar = new PharData($archive);
            $tar->extractTo($to);
        } catch (Throwable $e) {
            throw new RuntimeException("The Rebrickable download could not be extracted: {$e->getMessage()}", previous: $e);
        } finally {
            // Drops the archive from Phar's in-process cache as well as from disk
            unset($tar);
            try {
                PharData::unlinkArchive($archive);
            } catch (Throwable) {
                // A corrupt archive was never cached, so deleting the file is enough
                File::delete($archive);
            }
        }

        $extracted = collect(File::allFiles($to, hidden: true))
            ->keyBy(fn (SplFileInfo $file) => $file->getFilename());

        $files = [];
        foreach (RebrickableCsvFile::cases() as $file) {
            $csv = $extracted->get("{$file->value}.csv")?->getPathname();
            $gz = $extracted->get("{$file->value}.csv.gz")?->getPathname();

            if (is_null($csv) && !is_null($gz)) {
                $csv = "{$to}/{$file->value}.csv";
                if (@copy("compress.zlib://{$gz}", $csv) === false) {
                    throw new RuntimeException("Rebrickable {$file->value}.csv.gz could not be unzipped");
                }
            }

            if (is_null($csv)) {
                throw new RuntimeException("The Rebrickable download has no {$file->value}.csv");
            }

            $this->assertHasRows($file, $csv);
            $files[$file->value] = $csv;
        }

        return $files;
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
