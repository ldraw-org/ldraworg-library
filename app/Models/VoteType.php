<?php

namespace App\Models;

use App\Models\Traits\HasOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoteType extends Model
{
    use HasOrder;

    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'code',
        'short',
        'name',
        'phrase',
        'order',
    ];

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'vote_type_code', 'code');
    }

}
