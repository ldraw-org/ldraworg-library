<?php

namespace App\Models\Part;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartEventType extends Model
{
    public $fillable = [
        'slug',
        'name',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(PartEvent::class);
    }
}
