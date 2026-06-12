<?php

namespace App\Models;

use App\Enums\VoteType;
use App\Models\Traits\HasPart;
use App\Models\Traits\HasUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class Vote extends Model
{
    use HasUser;
    use HasPart;
    use HasFactory;

    /**
    * @return array{
    *     'vote_type': 'App\\Enums\\VoteType',
    * }
    */
    protected function casts(): array
    {
        return  [
            'vote_type' => VoteType::class,
        ];
    }
}
