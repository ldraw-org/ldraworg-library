<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;
use App\Collections\Traits\HasRelease;

class PartEventCollection extends Collection
{
    use HasRelease;
}
