<?php

namespace App\MediaLibrary\PathGenerators;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class LibraryPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media).'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media).'/responsive-images/';
    }

    protected function getBasePath(Media $media): string
    {
        $prefix = config('media-library.prefix', '');
        $model_path = Str::snake(Str::pluralStudly(class_basename($media->model_type))) . '/' . $media->model_id;
        if ($prefix !== '') {
            return $prefix.'/'.$model_path;
        }

        return $model_path;
    }
}
