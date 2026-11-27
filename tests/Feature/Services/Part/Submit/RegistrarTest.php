<?php

use App\Collections\PartCollection;
use App\Enums\EventType;
use App\Enums\PartType;
use App\Events\PartSubmitted;
use App\Jobs\CheckPart;
use App\Jobs\FinalizeParts;
use App\Jobs\GeneratePartImage;
use App\Jobs\UpdateZip;
use App\Models\Part\Part;
use App\Models\Part\PartEvent;
use App\Models\User;
use App\Services\Check\Enums\PartError;
use App\Services\LDraw\LDrawFile;
use App\Services\Part\BasePartSync;
use App\Services\Part\Finalizer;
use App\Services\Part\GenerateHeader;
use App\Services\Part\Submit\Registrar;
use App\Services\Part\SyncSubparts;
use App\Services\Part\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
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

/**
 * A pattern part, by a newly registered author, that has no Set keyword.
 */
function registrarPatternPartFile(): LDrawFile
{
    User::factory()->create(['name' => 'TestUser', 'realname' => 'Test User']);

    return LDrawFile::fromArray([
        'mimetype' => 'text/plain',
        'filename' => '3001p99.dat',
        'contents' => implode("\r\n", [
            '0 Brick  2 x  4 with Test Pattern',
            '0 Name: 3001p99.dat',
            '0 Author: Test User [TestUser]',
            '0 !LDRAW_ORG Unofficial_Part',
            '0 !LICENSE Licensed under CC BY 4.0 : see CAreadme.txt',
            '',
            '0 BFC CERTIFY CCW',
            '',
            '0 // body',
        ]),
    ]);
}

it('checks a submitted part once, during the submission', function () {
    Queue::fake();
    Event::fake([PartSubmitted::class]);

    $part = app(Registrar::class)->submit(collect([registrarPatternPartFile()]), User::factory()->create())->sole();

    expect(Part::query()->hasMessage(PartError::NoSetKeywordForPattern)->pluck('id')->all())->toBe([$part->id]);
    Queue::assertNotPushed(CheckPart::class);
});

it('queues the image of a submitted part instead of rendering it', function () {
    Queue::fake();

    $part = app(Registrar::class)->submit(collect([registrarPatternPartFile()]), User::factory()->create())->sole();

    expect($part->getMedia('image'))->toBeEmpty();
    Queue::assertPushed(GeneratePartImage::class, 1);
});

it('keeps a submission whose finalizing fails and finishes it from the queue', function () {
    Queue::fake();
    Exceptions::fake();
    $submitter = User::factory()->create();
    app()->instance(Finalizer::class, new class (app(SyncSubparts::class), app(Validator::class), app(BasePartSync::class), app(GenerateHeader::class)) extends Finalizer {
        public function handle(PartCollection $parts): void
        {
            throw new RuntimeException('Finalizing failed');
        }
    });

    $part = app(Registrar::class)->submit(collect([registrarPatternPartFile()]), $submitter, 'A comment')->sole();

    $hasSetKeywordMessage = fn (): bool => Part::query()->hasMessage(PartError::NoSetKeywordForPattern)->exists();

    Exceptions::assertReported(RuntimeException::class);
    Queue::assertPushed(UpdateZip::class);
    expect($hasSetKeywordMessage())->toBeFalse()
        ->and($submitter->notification_parts->modelKeys())->toBe([$part->id])
        ->and(PartEvent::sole())
        ->event_type->toBe(EventType::Submit)
        ->initial_submit->toBeTrue()
        ->user_id->toBe($submitter->id)
        ->comment->toBe('A comment');

    app()->forgetInstance(Finalizer::class);
    Queue::pushed(FinalizeParts::class)->sole()->handle(app(Finalizer::class));

    expect($hasSetKeywordMessage())->toBeTrue();
    Queue::assertPushed(GeneratePartImage::class);
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
