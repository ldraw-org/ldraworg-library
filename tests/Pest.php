<?php

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Services\Parser\ParsedPartCollection;
use App\Services\Check\PartChecker;
use Illuminate\Http\UploadedFile;
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
    $result = app(PartChecker::class)->runSingle(
        "App\\Services\\Check\\PartChecks\\{$check}",
        new ParsedPartCollection($this->value),
        $filename,
    );

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

/**
 * An OMR model file for set 6399-1 (by default) using one official part (3001.dat) and one unknown part (99999.dat).
 */
function omrModelFile(string $filename = '6399-1 - Airport Shuttle.mpd', string $author = 'Model Maker [ModelMaker]'): UploadedFile
{
    $text = implode("\r\n", [
        "0 FILE {$filename}",
        '0 Airport Shuttle',
        "0 Name: {$filename}",
        "0 Author: {$author}",
        '1 16 0 0 0 1 0 0 0 1 0 0 0 1 cabin.ldr',
        '0 FILE cabin.ldr',
        '1 4 0 0 0 1 0 0 0 1 0 0 0 1 3001.dat',
        '1 4 0 0 0 1 0 0 0 1 0 0 0 1 99999.dat',
    ]) . "\r\n";

    return UploadedFile::fake()->createWithContent($filename, $text);
}

/**
 * An official torso shortcut template (76382.dat) using a 973 torso and an arm (3818.dat) with one official pattern,
 * plus the official torso 973p01.dat to make a shortcut for.
 *
 * @return array{template: Part, torso: Part, arm: Part, armPattern: Part}
 */
function torsoShortcutParts(): array
{
    $official = fn (string $filename, string $description, PartCategory $category): Part => Part::factory()->official()->createQuietly([
        'filename' => $filename,
        'description' => $description,
        'type' => PartType::Part,
        'category' => $category,
    ]);

    $torso = $official('parts/973p01.dat', 'Minifig Torso with Logo Pattern', PartCategory::MinifigTorso);
    $blankTorso = $official('parts/973.dat', 'Minifig Torso', PartCategory::MinifigTorso);
    $arm = $official('parts/3818.dat', 'Minifig Arm Right', PartCategory::MinifigArm);
    $armPattern = $official('parts/3818p01.dat', 'Minifig Arm Right with Stripe', PartCategory::MinifigArm);
    $armPattern->forceFill(['base_part_id' => $arm->id, 'is_pattern' => true])->saveQuietly();

    $template = $official('parts/76382.dat', 'Minifig Torso Assembly', PartCategory::MinifigUpper);
    $template->body->forceFill(['body' => implode("\n", [
        '1 16 0 0 0 1 0 0 0 1 0 0 0 1 973.dat',
        '1 16 -15 8 0 1 0 0 0 1 0 0 0 1 3818.dat',
    ])])->save();
    $template->subparts()->attach([$blankTorso->id, $arm->id]);

    return compact('template', 'torso', 'arm', 'armPattern');
}

/**
 * A 32x32 RGBA PNG saved without compression, so lossless optimization always shrinks it.
 */
function uncompressedTexmapPng(): string
{
    $image = imagecreatetruecolor(32, 32);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    for ($y = 0; $y < 32; $y++) {
        for ($x = 0; $x < 32; $x++) {
            imagesetpixel($image, $x, $y, imagecolorallocatealpha($image, $x * 8, $y * 8, 128, ($x + $y) % 128));
        }
    }
    ob_start();
    imagepng($image, null, 0);

    return ob_get_clean();
}

/**
 * @return list<array{red: int, green: int, blue: int, alpha: int}>
 */
function texmapPixels(string $png): array
{
    $image = imagecreatefromstring($png);
    $pixels = [];
    for ($y = 0; $y < imagesy($image); $y++) {
        for ($x = 0; $x < imagesx($image); $x++) {
            $pixels[] = imagecolorsforindex($image, imagecolorat($image, $x, $y));
        }
    }

    return $pixels;
}
