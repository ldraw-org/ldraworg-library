<?php

use App\Models\Part\Part;
use App\Services\Support\DeleteOrphanMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('deletes media and files whose model no longer exists and keeps the rest', function () {
    Storage::fake(config('media-library.disk_name'));
    $disk = Storage::disk(config('media-library.disk_name'));

    $livePart = Part::factory()->create();
    $liveImage = $livePart->addMedia(UploadedFile::fake()->image('live.png'))->toMediaCollection('image');
    $orphanPart = Part::factory()->create();
    $orphanImage = $orphanPart->addMedia(UploadedFile::fake()->image('orphan.png'))->toMediaCollection('image');
    Part::whereKey($orphanPart->id)->delete();

    expect(app(DeleteOrphanMedia::class)->handle())->toBe(1);

    $this->assertModelMissing($orphanImage);
    $disk->assertMissing($orphanImage->getPathRelativeToRoot());
    $this->assertModelExists($liveImage);
    $disk->assertExists($liveImage->getPathRelativeToRoot());
});
