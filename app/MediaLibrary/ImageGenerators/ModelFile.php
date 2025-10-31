<?php

namespace App\MediaLibrary\ImageGenerators;

use App\Services\LDraw\Render\LDView;
use App\Services\Parser\ImprovedParser;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Conversions\ImageGenerators\ImageGenerator;

class ModelFile extends ImageGenerator
{
    /**
    * This function should return a path to an image representation of the given file.
    */
    public function convert(string $file, ?Conversion $conversion = null): ?string
    {
        $pathToImageFile = pathinfo($file, PATHINFO_DIRNAME).'/'.pathinfo($file, PATHINFO_FILENAME).'.png';

        // Here you should convert the file to an image and return generated conversion path.
        $image = app(LDView::class)->render(ImprovedParser::dosLineEndings(file_get_contents($file)));
        imagepng($image, $pathToImageFile);

        return $pathToImageFile;
    }

    public function requirementsAreInstalled(): bool
    {
        return true;
    }

    public function supportedExtensions(): Collection
    {
        return collect([
            'mpd',
            'ldr',
        ]);
    }

    public function supportedMimeTypes(): Collection
    {
        return collect([
            'application/x-ldraw',
            'text/plain',
        ]);
    }

}
