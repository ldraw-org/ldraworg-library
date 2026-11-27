<?php

use App\Enums\PartType;
use App\Events\PartSubmitted;
use App\Jobs\CheckPart;
use App\Models\Part\Part;
use App\Models\User;
use App\Services\Check\Enums\PartError;
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

it('checks a submitted part against its generated header', function () {
    Queue::fake();
    Event::fake([PartSubmitted::class]);
    User::factory()->create(['name' => 'TestUser', 'realname' => 'Test User']);
    $contents = implode("\r\n", [
        '0 Brick  2 x  4 with Test Pattern',
        '0 Name: 3001p99.dat',
        '0 Author: Test User [TestUser]',
        '0 !LDRAW_ORG Unofficial_Part',
        '0 !LICENSE Licensed under CC BY 4.0 : see CAreadme.txt',
        '',
        '0 BFC CERTIFY CCW',
        '',
        '0 // body',
    ]);

    $part = app(Registrar::class)->submit(
        collect([LDrawFile::fromArray(['mimetype' => 'text/plain', 'filename' => '3001p99.dat', 'contents' => $contents])]),
        User::factory()->create(),
    )->sole();

    expect(Part::query()->hasMessage(PartError::NoSetKeywordForPattern)->pluck('id')->all())->toBe([$part->id]);
    Queue::assertNotPushed(CheckPart::class);
});

it('places a texmap with the type of the existing texture of exactly the same name', function () {
    Queue::fake();
    Event::fake([PartSubmitted::class]);
    Part::factory()->unofficial()->create(['filename' => 'p/textures/tex-1.png', 'type' => PartType::PrimitiveTexmap]);

    $part = app(Registrar::class)->submit(
        collect([LDrawFile::fromArray(['mimetype' => 'image/png', 'filename' => 'tex_1.png', 'contents' => uncompressedTexmapPng()])]),
        User::factory()->create(),
    )->sole();

    expect($part->filename)->toBe('parts/textures/tex_1.png');
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
