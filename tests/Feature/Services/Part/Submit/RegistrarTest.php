<?php

use App\Events\PartSubmitted;
use App\Models\Part\Part;
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

it('saves none of the submitted parts when one of them cannot be created', function () {
    Queue::fake();
    Event::fake([PartSubmitted::class]);

    expect(fn () => app(Registrar::class)->submit(
        collect([
            LDrawFile::fromArray(['mimetype' => 'image/png', 'filename' => 'texture.png', 'contents' => uncompressedTexmapPng()]),
            LDrawFile::fromArray(['mimetype' => 'text/plain', 'filename' => 'bad.dat', 'contents' => "0 Not an LDraw part\r\n"]),
        ]),
        User::factory()->create(),
    ))->toThrow(Error::class);

    expect(Part::count())->toBe(0);
});
