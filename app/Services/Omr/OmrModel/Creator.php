<?php

namespace App\Services\Omr\OmrModel;

use App\Models\Omr\OmrModel;
use App\Models\Omr\Set;
use App\Models\User;

class Creator
{
    public function __construct(
        protected ModelDetails $details,
        protected ModelFile $file,
    ) {
    }

    /**
     * @param array{alt_model?: bool, alt_model_name?: ?string, missing_parts?: bool, missing_patterns?: bool, missing_stickers?: bool, notes?: ?string} $data
     */
    public function create(string $contents, Set $set, User $author, array $data = []): OmrModel
    {
        $model = OmrModel::create([
            'set_id' => $set->id,
            'approved' => true,
            ...$this->details->attributes($author, $data),
        ]);
        $this->file->store($model, $contents);

        return $model;
    }
}
