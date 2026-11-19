<?php

use App\Models\Omr\Theme;
use App\Services\Omr\Theme\SyncRebrickableThemes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    fakeRebrickableCsv([
        'themes' => [
            'id,name,parent_id',
            '3,Competition,1',
            '1,Technic,',
            '50,Town,',
        ],
    ]);
});

it('upserts themes from the CSV downloads without deleting others', function () {
    Http::preventStrayRequests();
    Theme::create(['id' => 50, 'name' => 'Old Town Name']);
    Theme::create(['id' => 7, 'name' => 'Fabuland']);

    app(SyncRebrickableThemes::class)->handle();

    expect(Theme::orderBy('id')->get(['id', 'name', 'parent_id'])->toArray())->toBe([
        ['id' => 1, 'name' => 'Technic', 'parent_id' => null],
        ['id' => 3, 'name' => 'Competition', 'parent_id' => 1],
        ['id' => 7, 'name' => 'Fabuland', 'parent_id' => null],
        ['id' => 50, 'name' => 'Town', 'parent_id' => null],
    ]);
});

it('finds a missing theme in the CSV downloads without calling the API', function () {
    Http::preventStrayRequests();

    expect(app(SyncRebrickableThemes::class)->ensureExists(3))->toBeTrue()
        ->and(Theme::count())->toBe(3);
});

it('falls back to the API for a theme and ancestors newer than the CSV downloads', function () {
    Http::fake([
        'rebrickable.com/api/v3/lego/themes/900/' => Http::response(['id' => 900, 'name' => 'New Sub', 'parent_id' => 800]),
        'rebrickable.com/api/v3/lego/themes/800/' => Http::response(['id' => 800, 'name' => 'New Parent', 'parent_id' => 1]),
    ]);

    expect(app(SyncRebrickableThemes::class)->ensureExists(900))->toBeTrue()
        ->and(Theme::find(900)->parent_id)->toBe(800)
        ->and(Theme::find(800)->parent_id)->toBe(1);
    Http::assertSentCount(2);
});

it('reports a theme that neither source has', function () {
    Http::fake(['rebrickable.com/*' => Http::response(['detail' => 'Not found.'], 404)]);

    expect(app(SyncRebrickableThemes::class)->ensureExists(901))->toBeFalse();
    Http::assertSentCount(1);
});
