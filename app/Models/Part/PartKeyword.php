<?php

namespace App\Models\Part;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperPartKeyword
 */
class PartKeyword extends Model
{
    protected $fillable = [
        'keyword',
    ];

    public $timestamps = false;

    public function parts(): BelongsToMany
    {
        return $this->belongsToMany(Part::class, 'parts_part_keywords', 'part_keyword_id', 'part_id');
    }
}
