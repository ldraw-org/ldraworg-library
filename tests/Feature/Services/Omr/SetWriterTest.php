<?php

use App\Models\LdrawColour;
use App\Models\Omr\Set;
use App\Models\Omr\Theme;
use App\Models\RebrickablePart;
use App\Services\Omr\Set\Writer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    LdrawColour::query()->delete();
    LdrawColour::factory()->create(['code' => 4, 'rebrickable_id' => 4]);
    Theme::create(['id' => 1, 'name' => 'Town']);
    RebrickablePart::create(['number' => '3001', 'name' => 'Brick 2 x 4']);
    fakeRebrickableCsv([
        'sets' => ['set_num,name,year,theme_id,num_parts,img_url', '6000-1,CSV Set,1990,1,3,'],
        'themes' => ['id,name,parent_id', '1,Town,'],
        'inventories' => ['id,version,set_num', '10,1,6000-1'],
        'inventory_minifigs' => ['inventory_id,fig_num,quantity'],
        'inventory_sets' => ['inventory_id,set_num,quantity'],
        'inventory_parts' => ['inventory_id,part_num,color_id,quantity,is_spare,img_url', '10,3001,4,3,False,'],
    ]);
});

it('adds a set from the CSV downloads without calling Rebrickable', function () {
    Http::preventStrayRequests();

    $set = app(Writer::class)->findFromSetNumber('6000-1', createIfMissing: true);

    expect($set->only(['number', 'name', 'year', 'theme_id', 'rb_url']))->toEqual([
        'number' => '6000-1',
        'name' => 'CSV Set',
        'year' => 1990,
        'theme_id' => 1,
        'rb_url' => 'https://rebrickable.com/sets/6000-1/',
    ])
        ->and($set->refreshed_at)->not->toBeNull()
        ->and($set->rebrickable_parts()->get()->map(fn (RebrickablePart $part) => [$part->number, $part->pivot->quantity, $part->pivot->ldraw_colour_id])->all())
        ->toBe([['3001', 3, 4]]);
});

it('looks up a set in the CSV downloads before the API', function () {
    Http::preventStrayRequests();

    expect(app(Writer::class)->setData('6000-1'))->toBe([
        'set_num' => '6000-1',
        'name' => 'CSV Set',
        'year' => 1990,
        'theme_id' => 1,
        'set_url' => 'https://rebrickable.com/sets/6000-1/',
    ]);
});

it('falls back to the API for a set the downloads do not have', function () {
    Http::fake([
        'rebrickable.com/api/v3/lego/sets/7000-1/parts/*' => Http::response([
            'results' => [[
                'part' => ['part_num' => '3001', 'name' => 'Brick', 'part_cat_id' => 11],
                'color' => ['id' => 4],
                'quantity' => 2,
                'is_spare' => false,
            ]],
            'next' => null,
        ]),
        'rebrickable.com/api/v3/lego/sets/7000-1/' => Http::response([
            'set_num' => '7000-1', 'name' => 'API Set', 'year' => 2026, 'theme_id' => 1,
            'set_url' => 'https://rebrickable.com/sets/7000-1/api-set/',
        ]),
    ]);

    $set = app(Writer::class)->findFromSetNumber('7000-1', createIfMissing: true);

    expect($set->name)->toBe('API Set')
        ->and($set->rb_url)->toBe('https://rebrickable.com/sets/7000-1/api-set/')
        ->and($set->rebrickable_parts()->first()->pivot->quantity)->toBe(2);
    Http::assertSentCount(2);
});

it('finds nothing for a set neither source has', function () {
    Http::fake(['rebrickable.com/*' => Http::response(['detail' => 'Not found.'], 404)]);

    expect(app(Writer::class)->findFromSetNumber('0000-1', createIfMissing: true))->toBeNull()
        ->and(Set::count())->toBe(0);
});
