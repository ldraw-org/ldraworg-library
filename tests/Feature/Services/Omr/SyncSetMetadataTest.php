<?php

use App\Models\Omr\Set;
use App\Models\Omr\Theme;
use App\Services\Omr\Set\SyncSetMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function metadataSet(string $number, string $name, int $year, int $themeId): Set
{
    return Set::create([
        'number' => $number,
        'name' => $name,
        'year' => $year,
        'theme_id' => $themeId,
        'rb_url' => "https://rebrickable.com/sets/{$number}/slug/",
    ]);
}

it('updates the name, year and theme of existing sets from the CSV downloads', function () {
    Http::preventStrayRequests();
    Theme::create(['id' => 50, 'name' => 'Town']);
    Theme::create(['id' => 52, 'name' => 'City']);

    $renamed = metadataSet('10281-1', 'Bonsai', 2021, 50);
    $moved = metadataSet('6000-1', 'Same Name', 1990, 50);
    $unchanged = metadataSet('6001-1', 'Unchanged', 1991, 50);
    $unknownTheme = metadataSet('6002-1', 'Unknown Theme', 1992, 50);
    $unchangedUpdatedAt = $unchanged->updated_at;

    fakeRebrickableCsv([
        'sets' => [
            'set_num,name,year,theme_id,num_parts,img_url',
            '10281-1,Bonsai Tree,2021,50,878,https://cdn.rebrickable.com/media/sets/10281-1.jpg',
            '6000-1,Same Name,1991,52,10,',
            '6001-1,Unchanged,1991,50,10,',
            '6002-1,Unknown Theme,1992,999,10,',
            '9999-1,Not In The OMR,2000,50,10,',
        ],
    ]);

    $this->travel(5)->minutes();
    $updated = app(SyncSetMetadata::class)->handle();

    expect($updated)->toBe(2)
        ->and($renamed->fresh()->name)->toBe('Bonsai Tree')
        ->and($renamed->fresh()->rb_url)->toBe('https://rebrickable.com/sets/10281-1/slug/')
        ->and($moved->fresh()->only(['year', 'theme_id']))->toEqual(['year' => 1991, 'theme_id' => 52])
        ->and($unchanged->fresh()->updated_at)->toEqual($unchangedUpdatedAt)
        ->and($unknownTheme->fresh()->theme_id)->toBe(50)
        ->and(Set::where('number', '9999-1')->exists())->toBeFalse();
});
