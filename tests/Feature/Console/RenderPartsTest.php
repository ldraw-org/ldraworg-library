<?php

use App\Jobs\GeneratePartImage;
use App\Models\Part\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(config('media-library.disk_name'));
});

/**
 * @return list<int> ids of the parts queued for rendering
 */
function queuedPartImageIds(): array
{
    return Queue::pushed(GeneratePartImage::class)
        ->flatMap(fn (GeneratePartImage $job) => (fn () => $this->parts)->call($job)->modelKeys())
        ->sort()
        ->values()
        ->all();
}

it('queues only parts whose image file is missing', function () {
    $withImage = Part::factory()->create();
    $withImage->addMedia(UploadedFile::fake()->image('present.png'))->toMediaCollection('image');
    $fileDeleted = Part::factory()->create();
    $deletedImage = $fileDeleted->addMedia(UploadedFile::fake()->image('deleted.png'))->toMediaCollection('image');
    Storage::disk(config('media-library.disk_name'))->delete($deletedImage->getPathRelativeToRoot());
    $noMedia = Part::factory()->create();
    Queue::fake();

    $this->artisan('lib:render-parts', ['--missing' => true])
        ->expectsOutputToContain('Queued 2 part images')
        ->assertSuccessful();

    expect(queuedPartImageIds())->toBe([$fileDeleted->id, $noMedia->id]);
});

it('queues only unofficial parts with missing images during non-production daily maintenance', function () {
    $unofficial = Part::factory()->unofficial()->create();
    Part::factory()->official()->create();
    Queue::fake();

    $this->artisan('lib:daily-maintenance')->assertSuccessful();

    expect(queuedPartImageIds())->toBe([$unofficial->id]);
});
