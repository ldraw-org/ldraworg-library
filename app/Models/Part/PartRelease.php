<?php

namespace App\Models\Part;

use App\Enums\PartType;
use App\Models\Traits\HasParts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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

    protected $guarded = [];

    /**
    * @return array{
    *     part_data: 'array',
    *     new_of_type: 'array',
    *     moved: 'array',
    *     renamed: 'array',
    *     fixed: 'array',
    *     enabled: 'boolean'
    * }
    */
    protected function casts(): array
    {
        return [
            'part_data' => 'array',
            'new_of_type' => 'array',
            'moved' => 'array',
            'renamed' => 'array',
            'fixed' => 'array',
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

    protected function blurb(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $prims = 0;
                $parts = 'no';
                foreach (json_decode($attributes['new_of_type'], true) ?? [] as $type => $count) {
                    $t = PartType::tryFrom($type);
                    if (Str::startsWith($t->folder(), 'p/') && $t->isDatFormat()) {
                        $prims += $count;
                    }
                    if ($t == PartType::Part) {
                        $parts = $count;
                    }

                }
                $prims = $prims > 0 ? $prims : 'no';
                return "This update adds {$attributes['new']} new files to the core library, including {$parts} new parts and {$prims} new primitives.";
            }
        );
    }

    protected function notes(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $notes = "Total files: {$attributes['total']}\n" .
                    "New files: {$attributes['new']}\n";
                foreach (json_decode(Arr::get($attributes, 'new_of_type'), true) ?? [] as $type => $count) {
                    if ($count == 0) {
                        continue;
                    }
                    $type = PartType::tryFrom($type);
                    $notes .= "New {$type->description()}s: {$count}\n";
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
