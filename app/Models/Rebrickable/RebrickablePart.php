<?php

namespace App\Models\Rebrickable;

use App\Models\Part\Part;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperRebrickablePart
 */
class RebrickablePart extends Model
{
    protected $fillable = [
        'part_num',
        'name',
        'part_url',
        'part_img_url',
        'part_id'
    ];

    public function part(): BelongsTo
    {
        return $this->BelongsTo(Part::class, 'part_id', 'id');
    }

}
