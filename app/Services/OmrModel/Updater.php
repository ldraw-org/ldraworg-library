<?php

namespace App\Services\OmrModel;

use App\Models\Omr\OmrModel;
use App\Models\User;

class Updater
{
    public function __construct(
        protected ModelDetails $details,
        protected ModelFile $file,
    ) {
    }

    /**
     * Updates a model's details and, when new file contents are given, replaces its file and image.
     *
     * @param array{alt_model?: bool, alt_model_name?: ?string, missing_parts?: bool, missing_patterns?: bool, missing_stickers?: bool, notes?: ?string} $data
     */
    public function update(OmrModel $model, User $author, array $data = [], ?string $contents = null): OmrModel
    {
        $model->update($this->details->attributes($author, $data, $model));

        if (is_null($contents)) {
            $this->file->syncFilename($model);
        } else {
            $this->file->store($model, $contents);
        }

        return $model;
    }
}
