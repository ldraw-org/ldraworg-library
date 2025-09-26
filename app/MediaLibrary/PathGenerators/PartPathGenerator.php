<?php

namespace App\MediaLibrary\PathGenerators;

use App\Models\Part\Part;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class PartPathGenerator implements PathGenerator
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
        $part = Part::find($media->model_id);
        $model_path = "ldraw/{$part->libFolder()}/{$part->type->folder()}";
        if ($prefix !== '') {
            return $prefix.'/'.$model_path;
        }

        return $model_path;
    }
}