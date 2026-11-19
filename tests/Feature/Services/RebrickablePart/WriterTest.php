<?php

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Models\RebrickablePart;
use App\Services\RebrickablePart\Writer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function linkablePart(string $number, ?RebrickablePart $linked = null): Part
{
    return Part::factory()->unofficial()->createQuietly([
        'filename' => "parts/{$number}.dat",
        'type' => PartType::Part,
        'category' => PartCategory::Brick,
        'rebrickable_part_id' => $linked?->id,
    ]);
}

it('links a part to the Rebrickable part mapped to the same number first', function () {
    Http::preventStrayRequests();
    RebrickablePart::create(['number' => '2557c03', 'name' => 'Other', 'ldraw' => ['2557c01']]);
    $same = RebrickablePart::create(['number' => '2557c01', 'name' => 'Same', 'ldraw' => ['2557c01']]);
    $part = linkablePart('2557c01');

    expect(app(Writer::class)->findOrCreateFromPart($part)?->id)->toBe($same->id)
        ->and($part->fresh()->rebrickable_part_id)->toBe($same->id);
});

it('links to the lowest mapped part number, then to an unmapped part with the same number', function () {
    Http::preventStrayRequests();
    RebrickablePart::create(['number' => '2335pr0022', 'name' => 'Higher', 'ldraw' => ['2335ps2']]);
    $lowest = RebrickablePart::create(['number' => '11055pr0005', 'name' => 'Lowest', 'ldraw' => ['2335ps2']]);
    RebrickablePart::create(['number' => '2335ps2', 'name' => 'Same number, not mapped', 'ldraw' => []]);
    $unmapped = RebrickablePart::create(['number' => '11153', 'name' => 'Unmapped']);

    expect(app(Writer::class)->findOrCreateFromPart(linkablePart('2335ps2'))?->id)->toBe($lowest->id)
        ->and(app(Writer::class)->findOrCreateFromPart(linkablePart('11153'))?->id)->toBe($unmapped->id);
});

it('ignores local parts and keeps the existing link when nothing matches', function () {
    Http::preventStrayRequests();
    RebrickablePart::create(['number' => '99999', 'name' => 'Local', 'is_local' => true, 'ldraw' => ['99999']]);
    $old = RebrickablePart::create(['number' => '98138pr0008', 'name' => 'Old link']);
    $part = linkablePart('99999', $old);

    expect(app(Writer::class)->findOrCreateFromPart($part))->toBeNull()
        ->and($part->fresh()->rebrickable_part_id)->toBe($old->id);
});
