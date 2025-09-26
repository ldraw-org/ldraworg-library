<?php

namespace App\Models\Part;

use App\Observers\PartKeywordObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperPartKeyword
 */
#[ObservedBy(PartKeywordObserver::class)]
class PartKeyword extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function parts(): BelongsToMany
    {
        return $this->belongsToMany(Part::class, 'parts_part_keywords', 'part_keyword_id', 'part_id');
    }
}
