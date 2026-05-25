<?php

namespace App\Models\Part;

use App\Models\Traits\HasParts;
use App\Models\Traits\HasUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class UnknownPartNumber extends Model
{
    use HasUser;
    use HasParts;
}
