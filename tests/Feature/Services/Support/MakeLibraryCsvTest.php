<?php

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Services\Support\MakeLibraryCsv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake();
    Storage::fake('library-media');
});

/**
 * @return array<string, array<string, string>> rows keyed by part_number
 */
function libraryCsvRows(): array
{
    app(MakeLibraryCsv::class)->handle();

    $reader = Reader::fromString(Storage::get('library/library.csv'));
    $reader->setHeaderOffset(0);

    return collect(iterator_to_array($reader->getRecords(), false))->keyBy('part_number')->all();
}

function libraryCsvPart(string $number, array $attributes = []): Part
{
    return Part::factory()->unofficial()->create([
        'filename' => "parts/{$number}.dat",
        'type' => PartType::Part,
        'category' => PartCategory::Brick,
        ...$attributes,
    ]);
}

it('writes a row with the image url and image date for a part with an image', function () {
    $part = libraryCsvPart('3001', ['description' => 'Brick 2 x 4']);
    $media = $part->addMedia(UploadedFile::fake()->image('3001.png'))->toMediaCollection('image');

    $row = libraryCsvRows()['3001.dat'];

    expect($row)->toBe([
        'part_number' => '3001.dat',
        'part_description' => 'Brick 2 x 4',
        'part_url' => route('part.download', ['library' => 'unofficial', 'filename' => 'parts/3001.dat']),
        'image_url' => $media->getUrl(),
        'image_last_modified' => $media->created_at->format('Y-m-d'),
        'category' => PartCategory::Brick->value,
    ]);
});

it('uses the fallback image url and part date for a part without an image', function () {
    $part = libraryCsvPart('3002');

    $row = libraryCsvRows()['3002.dat'];

    expect($row['image_url'])->toBe($part->getFallbackMediaUrl('image'))
        ->and($row['image_last_modified'])->toBe($part->created_at->format('Y-m-d'));
});

it('excludes parts that have an official counterpart or a moved/obsolete description', function () {
    libraryCsvPart('3003');
    libraryCsvPart('3004', ['description' => '~Moved to 3003']);
    libraryCsvPart('3005', ['description' => '|Alias']);
    $fixed = libraryCsvPart('3006');
    Part::factory()->official()->create([
        'filename' => 'parts/3006.dat',
        'type' => PartType::Part,
        'category' => PartCategory::Brick,
        'unofficial_part_id' => $fixed->id,
    ]);

    $rows = libraryCsvRows();

    expect(array_keys($rows))->toEqualCanonicalizing(['3003.dat', '3006.dat'])
        ->and($rows['3006.dat']['part_url'])->toContain('/official/');
});
