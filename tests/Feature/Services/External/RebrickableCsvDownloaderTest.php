<?php

use App\Enums\RebrickableCsvFile;
use App\Services\External\RebrickableCsvDownloader;
use Illuminate\Support\Facades\Http;

const DOWNLOAD_URL = 'https://cdn.example.test/downloads/';

/**
 * Fakes every download as a small gzipped CSV, with any overrides by base file name.
 *
 * @param array<string, mixed> $overrides
 */
function fakeDownloads(array $overrides = []): void
{
    $responses = [];
    foreach (RebrickableCsvFile::cases() as $file) {
        // A fresh response per request, as a faked response's body can only be read once
        $responses[DOWNLOAD_URL . "{$file->value}.csv.gz"] = $overrides[$file->value]
            ?? fn () => Http::response(gzencode("id,name\r\n1,new {$file->value}\r\n"));
    }
    Http::preventStrayRequests();
    Http::fake($responses);
}

function downloader(): RebrickableCsvDownloader
{
    return new RebrickableCsvDownloader(baseUrl: DOWNLOAD_URL);
}

function csvDirectory(): string
{
    return config('ldraw.rebrickable_csv_path');
}

beforeEach(function () {
    fakeRebrickableCsv(['parts' => ['id,name', '1,old parts']]);
});

it('downloads and unzips every file, replacing the current ones', function () {
    file_put_contents(csvDirectory() . '/parts.csv.gz', gzencode("id,name\n1,stale gz\n"));
    fakeDownloads();

    expect(downloader()->download())->toBeTrue();

    foreach (RebrickableCsvFile::cases() as $file) {
        expect(file_get_contents(csvDirectory() . "/{$file->value}.csv"))->toBe("id,name\r\n1,new {$file->value}\r\n");
    }
    expect(csvDirectory() . '/parts.csv.gz')->not->toBeFile()
        ->and(csvDirectory() . '/.staging')->not->toBeDirectory();
    Http::assertSentCount(count(RebrickableCsvFile::cases()));
});

it('skips a download within 24 hours of the last one', function () {
    fakeDownloads();
    downloader()->download();

    $this->travel(23)->hours();

    expect(downloader()->download())->toBeFalse();
    Http::assertSentCount(count(RebrickableCsvFile::cases()));
});

it('downloads again once 24 hours have passed or when forced', function () {
    fakeDownloads();
    downloader()->download();

    expect(downloader()->download(force: true))->toBeTrue();
    $this->travel(25)->hours();
    expect(downloader()->download())->toBeTrue();
    Http::assertSentCount(count(RebrickableCsvFile::cases()) * 3);
});

it('keeps the current files when any download fails', function (Closure $overrides, string $message) {
    fakeDownloads($overrides());

    expect(fn () => downloader()->download())->toThrow(RuntimeException::class, $message);

    expect(file_get_contents(csvDirectory() . '/parts.csv'))->toBe("id,name\r\n1,old parts\r\n")
        ->and(csvDirectory() . '/themes.csv')->not->toBeFile()
        ->and(csvDirectory() . '/.staging')->not->toBeDirectory()
        ->and(downloader()->downloadedRecently())->toBeFalse();
})->with([
    'an HTTP error' => [fn () => ['themes' => Http::response('Payment Required', 402)], 'failed with HTTP 402'],
    'an invalid gzip' => [fn () => ['themes' => Http::response('not gzip')], 'not a valid gzip file'],
    'an empty mapping file' => [fn () => ['part_mappings' => Http::response(gzencode("part_num,ext_system,ext_id\n"))], 'part_mappings.csv has no rows'],
]);
