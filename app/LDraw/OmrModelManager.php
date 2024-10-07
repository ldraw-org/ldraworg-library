<?php

namespace App\LDraw;

use App\LDraw\Render\LDView;
use App\Models\Omr\OmrModel;
use App\Settings\LibrarySettings;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Image;
use Spatie\Image\Enums\Fit;

class OmrModelManager
{
    public function __construct(
        public LDView $render,
        protected LibrarySettings $settings
    ) {
    }

    public function updateImage(OmrModel $model): void
    {
        $image = $this->render->render($model);
        $imageFilename = substr($model->filename(), 0, -4) . '.png';
        $imagePath = Storage::disk('images')->path("omr/models/{$imageFilename}");
        $imageThumbPath = substr($imagePath, 0, -4) . '_thumb.png';
        imagepng($image, $imagePath);
        Image::load($imagePath)->optimize()->save($imagePath);
        Image::load($imagePath)->fit(Fit::Contain, $this->settings->max_thumb_width, $this->settings->max_thumb_height)->optimize()->save($imageThumbPath);
    }

}