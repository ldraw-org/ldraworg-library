<?php

namespace App\Services\LDraw\Managers;

use App\Jobs\GenerateOmrModelImage;
use App\Services\Render\LDView;
use App\Models\Omr\OmrModel;
use App\Models\Omr\Set;
use App\Models\User;
use App\Settings\LibrarySettings;
use Illuminate\Support\Arr;
use Spatie\Image\Image;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class OmrModelManager
{
    public function __construct(
        public LDView $render,
        protected LibrarySettings $settings
    ) {
    }

    public function updateImage(OmrModel $model): void
    {
        $image = $this->render->render($model);
        $dir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $imagePath = $dir->path(substr($model->filename(), 0, -4) . '.png');
        imagepng($image, $imagePath);
        Image::load($imagePath)->optimize()->save($imagePath);
        $model->clearMediaCollection('image');
        $model->addMedia($imagePath)
            ->toMediaCollection('image');
    }

    /**
     * @param array{alt_model?: bool, alt_model_name?: ?string, missing_parts?: bool, missing_patterns?: bool, missing_stickers?: bool, notes?: ?string} $data
     */
    public function addModel(string $contents, Set $set, User $user, array $data = []): OmrModel
    {
        if (!$user->hasRole('OMR Author')) {
            $user->assignRole('OMR Author');
        }

        $model = OmrModel::create([
            'user_id' => $user->id,
            'set_id' => $set->id,
            'missing_parts' => Arr::get($data, 'missing_parts', false),
            'missing_patterns' => Arr::get($data, 'missing_patterns', false),
            'missing_stickers' => Arr::get($data, 'missing_stickers', false),
            'approved' => true,
            'alt_model' => Arr::get($data, 'alt_model', false),
            'alt_model_name' => Arr::get($data, 'alt_model', false) ? Arr::get($data, 'alt_model_name') : null,
            'notes' => ['notes' => Arr::get($data, 'notes') ?? ''],
            'license' => $user->license,
        ]);
        $model->addMediaFromString($contents)
            ->usingFilename($model->filename())
            ->toMediaCollection('file');
        GenerateOmrModelImage::dispatch($model);

        return $model;
    }
}
