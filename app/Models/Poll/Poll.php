<?php

namespace App\Models\Poll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    protected $guarded = [];

    public function items(): HasMany
    {
        return $this->hasMany(PollItem::class, 'poll_id', 'id');
    }

}
