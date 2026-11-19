<?php

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Models\RebrickablePart;
use App\Services\Part\RelinkRebrickableParts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
});

function relinkPart(string $number, ?RebrickablePart $linked = null, bool $official = true, PartCategory $category = PartCategory::Minifig): Part
{
    $factory = Part::factory();
    $factory = $official ? $factory->official() : $factory->unofficial();

    return $factory->createQuietly([
        'filename' => "parts/{$number}.dat",
        'description' => "Part {$number}",
        'type' => PartType::Part,
        'category' => $category,
        'rebrickable_part_id' => $linked?->id,
    ]);
}

function keywordsOf(Part $part): array
{
    return $part->fresh()->keywords->pluck('keyword')->sort()->values()->all();
}

it('relinks an official part to a corrected mapping, updates its keywords and marks it a minor edit', function () {
    $old = RebrickablePart::create(['number' => '11778pr0002', 'name' => 'Wrong', 'bricklink' => ['11778pb02'], 'ldraw' => []]);
    $new = RebrickablePart::create(['number' => '11777pr0002', 'name' => 'Right', 'bricklink' => ['11777pb02'], 'ldraw' => ['11777p02']]);
    $part = relinkPart('11777p02', $old);
    $part->setKeywords(['Rebrickable 11778pr0002', 'Bricklink 11778pb02']);

    $count = app(RelinkRebrickableParts::class)->handle([]);

    $part->refresh();
    expect($count)->toBe(1)
        ->and($part->rebrickable_part_id)->toBe($new->id)
        ->and(keywordsOf($part))->toBe(['Bricklink 11777pb02', 'Rebrickable 11777pr0002'])
        ->and($part->has_minor_edit)->toBeTrue()
        ->and($part->header)->toContain('Rebrickable 11777pr0002');
});

it('links an unlinked official part', function () {
    $rb = RebrickablePart::create(['number' => '3729', 'name' => 'Brick', 'ldraw' => ['3729']]);
    $part = relinkPart('3729');

    app(RelinkRebrickableParts::class)->handle([]);

    $part->refresh();
    expect($part->rebrickable_part_id)->toBe($rb->id)
        ->and($part->has_minor_edit)->toBeFalse();
});

it('re-syncs parts whose external ids changed, marking a minor edit only when keywords change', function () {
    $changed = RebrickablePart::create(['number' => '3001pr0001', 'name' => 'Changed', 'bricklink' => ['3001pb002'], 'ldraw' => ['3001p01']]);
    $same = RebrickablePart::create(['number' => '3002pr0001', 'name' => 'Same', 'bricklink' => ['3002pb001'], 'ldraw' => ['3002p01']]);
    $changedPart = relinkPart('3001p01', $changed);
    $changedPart->setKeywords(['Rebrickable 3001pr0001', 'Bricklink 3001pb001']);
    $samePart = relinkPart('3002p01', $same);
    $samePart->setKeywords(['Rebrickable 3002pr0001', 'Bricklink 3002pb001']);

    $count = app(RelinkRebrickableParts::class)->handle([$changed->id, $same->id]);

    expect($count)->toBe(2)
        ->and(keywordsOf($changedPart))->toBe(['Bricklink 3001pb002', 'Rebrickable 3001pr0001'])
        ->and($changedPart->fresh()->has_minor_edit)->toBeTrue()
        ->and($samePart->fresh()->has_minor_edit)->toBeFalse();
});

it('leaves correctly linked, unmatched and unlinked sticker parts alone', function () {
    $rb = RebrickablePart::create(['number' => '3003', 'name' => 'Brick', 'bricklink' => ['3003b'], 'ldraw' => ['3003']]);
    $correct = relinkPart('3003', $rb);
    $correct->setKeywords(['Bricklink 3003b']);
    $old = RebrickablePart::create(['number' => '98138pr0008', 'name' => 'Old link']);
    $unmatched = relinkPart('10238', $old);
    $sticker = relinkPart('973s01', category: PartCategory::Sticker);
    $updatedAt = $correct->fresh()->updated_at;

    $count = app(RelinkRebrickableParts::class)->handle([]);

    expect($count)->toBe(0)
        ->and($correct->fresh()->has_minor_edit)->toBeFalse()
        ->and($correct->fresh()->updated_at)->toEqual($updatedAt)
        ->and($unmatched->fresh()->rebrickable_part_id)->toBe($old->id)
        ->and($sticker->fresh()->rebrickable_part_id)->toBeNull()
        ->and(RebrickablePart::where('is_local', true)->exists())->toBeFalse();
});

it('re-syncs unofficial parts without marking them minor edits', function () {
    $rb = RebrickablePart::create(['number' => '4000pr0001', 'name' => 'Print', 'bricklink' => ['4000pb01'], 'ldraw' => ['4000p01']]);
    $part = relinkPart('4000p01', official: false);

    app(RelinkRebrickableParts::class)->handle([]);

    $part->refresh();
    expect($part->rebrickable_part_id)->toBe($rb->id)
        ->and(keywordsOf($part))->toBe(['Bricklink 4000pb01', 'Rebrickable 4000pr0001'])
        ->and($part->has_minor_edit)->toBeFalse();
});
