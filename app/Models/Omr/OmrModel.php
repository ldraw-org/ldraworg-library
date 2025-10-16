<?php

namespace App\Models\Omr;

use App\Enums\License;
use App\Models\Traits\HasUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @mixin IdeHelperOmrModel
 */
class OmrModel extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasUser;

    protected $guarded = [];

    protected $with = [
        'set',
    ];

    /**
    * @return array{
    *     'notes': 'array',
    *     'license': 'App\\Enums\\License',
    *     'missing_parts': 'boolean',
    *     'missing_patterns': 'boolean',
    *     'missing_stickers': 'boolean',
    *     'alt_model': 'boolean',
    *     'approved': 'boolean'    
    * }
    */
    protected function casts(): array
    {
        return [
            'notes' => 'array',
            'license' => License::class,
            'missing_parts' => 'boolean',
            'missing_patterns' => 'boolean',
            'missing_stickers' => 'boolean',
            'alt_model' => 'boolean',
            'approved' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->keepOriginalImageFormat()
                    ->fit(Fit::Contain, 35, 75);
                $this->addMediaConversion('feed-image')
                    ->keepOriginalImageFormat()
                    ->fit(Fit::Contain, 225, 225);
            });
        $this->addMediaCollection('file')
            ->singleFile();
    }

    public function set(): BelongsTo
    {
        return $this->belongsTo(Set::class, 'set_id', 'id');
    }

    public function filename(): string
    {
        $filename = $this->set->number;
        $filename .= $this->alt_model ? '_' . str_replace(' ', '-', $this->alt_model_name) : '';
        return "{$filename}.mpd";
    }
}
