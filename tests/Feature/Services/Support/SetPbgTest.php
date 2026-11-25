<?php

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Models\LdrawColour;
use App\Models\Omr\Set;
use App\Models\Omr\Theme;
use App\Models\Part\Part;
use App\Models\RebrickablePart;
use App\Services\Support\SetPbg;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function pbgSet(array $attributes = []): Set
{
    return Set::create([
        'number' => '1234-1',
        'name' => 'Test Set',
        'year' => 2026,
        'rb_url' => 'https://rebrickable.com/sets/1234-1/',
        'theme_id' => Theme::create(['name' => 'Test Theme'])->id,
        'refreshed_at' => now(),
        ...$attributes,
    ]);
}

function pbgRebrickablePart(string $number, array $attributes = []): RebrickablePart
{
    return RebrickablePart::create(['number' => $number, 'name' => "Part {$number}", ...$attributes]);
}

function pbgLdrawPart(string $number, ?RebrickablePart $rebrickablePart = null, array $attributes = []): Part
{
    return Part::factory()->create([
        'filename' => "parts/{$number}.dat",
        'type' => PartType::Part,
        'category' => PartCategory::Brick,
        'rebrickable_part_id' => $rebrickablePart?->id,
        ...$attributes,
    ]);
}

function pbgAttach(Set $set, RebrickablePart $part, ?int $colour = 4, int $quantity = 1, bool $isSpare = false): void
{
    $set->rebrickable_parts()->attach($part->id, [
        'ldraw_colour_id' => $colour,
        'quantity' => $quantity,
        'is_spare' => $isSpare,
    ]);
}

function pbgItems(string $pbg): array
{
    return array_slice(explode("\n", $pbg), 8);
}

beforeEach(function () {
    Cache::flush();
    Http::preventStrayRequests();
    LdrawColour::query()->delete();
    LdrawColour::factory()->create(['code' => 4, 'rebrickable_id' => 4]);
    // No CSV downloads, so a set that needs syncing goes to the API
    fakeRebrickableCsv([]);
});

it('syncs a never synced set from the CSV downloads without calling Rebrickable', function () {
    $set = pbgSet(['refreshed_at' => null]);
    pbgLdrawPart('3001', pbgRebrickablePart('3001'));
    fakeRebrickableCsv([
        'inventories' => ['id,version,set_num', '10,1,1234-1'],
        'inventory_minifigs' => ['inventory_id,fig_num,quantity'],
        'inventory_sets' => ['inventory_id,set_num,quantity'],
        'inventory_parts' => ['inventory_id,part_num,color_id,quantity,is_spare,img_url', '10,3001,4,6,False,'],
    ]);

    expect(pbgItems(app(SetPbg::class)->pbg('1234-1')))->toBe(['3001.dat: [color=4][count=6]']);
    expect($set->fresh()->refreshed_at->isToday())->toBeTrue();
});

it('builds a pbg from the stored parts of an old set without calling Rebrickable', function () {
    $set = pbgSet(['refreshed_at' => now()->subYears(5)]);
    $brick = pbgRebrickablePart('3001');
    pbgLdrawPart('3001', $brick);
    pbgAttach($set, $brick, 4, 3);
    pbgAttach($set, $brick, 4, 1, true);
    pbgAttach($set, $brick, 15, 2);

    $generator = app(SetPbg::class);
    $pbg = $generator->pbg('1234-1');

    expect(explode("\n", $pbg))->toBe([
        '[options]',
        'kind=basic',
        'caption=Set 1234-1 - Test Set',
        'description=Parts in set 1234-1',
        'sortDesc=false',
        'sortOn=description',
        'sortCaseInSens=true',
        '<items>',
        '3001.dat: [color=4][count=4]',
        '3001.dat: [color=15][count=2]',
    ]);
    expect($generator->messages->isEmpty())->toBeTrue();
});

it('prefers the linked part sharing the Rebrickable number, then the first linked part', function () {
    $set = pbgSet();
    $exact = pbgRebrickablePart('3062b');
    pbgLdrawPart('30068', $exact);
    pbgLdrawPart('3062b', $exact);
    $renumbered = pbgRebrickablePart('22670');
    pbgLdrawPart('72514p01', $renumbered);
    pbgLdrawPart('72514', $renumbered);
    pbgAttach($set, $exact);
    pbgAttach($set, $renumbered);

    expect(pbgItems(app(SetPbg::class)->pbg('1234-1')))->toEqualCanonicalizing([
        '3062b.dat: [color=4][count=1]',
        '72514.dat: [color=4][count=1]',
    ]);
});

it('falls back to an unlinked LDraw part with the same number', function () {
    $set = pbgSet();
    pbgLdrawPart('3002');
    pbgAttach($set, pbgRebrickablePart('3002'));

    expect(pbgItems(app(SetPbg::class)->pbg('1234-1')))->toBe(['3002.dat: [color=4][count=1]']);
});

it('merges parts that resolve to the same alias target', function () {
    $set = pbgSet();
    $target = pbgLdrawPart('3003');
    $alias = pbgLdrawPart('3003a', null, ['type_qualifier' => PartTypeQualifier::Alias]);
    $alias->subparts()->attach($target->id);
    pbgAttach($set, pbgRebrickablePart('3003'), 4, 2);
    pbgAttach($set, pbgRebrickablePart('3003a'), 4, 3);

    expect(pbgItems(app(SetPbg::class)->pbg('1234-1')))->toBe(['3003.dat: [color=4][count=5]']);
});

it('substitutes the unpatterned part for a print without an LDraw part', function () {
    $set = pbgSet();
    pbgLdrawPart('3004', pbgRebrickablePart('3004'));
    pbgAttach($set, pbgRebrickablePart('3004pr0001', ['print_of' => '3004']));

    $generator = app(SetPbg::class);

    expect(pbgItems($generator->pbg('1234-1')))->toBe(['3004.dat: [color=4][count=1]']);
    expect($generator->messages->get('unpatterned'))->toBe([
        '<a class="underline decoration-dotted hover:decoration-solid" href="https://rebrickable.com/parts/3004pr0001/">3004pr0001 (Part 3004pr0001)</a> as 3004',
    ]);
});

it('expands a sticker sheet into its stickers using the main colour', function () {
    $set = pbgSet();
    $sheet = pbgRebrickablePart('12345stk01', ['rb_part_category_id' => 58]);
    pbgLdrawPart('12345a', $sheet, ['category' => PartCategory::Sticker]);
    pbgLdrawPart('12345b', $sheet, ['category' => PartCategory::Sticker]);
    pbgAttach($set, $sheet, null, 2);

    $generator = app(SetPbg::class);

    expect(pbgItems($generator->pbg('1234-1')))->toEqualCanonicalizing([
        '12345a.dat: [color=16][count=2]',
        '12345b.dat: [color=16][count=2]',
    ]);
    expect($generator->messages->isEmpty())->toBeTrue();
});

it('reports missing parts with escaped details and unmapped colours', function () {
    $set = pbgSet();
    pbgAttach($set, pbgRebrickablePart('9999', ['name' => '<b>Unknown</b>']));
    pbgLdrawPart('3005', $mapped = pbgRebrickablePart('3005'));
    pbgAttach($set, $mapped, null);

    $generator = app(SetPbg::class);

    expect(pbgItems($generator->pbg('1234-1')))->toBe(['3005.dat: [color=16][count=1]']);
    expect($generator->messages->get('missing')[0])
        ->toContain('https://rebrickable.com/parts/9999/')
        ->toContain('&lt;b&gt;Unknown&lt;/b&gt;');
    expect($generator->messages->get('errors'))->toBe(['LDraw color not found for part 3005, using 16']);
});

it('syncs a set that has never been synced before generating', function () {
    $set = pbgSet(['refreshed_at' => null]);
    pbgAttach($set, pbgRebrickablePart('3001'), 0);
    pbgLdrawPart('3001', RebrickablePart::firstWhere('number', '3001'));
    Http::fake([
        'rebrickable.com/*' => Http::response([
            'results' => [[
                'part' => ['part_num' => '3001', 'name' => 'Brick', 'part_cat_id' => 11],
                'color' => ['id' => 4],
                'quantity' => 5,
                'is_spare' => false,
                'element_id' => null,
            ]],
            'next' => null,
        ]),
    ]);

    expect(pbgItems(app(SetPbg::class)->pbg('1234-1')))->toBe(['3001.dat: [color=4][count=5]']);
    expect($set->fresh()->refreshed_at->isToday())->toBeTrue();
});

it('returns an error instead of a pbg when an unsynced set cannot be synced', function () {
    $set = pbgSet(['refreshed_at' => null]);
    pbgAttach($set, pbgRebrickablePart('3001'), 0);
    Http::fake(['rebrickable.com/*' => Http::response('Server Error', 500)]);

    $generator = app(SetPbg::class);

    expect($generator->pbg('1234-1'))->toBeFalse();
    expect($generator->messages->get('errors'))->toBe(['Unable to load the set parts from Rebrickable']);
    expect($set->fresh()->refreshed_at)->toBeNull();
});

it('reports a set that does not exist', function () {
    $generator = app(SetPbg::class);

    expect($generator->pbg('0000-1'))->toBeFalse();
    expect($generator->messages->get('errors'))->toBe(['Set Not Found']);
});
