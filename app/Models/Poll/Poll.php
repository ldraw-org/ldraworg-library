<?php

namespace App\Models\Poll;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Unguarded]
class Poll extends Model
{
    public function items(): HasMany
    {
        return $this->hasMany(PollItem::class, 'poll_id', 'id');
    }

}
