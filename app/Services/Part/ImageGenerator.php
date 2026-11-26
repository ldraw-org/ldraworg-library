<?php

namespace App\Services\Part;

use App\Models\Part\Part;
use App\Services\Render\LDView;
use Spatie\Image\Image;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class ImageGenerator
{
    public function __construct(
        protected LDView $ldview,
    ) {
    }

    /**
     * Render the part's image and run the full optimizer suite on it.
     * For a texmap the image is a re-encoded copy, so the part body itself is left untouched.
     */
    public function regenerateImage(Part $part): void
    {
        if ($part->isTexmap()) {
            $image = imagecreatefromstring($part->get());
        } else {
            $image = $this->ldview->render($part);
        }
        $dir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $imagePath = $dir->path(pathinfo($part->filename, PATHINFO_FILENAME) . '.png');
        imagepng($image, $imagePath);
        Image::load($imagePath)->optimize()->save($imagePath);
        $part->clearMediaCollection('image');
        $part->addMedia($imagePath)
            ->toMediaCollection('image');
    }

}
