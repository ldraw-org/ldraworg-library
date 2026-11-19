<?php

use App\Models\LdrawColour;
use App\Models\Omr\Set;
use App\Models\Omr\Theme;
use App\Models\RebrickablePart;
use App\Services\Omr\Set\ImportSetInventories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function inventorySet(string $number): Set
{
    return Set::create([
        'number' => $number,
        'name' => "Set {$number}",
        'year' => 2020,
        'theme_id' => 1,
        'rb_url' => "https://rebrickable.com/sets/{$number}/",
    ]);
}

/**
 * @return array<int, array{0: string, 1: ?int, 2: int, 3: bool}> part number, LDraw colour, quantity, spare
 */
function storedInventory(Set $set): array
{
    return DB::table('rebrickable_parts_sets')
        ->join('rebrickable_parts', 'rebrickable_parts.id', '=', 'rebrickable_parts_sets.rebrickable_part_id')
        ->where('set_id', $set->id)
        ->orderBy('rebrickable_parts.number')->orderBy('rebrickable_parts_sets.ldraw_colour_id')->orderBy('is_spare')
        ->get(['rebrickable_parts.number', 'rebrickable_parts_sets.ldraw_colour_id', 'quantity', 'is_spare'])
        ->map(fn (object $row) => [$row->number, $row->ldraw_colour_id, $row->quantity, (bool) $row->is_spare])
        ->all();
}

beforeEach(function () {
    Http::preventStrayRequests();
    Cache::flush();
    LdrawColour::query()->delete();
    LdrawColour::factory()->create(['code' => 0, 'rebrickable_id' => 0]);
    LdrawColour::factory()->create(['code' => 4, 'rebrickable_id' => 4]);
    Theme::create(['id' => 1, 'name' => 'Town']);
    foreach (['3001', '3002', '973', 'sheet1', 'sheet2'] as $number) {
        RebrickablePart::create([
            'number' => $number,
            'name' => "Part {$number}",
            'rb_part_category_id' => str_starts_with($number, 'sheet') ? 58 : 11,
        ]);
    }

    fakeRebrickableCsv([
        'inventories' => [
            'id,version,set_num',
            '1,1,6000-1',
            '2,2,6000-1',
            '3,1,fig-000001',
            '4,1,K100-1',
            '5,1,6001-1',
            '6,1,7000-1',
            '7,1,7001-1',
        ],
        'inventory_parts' => [
            'inventory_id,part_num,color_id,quantity,is_spare,img_url',
            '1,3001,4,99,False,',
            '2,3001,4,2,False,',
            '2,3001,4,1,True,',
            '2,3002,9999,1,False,',
            '2,unknown,4,1,False,',
            '3,973,0,1,False,',
            '3,3001,4,1,False,',
            '5,3002,0,4,False,',
            '6,sheet1,9999,1,False,',
            '7,sheet2,9999,1,False,',
        ],
        'inventory_minifigs' => [
            'inventory_id,fig_num,quantity',
            '2,fig-000001,2',
            '5,fig-000001,1',
        ],
        'inventory_sets' => [
            'inventory_id,set_num,quantity',
            '4,6001-1,2',
        ],
        'sets' => [
            'set_num,name,year,theme_id,num_parts,img_url',
            '7000-1,Sticker Set,1999,1,1,',
            '7001-1,Unknown Theme Set,1999,999,1,',
        ],
    ]);
});

it('builds inventories from the latest version with minifigs, sub-sets and spares', function () {
    $set = inventorySet('6000-1');
    $kit = inventorySet('K100-1');

    $result = app(ImportSetInventories::class)->handle();

    expect(storedInventory($set))->toBe([
        ['3001', 4, 4, false],
        ['3001', 4, 1, true],
        ['3002', null, 1, false],
        ['973', 0, 2, false],
    ])
        ->and(storedInventory($kit))->toBe([
            ['3001', 4, 2, false],
            ['3002', 0, 8, false],
            ['973', 0, 2, false],
        ])
        ->and($result['missing_parts'])->toBe(['unknown'])
        ->and($set->fresh()->refreshed_at)->not->toBeNull();
});

it('rewrites only sets whose inventory changed and leaves sets missing from the downloads alone', function () {
    $set = inventorySet('6000-1');
    $notInDownloads = inventorySet('9999-1');
    $existing = RebrickablePart::firstWhere('number', '3001');
    $notInDownloads->rebrickable_parts()->attach($existing->id, ['quantity' => 5, 'ldraw_colour_id' => 4]);

    $first = app(ImportSetInventories::class)->handle();
    $pivotIds = DB::table('rebrickable_parts_sets')->where('set_id', $set->id)->pluck('quantity', 'rebrickable_part_id');
    $second = app(ImportSetInventories::class)->handle();

    // 6000-1 and the added sticker set 7000-1
    expect($first['updated_sets'])->toBe(2)
        ->and($second['updated_sets'])->toBe(0)
        ->and($second['unchanged_sets'])->toBe(2)
        ->and(DB::table('rebrickable_parts_sets')->where('set_id', $set->id)->pluck('quantity', 'rebrickable_part_id'))->toEqual($pivotIds)
        ->and(storedInventory($notInDownloads))->toBe([['3001', 4, 5, false]])
        ->and($notInDownloads->fresh()->refreshed_at)->toBeNull();
});

it('adds the sets holding a sticker sheet when their theme is known', function () {
    $result = app(ImportSetInventories::class)->handle();

    $added = Set::firstWhere('number', '7000-1');
    expect($result['added_sets'])->toBe(1)
        ->and($added->only(['name', 'year', 'theme_id', 'rb_url']))->toEqual([
            'name' => 'Sticker Set',
            'year' => 1999,
            'theme_id' => 1,
            'rb_url' => 'https://rebrickable.com/sets/7000-1/',
        ])
        ->and(storedInventory($added))->toBe([['sheet1', null, 1, false]])
        ->and(Set::where('number', '7001-1')->exists())->toBeFalse();
});

it('imports a single set the same way, reading only the inventories it needs', function () {
    $set = inventorySet('6000-1');
    $kit = inventorySet('K100-1');
    $importer = app(ImportSetInventories::class);

    expect($importer->importSet($set))->toBeTrue()
        ->and($importer->importSet($kit))->toBeTrue()
        ->and(storedInventory($set))->toBe([
            ['3001', 4, 4, false],
            ['3001', 4, 1, true],
            ['3002', null, 1, false],
            ['973', 0, 2, false],
        ])
        ->and(storedInventory($kit))->toBe([
            ['3001', 4, 2, false],
            ['3002', 0, 8, false],
            ['973', 0, 2, false],
        ])
        ->and($set->fresh()->refreshed_at)->not->toBeNull()
        ->and(Set::where('number', '7000-1')->exists())->toBeFalse();
});

it('reports a single set the downloads have no inventory for', function () {
    $set = inventorySet('9999-1');

    expect(app(ImportSetInventories::class)->importSet($set))->toBeFalse()
        ->and($set->fresh()->refreshed_at)->toBeNull();
});
