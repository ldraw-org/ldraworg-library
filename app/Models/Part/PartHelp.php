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

    protected $guarded = [];

    public $timestamps = false;
}
