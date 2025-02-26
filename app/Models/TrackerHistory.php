<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

/**
 * @mixin IdeHelperTrackerHistory
 */
class TrackerHistory extends Model
{

    protected $guarded = [];

    /**
    * @return array{
    *     history_data: 'Illuminate\Database\Eloquent\Casts\AsArrayObject',
    * }
    */
    protected function casts(): array
    {
        return [
            'history_data' => AsArrayObject::class,
        ];
    }
}
