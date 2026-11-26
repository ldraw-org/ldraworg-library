<?php

namespace App\Services\Omr\OmrModel;

use App\Jobs\GenerateOmrModelImage;
use App\Models\Omr\OmrModel;

class ModelFile
{
    /**
     * Replaces the model's file and queues a new image. Media paths are per model, so the old file is
     * cleared first or removing it would delete a new file of the same name.
     */
    public function store(OmrModel $model, string $contents): void
    {
        $model->clearMediaCollection('file');
        $model->addMediaFromString($contents)
            ->usingFilename($model->filename())
            ->toMediaCollection('file');
        GenerateOmrModelImage::dispatch($model);
    }

    /**
     * Renames the stored file when the model's details change its filename.
     */
    public function syncFilename(OmrModel $model): void
    {
        $file = $model->getFirstMedia('file');
        if ($file && $file->file_name !== $model->filename()) {
            $file->file_name = $model->filename();
            $file->save();
        }
    }
}
