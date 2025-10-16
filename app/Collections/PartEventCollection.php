<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;
use App\Collections\Traits\HasRelease;

/**
 * @extends \Illuminate\Database\Eloquent\Collection<int|string, \App\Models\Part\PartEvent>
 */
class PartEventCollection extends Collection
{
    use HasRelease;
}
