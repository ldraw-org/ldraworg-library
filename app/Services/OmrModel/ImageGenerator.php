<?php

namespace App\Services\OmrModel;

use App\Models\Omr\OmrModel;
use App\Services\Render\LDView;
use Spatie\Image\Image;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class ImageGenerator
{
    public function __construct(
        public LDView $ldview,
    ) {}

    public function regenerateImage(OmrModel $model): void
    {
        $image = $this->ldview->render($model);
        $dir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $imagePath = $dir->path(pathinfo($model->filename(), PATHINFO_FILENAME) . '.png');
        imagepng($image, $imagePath);
        Image::load($imagePath)->optimize()->save($imagePath);
        $model->clearMediaCollection('image');
        $model->addMedia($imagePath)
            ->toMediaCollection('image');
    }

}
