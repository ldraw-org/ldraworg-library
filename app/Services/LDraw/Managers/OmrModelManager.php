<?php

namespace App\Services\LDraw\Managers;

use App\Jobs\GenerateOmrModelImage;
use App\Models\Omr\OmrModel;
use App\Models\Omr\Set;
use App\Models\User;
use App\Settings\LibrarySettings;
use Illuminate\Support\Arr;

class OmrModelManager
{
    public function __construct(
        protected LibrarySettings $settings
    ) {
    }

    /**
     * @param array{alt_model?: bool, alt_model_name?: ?string, missing_parts?: bool, missing_patterns?: bool, missing_stickers?: bool, notes?: ?string} $data
     */
    public function addModel(string $contents, Set $set, User $user, array $data = []): OmrModel
    {
        $this->assignAuthorRole($user);

        $model = OmrModel::create([
            'user_id' => $user->id,
            'set_id' => $set->id,
            'approved' => true,
            'license' => $user->license,
            ...$this->modelAttributes($data),
        ]);
        $this->storeFile($model, $contents);

        return $model;
    }

    /**
     * Updates a model's details and, when new file contents are given, replaces its file and image.
     *
     * @param array{alt_model?: bool, alt_model_name?: ?string, missing_parts?: bool, missing_patterns?: bool, missing_stickers?: bool, notes?: ?string} $data
     */
    public function updateModel(OmrModel $model, User $user, array $data = [], ?string $contents = null): OmrModel
    {
        $this->assignAuthorRole($user);

        $model->update([
            'user_id' => $user->id,
            'license' => $model->user_id === $user->id ? $model->license : $user->license,
            ...$this->modelAttributes($data),
        ]);

        if (!is_null($contents)) {
            $this->storeFile($model, $contents);
        } elseif (($file = $model->getFirstMedia('file')) && $file->file_name !== $model->filename()) {
            $file->file_name = $model->filename();
            $file->save();
        }

        return $model;
    }

    protected function assignAuthorRole(User $user): void
    {
        if (!$user->hasRole('OMR Author')) {
            $user->assignRole('OMR Author');
        }
    }

    /**
     * @param array{alt_model?: bool, alt_model_name?: ?string, missing_parts?: bool, missing_patterns?: bool, missing_stickers?: bool, notes?: ?string} $data
     * @return array{missing_parts: bool, missing_patterns: bool, missing_stickers: bool, alt_model: bool, alt_model_name: ?string, notes: array{notes: string}}
     */
    protected function modelAttributes(array $data): array
    {
        return [
            'missing_parts' => Arr::get($data, 'missing_parts', false),
            'missing_patterns' => Arr::get($data, 'missing_patterns', false),
            'missing_stickers' => Arr::get($data, 'missing_stickers', false),
            'alt_model' => Arr::get($data, 'alt_model', false),
            'alt_model_name' => Arr::get($data, 'alt_model', false) ? Arr::get($data, 'alt_model_name') : null,
            'notes' => ['notes' => Arr::get($data, 'notes') ?? ''],
        ];
    }

    /**
     * Media paths are per model, so the old file is cleared first or removing it would delete a new file of the same name.
     */
    protected function storeFile(OmrModel $model, string $contents): void
    {
        $model->clearMediaCollection('file');
        $model->addMediaFromString($contents)
            ->usingFilename($model->filename())
            ->toMediaCollection('file');
        GenerateOmrModelImage::dispatch($model);
    }
}
