<?php

namespace App\Services\Part;

use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class TexmapOptimizer
{
    /**
     * Losslessly optimize a texmap PNG. The original is returned if optimization fails or doesn't shrink it.
     */
    public function optimize(string $png): string
    {
        $dir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $path = $dir->path('texmap.png');
        file_put_contents($path, $png);

        (new OptimizerChain())
            ->addOptimizer(new Optipng(['-i0', '-o2', '-quiet']))
            ->optimize($path);

        $optimized = file_get_contents($path);

        return $optimized !== false && $optimized !== '' && strlen($optimized) < strlen($png)
            ? $optimized
            : $png;
    }
}
