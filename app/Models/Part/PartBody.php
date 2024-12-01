<?php

namespace App\Models\Part;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPartBody
 */
class PartBody extends Model
{
    protected $fillable = [
        'body',
        'part_id',
    ];

    public $timestamps = false;

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class, 'part_id', 'id');
    }
}
