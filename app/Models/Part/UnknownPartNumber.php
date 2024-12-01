<?php

namespace App\Models\Part;

use App\Models\Traits\HasParts;
use App\Models\Traits\HasUser;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperUnknownPartNumber
 */
class UnknownPartNumber extends Model
{
    use HasUser,
        HasParts;
    
    protected $fillable = [
        'number',
        'user_id',
        'notes',
    ];
}
