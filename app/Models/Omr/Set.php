<?php

namespace App\Models\Omr;

use App\Models\RebrickablePart;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Set extends Model
{
    protected $guarded = [];

    protected $with = [
        'theme',
    ];

    /**
     * @return array{
     *     'refreshed_at': 'datetime'
     * }
     */
    protected function casts(): array
    {
        return [
            'refreshed_at' => 'datetime',
        ];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class, 'theme_id', 'id');
    }

    public function models(): HasMany
    {
        return $this->hasMany(OmrModel::class, 'set_id', 'id');
    }

    public function rebrickable_parts(): BelongsToMany
    {
        return $this->belongsToMany(RebrickablePart::class, 'rebrickable_parts_sets', 'set_id', 'rebrickable_part_id')
                    ->withPivot('ldraw_colour_id', 'quantity');
    }

    public function mainModel(): OmrModel
    {
        return $this->models->where('alt_model', false)->first() ?? $this->models->first();
    }
}
