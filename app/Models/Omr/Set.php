<?php

namespace App\Models\Omr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperSet
 */
class Set extends Model
{
    protected $guarded = [];

    protected $with = [
        'theme',
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class, 'theme_id', 'id');
    }

    public function models(): HasMany
    {
        return $this->hasMany(OmrModel::class, 'set_id', 'id');
    }

    public function mainModel(): OmrModel
    {
        return $this->models->where('alt_model', false)->first() ?? $this->models->first();
    }
}
