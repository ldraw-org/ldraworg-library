<?php

namespace App\Services\Render;

use App\Models\Part\Part;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class ImageService
{
    public function __construct(
        protected LDView $ldview,
    )
    {}

    public function updatePartImage(Part $part): void
    {
        if ($part->isTexmap()) {
            $this->updateTexmapImage($part);
            return;
        }
        $part->updateDatImage();
    }

    protected function updateTexmapImage(Part $part)
    {
        if (!$part->isTexmap()) {
            return;
        }
        $dir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $imagePath = $dir->path(substr($part->filename, 0, -4) . '.png');
        $image = imagecreatefromstring($part->get());
        imagepng($image, $imagePath);
        (new OptimizerChain())->addOptimizer(new Optipng([]))->optimize($imagePath);
        return;
    }

    protected function updateDatImage(Part $part)
    {
        if ($part->isTexmap()) {
            return;
        }
        $image = $this->ldview->renderSync($part);
        $dir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $imagePath = $dir->path(substr($part->filename, 0, -4) . '.png');
        $image = imagecreatefromstring($part->get());
        imagepng($image, $imagePath);
        (new OptimizerChain())->addOptimizer(new Optipng([]))->optimize($imagePath);
        return;
    }

}