<?php

namespace App\Console\Commands;

use App\Models\Part\PartRelease;
use App\Services\Part\Release\MakeReleaseImage;
use Illuminate\Console\Command;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class MassUpdate extends Command
{
    protected $signature = 'lib:mass-update';

    protected $description = 'A command to do focused mass updates';

    public function handle(MakeReleaseImage $makeReleaseImage): void
    {
        $tempDir = TemporaryDirectory::make()->deleteWhenDestroyed();
        PartRelease::all()
            ->each(function (PartRelease $release) use ($makeReleaseImage, $tempDir) {
                $publicImagePath = public_path("images/updates/{$release->short}.png");
                if (file_exists($publicImagePath)) {
                    $image = file_get_contents($publicImagePath);
                } else {
                    $image = $makeReleaseImage->handle($release);
                }
                $imagePath = $tempDir->path("{$release->short}.png");
                file_put_contents($imagePath, $image);
                $release->clearMediaCollection('image');
                $release->addMedia($imagePath)->toMediaCollection('image');
            });
    }
}
