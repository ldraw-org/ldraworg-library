<?php

use App\Models\LdrawColour;
use App\Models\Omr\Set;
use App\Models\Omr\Theme;
use App\Models\RebrickablePart;
use App\Services\External\RebrickableRequestFailed;
use App\Services\Omr\Set\SyncSetParts;
use App\Services\Omr\Set\Writer;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function setPartRow(string $partNumber, int $colorId, int $quantity = 1, bool $isSpare = false): array
{
    return [
        'part' => ['part_num' => $partNumber, 'name' => "Part {$partNumber}", 'part_cat_id' => 11],
        'color' => ['id' => $colorId],
        'quantity' => $quantity,
        'is_spare' => $isSpare,
        'element_id' => null,
    ];
}

function setWithExistingPart(array $attributes = []): Set
{
    $theme = Theme::create(['name' => 'Test Theme']);
    $set = Set::create([
        'number' => '1234-1',
        'name' => 'Test Set',
        'year' => 2026,
        'rb_url' => 'https://rebrickable.com/sets/1234-1/',
        'theme_id' => $theme->id,
        ...$attributes,
    ]);
    $part = RebrickablePart::create(['number' => 'existing', 'name' => 'Existing Part']);
    $set->rebrickable_parts()->attach($part->id, ['quantity' => 2, 'ldraw_colour_id' => 4]);

    return $set;
}

function fakeFailureOnSecondPage(): void
{
    Http::fake([
        'rebrickable.com/*' => Http::sequence()
            ->push(['results' => [setPartRow('3001', 4)], 'next' => 'https://rebrickable.com/api/v3/lego/page2'])
            ->whenEmpty(Http::response('Server Error', 500)),
    ]);
}

beforeEach(function () {
    Cache::flush();
    LdrawColour::query()->delete();
    LdrawColour::factory()->create(['code' => 4, 'rebrickable_id' => 4]);
});

it('replaces set parts and stores null for colours without an LDraw mapping', function () {
    $set = setWithExistingPart();
    Http::fake([
        'rebrickable.com/*' => Http::response([
            'results' => [setPartRow('3001', 4, 3), setPartRow('3002', -1), setPartRow('3003', 9999)],
            'next' => null,
        ]),
    ]);

    app(SyncSetParts::class)->handle($set);

    $colours = $set->rebrickable_parts()->get()
        ->mapWithKeys(fn (RebrickablePart $part) => [$part->number => $part->pivot->ldraw_colour_id]);

    expect($colours->all())->toEqual(['3001' => 4, '3002' => null, '3003' => null]);
});

it('writes each part once and inserts pivot rows in bulk', function () {
    $set = setWithExistingPart();
    Http::fake([
        'rebrickable.com/*' => Http::response([
            'results' => [
                [...setPartRow('3024', 4), 'element_id' => '302404'],
                [...setPartRow('3024', 9999), 'element_id' => '302426'],
                setPartRow('3024', 4, 2, true),
                setPartRow('3001', 4),
            ],
            'next' => null,
        ]),
    ]);

    $queries = collect();
    DB::listen(fn (QueryExecuted $query) => $queries->push($query->sql));

    app(SyncSetParts::class)->handle($set);

    expect($queries->filter(fn (string $sql) => str_starts_with($sql, 'insert into `rebrickable_parts_sets`')))->toHaveCount(1);
    expect($queries->filter(fn (string $sql) => str_starts_with($sql, 'insert into `rebrickable_parts` ')))->toHaveCount(2);
    expect($set->rebrickable_parts()->count())->toBe(4);
});

it('creates parts newer than the CSV downloads without external ids and leaves imported parts alone', function () {
    $set = setWithExistingPart();
    $imported = RebrickablePart::create(['number' => '3001', 'name' => 'Imported', 'bricklink' => ['3001'], 'element' => '300121']);
    Http::fake([
        'rebrickable.com/*' => Http::response([
            'results' => [
                [...setPartRow('3001', 4), 'part' => ['part_num' => '3001', 'name' => 'API Name', 'part_cat_id' => 11, 'external_ids' => ['BrickLink' => ['api']]]],
                [...setPartRow('99999', 4), 'part' => ['part_num' => '99999', 'name' => 'New Part', 'part_cat_id' => 14, 'part_url' => 'https://rebrickable.com/parts/99999/new-part/', 'external_ids' => ['BrickLink' => ['api'], 'Brickset' => ['api']]]],
            ],
            'next' => null,
        ]),
    ]);

    app(SyncSetParts::class)->handle($set);

    $new = RebrickablePart::firstWhere('number', '99999');
    expect($imported->fresh()->only(['name', 'bricklink', 'element']))->toBe(['name' => 'Imported', 'bricklink' => ['3001'], 'element' => '300121'])
        ->and($new->only(['name', 'rb_part_category_id', 'url', 'bricklink', 'brickset']))->toBe([
            'name' => 'New Part',
            'rb_part_category_id' => 14,
            'url' => 'https://rebrickable.com/parts/99999/new-part/',
            'bricklink' => null,
            'brickset' => null,
        ]);
});

it('stores regular and spare quantities separately', function () {
    $set = setWithExistingPart();
    Http::fake([
        'rebrickable.com/*' => Http::response([
            'results' => [setPartRow('3024', 4, 3), setPartRow('3024', 4, 2, true)],
            'next' => null,
        ]),
    ]);

    app(SyncSetParts::class)->handle($set);

    $quantities = $set->rebrickable_parts()->get()
        ->mapWithKeys(fn (RebrickablePart $part) => [(int) $part->pivot->is_spare => $part->pivot->quantity]);

    expect($quantities->all())->toEqual([0 => 3, 1 => 2]);
});

it('keeps existing parts and caches nothing when a page fails', function () {
    $set = setWithExistingPart();
    fakeFailureOnSecondPage();

    expect(fn () => app(SyncSetParts::class)->handle($set))->toThrow(RebrickableRequestFailed::class);

    expect($set->rebrickable_parts()->pluck('number')->all())->toBe(['existing']);
    expect(Cache::has('rebrickable_set_parts_1234-1'))->toBeFalse();
});

function setDataFor(Set $set): array
{
    return [
        'set_num' => $set->number,
        'name' => $set->name,
        'year' => $set->year,
        'theme_id' => $set->theme_id,
        'set_url' => $set->rb_url,
    ];
}

it('does not mark a set as refreshed when the sync fails', function () {
    $set = setWithExistingPart();
    fakeFailureOnSecondPage();

    app(Writer::class)->updateOrCreateSetFromArray(setDataFor($set));

    expect($set->fresh()->refreshed_at)->toBeNull();
    expect($set->rebrickable_parts()->pluck('number')->all())->toBe(['existing']);
});

it('leaves a synced set to the CSV import, however old its sync', function () {
    Http::preventStrayRequests();
    $set = setWithExistingPart(['refreshed_at' => now()->subYear()]);

    app(Writer::class)->updateOrCreateSetFromArray(setDataFor($set));

    expect($set->rebrickable_parts()->pluck('number')->all())->toBe(['existing']);
});
