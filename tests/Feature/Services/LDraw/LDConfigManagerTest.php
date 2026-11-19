<?php

use App\Models\LdrawColour;
use App\Services\LDraw\Managers\LDConfigManager;
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

    app(LDConfigManager::class)->importColours();

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
        ->and(app(LDConfigManager::class)->ldrawColourCodesToRebrickable())->toMatchArray([4 => 4, 256 => 0]);
});
