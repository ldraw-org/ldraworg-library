<?php

use App\Models\LdrawColour;
use App\Services\LDConfig\ColourImporter;
use App\Services\LDConfig\ColourLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake();
    Http::preventStrayRequests();
});

it('maps LDConfig colours to Rebrickable colours from the CSV downloads', function () {
    Storage::put('library/official/LDConfig.ldr', implode("\n", [
        '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080',
        '0 !COLOUR Red CODE 4 VALUE #B40000 EDGE #333333',
        '0 !COLOUR Rubber_Black CODE 256 VALUE #212121 EDGE #595959 RUBBER',
        '0 !COLOUR Trans_Reddish_Lilac CODE 284 VALUE #E0D0E5 EDGE #B8A0C0 ALPHA 128',
        '0 !COLOUR Unmapped CODE 999 VALUE #FFFFFF EDGE #000000',
    ]));
    fakeRebrickableCsv([
        'colors' => [
            'id,name,rgb,is_trans,num_parts,num_sets,y1,y2',
            '-1,[Unknown],0033B2,False,17,2,2000,2000',
            '0,Black,05131D,False,1,1,1957,2027',
            '4,Red,C91A09,False,1,1,1957,2027',
            '236,Trans-Light Purple,96388E,True,1,1,2000,2027',
            '1094,Trans-Reddish Lilac,E0D0E5,True,1,1,2000,2027',
        ],
        'color_mappings' => [
            'id,ext_system,ext_id,ext_descr,is_export_id,is_import_id',
            '-1,LD,999,"[""Unknown""]",True,True',
            '0,BL,11,"[""Black""]",True,True',
            '0,LD,256,"[""Rubber_Black""]",False,True',
            '0,LD,0,"[""Black""]",True,True',
            '4,BL,5,"[""Red""]",True,True',
            '4,LD,4,"[""Red""]",True,True',
            '1094,LD,284,"[""Trans_Reddish_Lilac""]",True,True',
            '236,LD,284,"[""Trans_Light_Purple""]",False,True',
        ],
    ]);

    app(ColourImporter::class)->import();

    $mapped = LdrawColour::whereIn('code', [0, 4, 256, 284, 999])
        ->orderBy('code')
        ->get()
        ->mapWithKeys(fn (LdrawColour $c) => [$c->code => [$c->rebrickable_id, $c->rebrickable_name]])
        ->all();

    expect($mapped)->toBe([
        0 => [0, 'Black'],
        4 => [4, 'Red'],
        256 => [0, 'Black'],
        284 => [236, 'Trans-Light Purple'],
        999 => [null, null],
    ])
        ->and(LdrawColour::firstWhere('code', 256)->rubber)->toBeTrue()
        ->and(app(ColourLookup::class)->codesToRebrickable())->toMatchArray([4 => 4, 256 => 0]);
});

it('does nothing without an LDConfig', function () {
    $red = LdrawColour::factory()->create(['code' => 4, 'name' => 'Red', 'value' => '#B40000', 'rebrickable_id' => 4]);
    fakeRebrickableCsv([]);

    app(ColourImporter::class)->import();

    expect($red->fresh()->only(['value', 'rebrickable_id']))->toBe(['value' => '#B40000', 'rebrickable_id' => 4]);
});

it('keeps stored Rebrickable mappings and leaves new colours unmapped without the CSV downloads', function () {
    $red = LdrawColour::factory()->create(['code' => 4, 'name' => 'Red', 'value' => '#B40000', 'rebrickable_id' => 4, 'rebrickable_name' => 'Red']);
    Storage::put('library/official/LDConfig.ldr', implode("\n", [
        '0 !COLOUR Red CODE 4 VALUE #C91A09 EDGE #333333',
        '0 !COLOUR Brand_New CODE 998 VALUE #FFFFFF EDGE #000000',
    ]));
    fakeRebrickableCsv(['colors' => ['id,name,rgb,is_trans,num_parts,num_sets,y1,y2']]);

    app(ColourImporter::class)->import();

    expect($red->fresh()->only(['value', 'rebrickable_id', 'rebrickable_name']))->toBe(['value' => '#C91A09', 'rebrickable_id' => 4, 'rebrickable_name' => 'Red'])
        ->and(LdrawColour::firstWhere('code', 998)->only(['rebrickable_id', 'rebrickable_name']))->toBe(['rebrickable_id' => null, 'rebrickable_name' => null]);
});

it('refreshes the cached colour options', function () {
    LdrawColour::factory()->create(['code' => 4, 'name' => 'Red', 'value' => '#B40000']);
    app(ColourLookup::class)->options();
    Storage::put('library/official/LDConfig.ldr', implode("\n", [
        '0 !COLOUR Red CODE 4 VALUE #B40000 EDGE #333333',
        '0 !COLOUR Brand_New CODE 998 VALUE #FFFFFF EDGE #000000',
    ]));
    fakeRebrickableCsv([]);

    app(ColourImporter::class)->import();

    expect(app(ColourLookup::class)->options())->toHaveKeys([4, 998]);
});
