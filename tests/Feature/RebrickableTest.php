<?php

use App\Services\LDraw\Rebrickable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

it('fetches set parts from rebrickable', function () {
    Http::fake([
        'https://rebrickable.com/api/v3/lego/*' => Http::response([
            'results' => [
                ['part_num' => '3001', 'qty' => 4]
            ],
            'next' => null,
        ], 200)
    ]);

    Cache::forget('rebrickable_set_parts_1234-1');

    $service = new Rebrickable();

    $result = $service->getSetParts('1234-1');

    expect($result)->toHaveCount(1);
    expect($result->first()['part_num'])->toBe('3001');
});

it('handles pagination correctly', function () {
    Http::fakeSequence()
        ->push([
            'results' => [['id' => 1]],
            'next' => 'https://rebrickable.com/api/v3/lego/parts/?page=2'
        ])
        ->push([
            'results' => [['id' => 2]],
            'next' => null
        ]);

    Cache::flush();

    $service = new Rebrickable();

    $result = $service->getParts([]);

    expect($result)->toHaveCount(2);
    expect($result->pluck('id')->all())->toEqual([1, 2]);
});

it('returns unpaginated set data', function () {
    Http::fake([
        '*' => Http::response([
            'name' => 'Test Set',
            'set_num' => '1234-1'
        ], 200)
    ]);

    Cache::flush();

    $service = new Rebrickable();

    $result = $service->getSet('1234-1');

    expect($result->get('name'))->toBe('Test Set');
    expect($result->get('set_num'))->toBe('1234-1');
});

it('enforces 1 request per second globally', function () {
    Cache::flush();

    $timestamps = [];

    Http::fake([
        'https://rebrickable.com/api/v3/lego/*' => function ($request) use (&$timestamps) {
            $timestamps[] = microtime(true);
            return Http::response(['results' => []], 200);
        },
    ]);

    $service = new Rebrickable();

    $start = microtime(true);

    $service->getSetParts('1234-1');
    $service->getSetParts('1234-2');
    $service->getSetParts('1234-3');

    $end = microtime(true);

    expect($timestamps)->toHaveCount(3);

    $intervals = [];
    for ($i = 1; $i < count($timestamps); $i++) {
        $intervals[] = $timestamps[$i] - $timestamps[$i - 1];
    }

    foreach ($intervals as $interval) {
        expect($interval)->toBeGreaterThanOrEqual(1);
    }

    expect($end - $start)->toBeGreaterThanOrEqual(count($timestamps) - 1);
});

it('caches results and avoids repeated API calls', function () {
    Cache::flush();

    Http::fake([
        'https://rebrickable.com/api/v3/lego/*' => Http::response([
            'results' => [['id' => 1]],
            'next' => null
        ], 200),
    ]);

    $service = new Rebrickable();

    $response1 = $service->getSetParts('1234-1');
    $response2 = $service->getSetParts('1234-1');

    expect($response1)->toEqual($response2);

    Http::assertSentCount(1);
});

it('retries on 429 responses', function () {
    Http::fakeSequence()
        ->push(null, 429)
        ->push(['results' => []]);

    Cache::flush();

    $service = new Rebrickable();
    $service->getThemes();

    Http::assertSentCount(2);
});

it('handles API failure gracefully', function () {
    Http::fake([
        '*' => Http::response('Server Error', 500)
    ]);

    Cache::flush();

    $service = new Rebrickable();

    $result = $service->getThemes();

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result)->toBeEmpty();
});
