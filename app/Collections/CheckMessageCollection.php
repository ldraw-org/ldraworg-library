<?php

namespace App\Collections;

use App\Services\Check\Traits\InteractsWithCheckMessages;
use Illuminate\Database\Eloquent\Collection;

class CheckMessageCollection extends Collection
{
    use InteractsWithCheckMessages;
}
