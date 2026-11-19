<?php

use App\Models\RebrickablePart;
use App\Services\RebrickablePart\ImportRebrickableParts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
    fakeRebrickableCsv([
        'parts' => [
            'part_num,name,part_cat_id,part_material',
            '3001,Brick 2 x 4,11,Plastic',
            '3001pr0001,"Brick 2 x 4 with ""Logo"" Print",2,Plastic',
            '003238,Sticker Sheet for Set 375-2,58,Plastic',
        ],
        'part_mappings' => [
            'part_num,ext_system,ext_id,is_export_id,is_import_id',
            '3001,LE,72841,False,True',
            '3001,LE,3001,True,True',
            '3001,BL,3001,True,True',
            '3001,BO,771344,True,True',
            '3001,BS,ignored,True,True',
            '3001pr0001,LD,3001p01,True,True',
            '3001pr0001,LD,3001p01,True,True',
        ],
        'part_relationships' => [
            'rel_type,child_part_num,parent_part_num',
            'P,3001pr0001,3001b',
            'P,3001pr0001,3001',
            'M,3001a,3001',
        ],
        'elements' => [
            'element_id,part_num,color_id,design_id',
            '300126,3001,5,3001',
            '300121,3001,4,3001',
            '4111111,3001pr0001,15,',
        ],
    ]);
});

it('creates every part with its mappings, print_of and element', function () {
    $result = app(ImportRebrickableParts::class)->handle();

    $brick = RebrickablePart::firstWhere('number', '3001');
    $print = RebrickablePart::firstWhere('number', '3001pr0001');

    expect($result)->toMatchArray(['created' => 3, 'updated' => 0, 'retired_stickers' => 0, 'external_ids_changed' => []])
        ->and($brick->only(['name', 'rb_part_category_id', 'url', 'bricklink', 'brickowl', 'lego', 'ldraw', 'brickset', 'element', 'print_of', 'is_local']))
        ->toBe([
            'name' => 'Brick 2 x 4',
            'rb_part_category_id' => 11,
            'url' => 'https://rebrickable.com/parts/3001/',
            'bricklink' => ['3001'],
            'brickowl' => ['771344'],
            'lego' => ['3001', '72841'],
            'ldraw' => [],
            'brickset' => ['3001', '72841'],
            'element' => '300126',
            'print_of' => null,
            'is_local' => false,
        ])
        ->and($brick->refreshed_at)->not->toBeNull()
        ->and($print->name)->toBe('Brick 2 x 4 with "Logo" Print')
        ->and($print->ldraw)->toBe(['3001p01'])
        ->and($print->print_of)->toBe('3001')
        ->and($print->element)->toBe('4111111');
});

it('updates changed parts, keeps stored values the downloads lack, and skips unchanged parts', function () {
    app(ImportRebrickableParts::class)->handle();
    $brick = RebrickablePart::firstWhere('number', '3001');
    $brick->update([
        'name' => 'Old Name',
        'url' => 'https://rebrickable.com/parts/3001/brick-2-x-4/',
        'image_url' => 'https://cdn.rebrickable.com/media/parts/elements/300102.jpg',
        'brickset' => ['15589'],
        'element' => '300121',
    ]);
    $print = RebrickablePart::firstWhere('number', '3001pr0001');
    $print->update(['bricklink' => ['3001pb001']]);
    $sticker = RebrickablePart::firstWhere('number', '003238');
    $this->travel(5)->minutes();

    $result = app(ImportRebrickableParts::class)->handle();

    $brick->refresh();
    expect($result)->toMatchArray(['created' => 0, 'updated' => 2, 'external_ids_changed' => [$print->id]])
        ->and($brick->only(['name', 'url', 'image_url', 'brickset', 'element']))->toBe([
            'name' => 'Brick 2 x 4',
            'url' => 'https://rebrickable.com/parts/3001/brick-2-x-4/',
            'image_url' => 'https://cdn.rebrickable.com/media/parts/elements/300102.jpg',
            'brickset' => ['15589'],
            'element' => '300121',
        ])
        ->and($print->fresh()->bricklink)->toBe([])
        ->and($sticker->fresh()->updated_at)->toEqual($sticker->updated_at);
});

it('replaces a stored element Rebrickable no longer lists', function () {
    RebrickablePart::create(['number' => '3001', 'name' => 'Brick 2 x 4', 'element' => '999999']);

    app(ImportRebrickableParts::class)->handle();

    expect(RebrickablePart::firstWhere('number', '3001')->element)->toBe('300126');
});

it('keeps sticker sheets Rebrickable dropped as local sheets and leaves other dropped parts alone', function () {
    $droppedSheet = RebrickablePart::create(['number' => '003999', 'name' => 'Old Sheet', 'rb_part_category_id' => 58]);
    $droppedPart = RebrickablePart::create(['number' => '16175pr0001', 'name' => 'Old Print']);
    $localSheet = RebrickablePart::create(['number' => 'u-s123', 'name' => 'Local', 'rb_part_category_id' => 58, 'is_local' => true]);

    $result = app(ImportRebrickableParts::class)->handle();

    expect($result['retired_stickers'])->toBe(1)
        ->and($droppedSheet->fresh()->is_local)->toBeTrue()
        ->and($droppedPart->fresh()->is_local)->toBeFalse()
        ->and($localSheet->fresh()->is_local)->toBeTrue();
});
