<?php

use App\Enums\RebrickableCsvFile;
use App\Services\External\RebrickableCsvDownloader;
use Illuminate\Support\Facades\Http;

const DOWNLOAD_URL = 'https://cdn.example.test/downloads/all_downloads.tar.gz';

/**
 * Every CSV under all_downloads/, with overrides by archive path; a null override leaves the file out.
 *
 * @param array<string, ?string> $overrides
 * @return array<string, string>
 */
function archiveFiles(array $overrides = []): array
{
    $files = [];
    foreach (RebrickableCsvFile::cases() as $file) {
        $files["all_downloads/{$file->value}.csv"] = "id,name\r\n1,new {$file->value}\r\n";
    }

    return array_filter([...$files, ...$overrides], fn (?string $contents) => !is_null($contents));
}

function fakeArchive(string|Closure $body): void
{
    Http::preventStrayRequests();
    // A fresh response per request, as a faked response's body can only be read once
    Http::fake([DOWNLOAD_URL => $body instanceof Closure ? $body : fn () => Http::response($body)]);
}

function downloader(): RebrickableCsvDownloader
{
    return new RebrickableCsvDownloader(url: DOWNLOAD_URL);
}

function csvDirectory(): string
{
    return config('ldraw.rebrickable_csv_path');
}

beforeEach(function () {
    fakeRebrickableCsv(['parts' => ['id,name', '1,old parts']]);
});

it('downloads the archive and extracts every file, replacing the current ones', function () {
    file_put_contents(csvDirectory() . '/parts.csv.gz', gzencode("id,name\n1,stale gz\n"));
    fakeArchive(rebrickableArchive(archiveFiles()));

    expect(downloader()->download())->toBeTrue();

    foreach (RebrickableCsvFile::cases() as $file) {
        expect(file_get_contents(csvDirectory() . "/{$file->value}.csv"))->toBe("id,name\r\n1,new {$file->value}\r\n");
    }
    expect(csvDirectory() . '/parts.csv.gz')->not->toBeFile()
        ->and(csvDirectory() . '/.staging')->not->toBeDirectory();
    Http::assertSentCount(1);
});

it('finds files at the top of the archive and unzips gzipped ones', function () {
    $files = [];
    foreach (archiveFiles() as $path => $contents) {
        $files[basename($path)] = $contents;
    }
    $files['parts.csv.gz'] = gzencode("id,name\r\n1,gzipped parts\r\n");
    unset($files['parts.csv']);
    fakeArchive(rebrickableArchive($files));

    downloader()->download();

    expect(file_get_contents(csvDirectory() . '/parts.csv'))->toBe("id,name\r\n1,gzipped parts\r\n")
        ->and(file_get_contents(csvDirectory() . '/themes.csv'))->toBe("id,name\r\n1,new themes\r\n");
});

it('skips a download within 24 hours of the last one', function () {
    fakeArchive(rebrickableArchive(archiveFiles()));
    downloader()->download();

    $this->travel(23)->hours();

    expect(downloader()->download())->toBeFalse();
    Http::assertSentCount(1);
});

it('downloads again once 24 hours have passed or when forced', function () {
    fakeArchive(rebrickableArchive(archiveFiles()));
    downloader()->download();

    expect(downloader()->download(force: true))->toBeTrue();
    $this->travel(25)->hours();
    expect(downloader()->download())->toBeTrue();
    Http::assertSentCount(3);
});

it('reads each new download rather than an earlier one cached by the same process', function () {
    $week = 1;
    fakeArchive(function () use (&$week) {
        return Http::response(rebrickableArchive(archiveFiles(['all_downloads/themes.csv' => "id,name\r\n1,week {$week}\r\n"])));
    });

    downloader()->download();
    $week = 2;
    downloader()->download(force: true);

    expect(file_get_contents(csvDirectory() . '/themes.csv'))->toBe("id,name\r\n1,week 2\r\n");
});

it('keeps the current files when the download fails', function (Closure $body, string $message) {
    fakeArchive($body());

    expect(fn () => downloader()->download())->toThrow(RuntimeException::class, $message);

    expect(file_get_contents(csvDirectory() . '/parts.csv'))->toBe("id,name\r\n1,old parts\r\n")
        ->and(csvDirectory() . '/themes.csv')->not->toBeFile()
        ->and(csvDirectory() . '/.staging')->not->toBeDirectory()
        ->and(downloader()->downloadedRecently())->toBeFalse();
})->with([
    'an HTTP error' => [fn () => fn () => Http::response('Payment Required', 402), 'failed with HTTP 402'],
    'an error page' => [fn () => '<html>Error, try later</html>', 'not a gzip archive'],
    'a corrupt archive' => [fn () => gzencode('not a tar file'), 'could not be extracted'],
    'a missing file' => [fn () => rebrickableArchive(archiveFiles(['all_downloads/themes.csv' => null])), 'has no themes.csv'],
    'an empty mapping file' => [fn () => rebrickableArchive(archiveFiles(['all_downloads/part_mappings.csv' => "part_num,ext_system,ext_id\r\n"])), 'part_mappings.csv has no rows'],
]);
