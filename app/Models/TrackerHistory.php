<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

#[Unguarded]
class TrackerHistory extends Model
{
    /**
    * @return array{
    *     'history_data': 'Illuminate\\Database\\Eloquent\\Casts\\AsArrayObject',
    * }
    */
    protected function casts(): array
    {
        return [
            'history_data' => AsArrayObject::class,
        ];
    }
}
