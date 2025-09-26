<?php

namespace App\Models\Part;

use App\Models\Traits\HasParts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @mixin IdeHelperPartRelease
 */
class PartRelease extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasParts;
    use HasFactory;

    protected $fillable = [
        'name',
        'short',
        'created_at',
        'part_list',
        'part_data',
        'enabled'
    ];

    /**
    * @return array{
    *     part_list: 'array',
    *     part_data: 'Illuminate\Database\Eloquent\Casts\AsArrayObject'
    *     enabled: 'boolean'
    * }
    */
    protected function casts(): array
    {
        return  [
            'part_list' => 'array',
            'part_data' => AsArrayObject::class,
            'enabled' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('view')
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->keepOriginalImageFormat()
                    ->fit(Fit::Contain, 35, 75);                    
            });
    }

    protected function notes(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                if (is_null($attributes['part_data'])) {
                    return '';
                }
                $data = json_decode($attributes['part_data'] ?? "{}", true);
                $notes = "Total files: {$data['total_files']}\n" .
                    "New files: {$data['new_files']}\n";
                foreach ($data['new_types'] as $t) {
                    $notes .= "New {$t['name']}s: {$t['count']}\n";
                }
                return $notes;
            }
        );
    }

    public function toString(): string
    {
        return $this->short == 'original' ? " ORIGINAL" : " UPDATE {$this->name}";
    }

    public static function current(): ?self
    {
        return self::where('enabled', true)->latest()?->first();
    }

    public function isLatest(): bool
    {
        return self::current()?->id === $this->id;
    }
}
