<?php

use App\Events\PartSubmitted;
use App\Models\User;
use App\Services\LDraw\LDrawFile;
use App\Services\Part\Submit\Registrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('losslessly optimizes a submitted texmap body', function () {
    Queue::fake();
    Event::fake([PartSubmitted::class]);
    $uncompressed = uncompressedTexmapPng();

    $parts = app(Registrar::class)->submit(
        collect([LDrawFile::fromArray(['mimetype' => 'image/png', 'filename' => 'texture.png', 'contents' => $uncompressed])]),
        User::factory()->create(),
    );

    $body = $parts->first()->get();
    expect(strlen($body))->toBeLessThan(strlen($uncompressed))
        ->and(texmapPixels($body))->toBe(texmapPixels($uncompressed));
});
