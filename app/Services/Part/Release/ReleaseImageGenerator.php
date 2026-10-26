<?php

namespace App\Services\Part\Release;

use App\Models\Part\PartRelease;
use App\Services\UpdateImageGenerator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class ReleaseImageGenerator
{
    public function handle(PartRelease $release): void
    {
        $tempDir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $publicImagePath = public_path("images/updates/{$release->short}.png");
        if (file_exists($publicImagePath)) {
            $image = file_get_contents($publicImagePath);
        } else {
            $image = $this->generateImage($release);
        }
        $imagePath = $tempDir->path("{$release->short}.png");
        file_put_contents($imagePath, $image);
        $release->clearMediaCollection('image');
        $release->addMedia($imagePath)->toMediaCollection('image');
    }

    protected function generateImage(PartRelease $release): string
    {
        $fontPath = resource_path('fonts/DejaVu/DejaVuSans.ttf');
        $images = $release
            ->getMedia('view')
            ->filter(fn (Media $media) => $media->getCustomProperty('highlight') === true);
        if ($images->isNotEmpty()) {
            $paths = $images
                ->map(fn(Media $media) => $media->getPath())
                ->shuffle()
                ->take(8)
                ->values()
                ->all();
        } else {
            $paths = [
                public_path('images/updates/default_img.png')
            ];
        }
        $generator = new UpdateImageGenerator(
            width: 1200,
            height: 675,
            caption: "LDraw.org Library Update {$release->name}",
            font: $fontPath,
            captionSize: 48
        );
        return $generator->generate($paths);
    }
}
