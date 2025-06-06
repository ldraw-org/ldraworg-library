<?php

namespace App\LDraw;

use App\Enums\License;
use App\Enums\Permission;
use App\Jobs\UpdateImage;
use App\LDraw\Render\LDView;
use App\Models\Mybb\MybbAttachment;
use App\Models\Omr\OmrModel;
use App\Models\Omr\Set;
use App\Models\User;
use App\Settings\LibrarySettings;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Image\Image;
use Spatie\Image\Enums\Fit;

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
        $imageFilename = substr($model->filename(), 0, -4) . '.png';
        $imagePath = Storage::disk('images')->path("omr/models/{$imageFilename}");
        $imageThumbPath = substr($imagePath, 0, -4) . '_thumb.png';
        imagepng($image, $imagePath);
        Image::load($imagePath)->optimize()->save($imagePath);
        Image::load($imagePath)->fit(Fit::Contain, $this->settings->max_thumb_width, $this->settings->max_thumb_height)->optimize()->save($imageThumbPath);
    }

    public function addModelFromMybbAttachment(MybbAttachment $file, Set $set, array $data = []): void
    {
        $modeltext = $file->get();
        $user = $file->user->library_user;
        if (is_null($user)) {
            $user = User::create([
                'name' => $file->user->loginname,
                'realname' => $file->user->username,
                'email' => $file->user->email,
                'license' => License::CC_BY_4,
                'forum_user_id' => $file->user->uid,
                'password' => bcrypt(Str::random(40)),
            ]);
        }
        
        if (!$user->hasRole('OMR Author')) {
            $user->assignRole('OMR Author');
            $user->save();
        }
        
        $model = OmrModel::create([
            'user_id' => $user->id,
            'set_id' => $set->id,
            'missing_parts' => Arr::get($data, 'missing_parts', false),
            'missing_patterns' => Arr::get($data, 'missing_patterns', false),
            'missing_stickers' => Arr::get($data, 'missing_stickers', false),
            'approved' => true,
            'alt_model' => Arr::get($data, 'alt_model', false),
            'alt_model_name' => Arr::get($data, 'alt_model_name'),
            'notes' => ['notes' => Arr::get($data, 'notes', '')],
            'license' => $user->license,
        ]);
        Storage::disk('library')->put("omr/{$model->filename()}", $modeltext);
        UpdateImage::dispatch($model);
        $file->posthash = true;
        $file->save();
    }
}
