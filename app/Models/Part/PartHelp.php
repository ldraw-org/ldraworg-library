<?php

namespace App\Models\Part;

use App\Models\Traits\HasOrder;
use App\Models\Traits\HasPart;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperPartHelp
 */
class PartHelp extends Model
{
    use HasPart;
    use HasOrder;

    protected $fillable = [
        'order',
        'text',
        'part_id',
    ];

    public $timestamps = false;
}
