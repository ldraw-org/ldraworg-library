<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartBody extends Model
{
    protected $fillable = [
        'body',
        'part_id',
    ];

    public $timestamps = false;
    
    public function body(): BelongsTo 
    {
        return $this->belongsTo(Part::class, 'part_id', 'id');
    }
}
