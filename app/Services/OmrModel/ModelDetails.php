<?php

namespace App\Services\OmrModel;

use App\Enums\License;
use App\Models\Omr\OmrModel;
use App\Models\User;
use Illuminate\Support\Arr;

class ModelDetails
{
    /**
     * Attributes for a model written by the given author, who is given the OMR Author role if needed.
     * An existing model keeps its license unless the author changes.
     *
     * @param array{alt_model?: bool, alt_model_name?: ?string, missing_parts?: bool, missing_patterns?: bool, missing_stickers?: bool, notes?: ?string} $data
     * @return array{user_id: int, license: License, missing_parts: bool, missing_patterns: bool, missing_stickers: bool, alt_model: bool, alt_model_name: ?string, notes: array{notes: string}}
     */
    public function attributes(User $author, array $data, ?OmrModel $existing = null): array
    {
        if (!$author->hasRole('OMR Author')) {
            $author->assignRole('OMR Author');
        }

        return [
            'user_id' => $author->id,
            'license' => $existing?->user_id === $author->id ? $existing->license : $author->license,
            'missing_parts' => Arr::get($data, 'missing_parts', false),
            'missing_patterns' => Arr::get($data, 'missing_patterns', false),
            'missing_stickers' => Arr::get($data, 'missing_stickers', false),
            'alt_model' => Arr::get($data, 'alt_model', false),
            'alt_model_name' => Arr::get($data, 'alt_model', false) ? Arr::get($data, 'alt_model_name') : null,
            'notes' => ['notes' => Arr::get($data, 'notes') ?? ''],
        ];
    }
}
