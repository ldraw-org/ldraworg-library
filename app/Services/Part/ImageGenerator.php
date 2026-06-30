<?php

namespace App\Services\Part;

use App\Models\Part\Part;
use App\Services\Render\LDView;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class ImageGenerator
{
    public function __construct(
        protected LDView $ldview,
        protected OptimizerChain $optimizerChain
    )
    {
        $this->optimizerChain->addOptimizer(new Optipng([]));
    }

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
        $this->optimizerChain->optimize($imagePath);
        $part->clearMediaCollection('image');
        $part->addMedia($imagePath)
            ->toMediaCollection('image');
    }

}
