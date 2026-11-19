<?php

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Models\RebrickablePart;
use App\Services\RebrickablePart\Writer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('keeps the stored element when none is provided', function () {
    RebrickablePart::create(['number' => '3001', 'name' => 'Brick', 'element' => '300121']);

    $part = app(Writer::class)->updateOrCreateFromArray(['part_num' => '3001', 'name' => 'Brick 2 x 4', 'part_cat_id' => 11, 'element' => null]);

    expect($part->fresh()->element)->toBe('300121');
    expect($part->fresh()->name)->toBe('Brick 2 x 4');
});

it('updates the stored element when one is provided', function () {
    RebrickablePart::create(['number' => '3001', 'name' => 'Brick', 'element' => '300121']);

    $part = app(Writer::class)->updateOrCreateFromArray(['part_num' => '3001', 'name' => 'Brick', 'part_cat_id' => 11, 'element' => '300126']);

    expect($part->fresh()->element)->toBe('300126');
});

it('stores print_of and keeps it when none is provided', function () {
    $writer = app(Writer::class);

    $part = $writer->updateOrCreateFromArray(['part_num' => '3001pr0001', 'name' => 'Printed Brick', 'part_cat_id' => 11, 'print_of' => '3001']);
    expect($part->fresh()->print_of)->toBe('3001');

    $writer->updateOrCreateFromArray(['part_num' => '3001pr0001', 'name' => 'Printed Brick', 'part_cat_id' => 11]);
    expect($part->fresh()->print_of)->toBe('3001');
});

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
