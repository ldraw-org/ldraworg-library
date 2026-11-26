<?php

use App\Enums\PartType;
use App\Jobs\GeneratePartImage;
use App\Models\Part\Part;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('renders the rest of a batch when one part fails', function () {
    Storage::fake(config('media-library.disk_name'));
    $corrupt = Part::factory()->create(['type' => PartType::PartTexmap, 'filename' => 'parts/textures/corrupt.png']);
    $corrupt->setBody(base64_encode('not a png'));
    $valid = Part::factory()->create(['type' => PartType::PartTexmap, 'filename' => 'parts/textures/valid.png']);
    $valid->setBody(base64_encode(UploadedFile::fake()->image('valid.png', 8, 8)->getContent()));

    dispatch_sync(new GeneratePartImage(new Collection([$corrupt, $valid])));

    expect($corrupt->fresh()->hasMedia('image'))->toBeFalse()
        ->and($valid->fresh()->hasMedia('image'))->toBeTrue();
});
