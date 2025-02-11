<?php

namespace App\Models\Poll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollItem extends Model
{
    protected $guarded = [];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class, 'poll_id', 'id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class, 'poll_item_id', 'id');
    }

}
