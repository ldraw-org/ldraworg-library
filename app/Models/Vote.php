<?php

namespace App\Models;

use App\Enums\VoteType;
use App\Models\Traits\HasPart;
use App\Models\Traits\HasUser;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperVote
 */
class Vote extends Model
{
    use HasUser;
    use HasPart;

    protected $guarded = [];

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
