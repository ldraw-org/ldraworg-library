<?php

use App\Services\Parser\ParsedPartCollection;
use App\Services\Check\PartChecker;
use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)->in('Feature');

expect()->extend('toHaveCheckResult', function (bool $expected, string $check, ?string $filename = null) {
    $file = new ParsedPartCollection($this->value);
    $check_namespace = '\\App\\Services\\Check\\PartChecks\\';
    $check = $check_namespace . $check;
    $check = new $check();
    $result = PartChecker::singleCheck($file, $check, $filename);
    expect($result->isEmpty())->toBe($expected);
});

/**
 * Points the Rebrickable CSV reader at a fresh directory holding the given files.
 *
 * @param array<string, array<int, string>> $files lines (header first) keyed by base file name, e.g. 'themes'
 */
function fakeRebrickableCsv(array $files): void
{
    $dir = storage_path('framework/testing/rebrickable-csv');
    File::deleteDirectory($dir);
    File::ensureDirectoryExists($dir);

    foreach ($files as $name => $lines) {
        file_put_contents("{$dir}/{$name}.csv", implode("\r\n", $lines) . "\r\n");
    }

    config(['ldraw.rebrickable_csv_path' => $dir]);
}

/**
 * The bytes of a .tar.gz like Rebrickable's all_downloads archive.
 *
 * @param array<string, string> $files contents keyed by path inside the archive
 */
function rebrickableArchive(array $files): string
{
    $dir = storage_path('framework/testing/rebrickable-archive-' . uniqid());
    File::ensureDirectoryExists($dir);

    try {
        $tar = new PharData("{$dir}/all_downloads.tar");
        foreach ($files as $path => $contents) {
            $tar->addFromString($path, $contents);
        }
        $tar->compress(Phar::GZ);

        return file_get_contents("{$dir}/all_downloads.tar.gz");
    } finally {
        unset($tar);
        File::deleteDirectory($dir);
    }
}
