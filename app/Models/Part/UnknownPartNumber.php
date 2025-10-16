<?php

namespace App\Models\Part;

use App\Models\Traits\HasParts;
use App\Models\Traits\HasUser;
use Illuminate\Database\Eloquent\Model;

class UnknownPartNumber extends Model
{
    use HasUser;
    use HasParts;

    protected $guarded = [];
}
