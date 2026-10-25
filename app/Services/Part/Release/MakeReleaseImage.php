<?php

namespace App\Services\Part\Release;

use App\Models\Part\PartRelease;
use App\Services\UpdateImageGenerator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MakeReleaseImage
{
    public function handle(PartRelease $release): string
    {
        $fontPath = resource_path('fonts/DejaVuSans.ttf');
        $images = $release
            ->getMedia('view')
            ->filter(fn (Media $media) => $media->getCustomProperty('highlight') === true);
        if ($images->isNotEmpty()) {
            $paths = $images
                ->map(fn(Media $media) => $media->getPath())
                ->random(8)
                ->values()
                ->all();
        } else {
            $paths = [public_path('images/updates/default_img.png')];
        }
        $generator = new UpdateImageGenerator(
            width: 600,
            height: 337,
            caption: "Parts Update {$release->name}",
            font: $fontPath
        );
        return $generator->generate($paths);
    }
}
