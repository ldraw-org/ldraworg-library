<?php

use App\Enums\PreviewRotation;
use App\Jobs\GeneratePartImage;
use App\Models\Part\Part;
use App\Services\Part\PreviewSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('regenerates the header and queues a new image when the preview changes', function () {
    Queue::fake();
    $part = Part::factory()->official()->create();

    app(PreviewSync::class)->updatePartPreview($part, PreviewRotation::Y180);

    expect($part->fresh())
        ->preview->toBe(PreviewRotation::Y180)
        ->has_minor_edit->toBeTrue()
        ->header->toContain(PreviewRotation::Y180->ldrawString());
    Queue::assertPushed(GeneratePartImage::class);
});

it('treats a missing preview as the default preview', function () {
    Queue::fake();
    $part = Part::factory()->official()->create(['preview' => PreviewRotation::Default]);

    app(PreviewSync::class)->updatePartPreview($part, null);

    expect($part->fresh()->has_minor_edit)->toBeFalse();
    Queue::assertNotPushed(GeneratePartImage::class);
});
