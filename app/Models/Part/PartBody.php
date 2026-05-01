<?php

namespace App\Models\Part;

use App\Observers\PartBodyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(PartBodyObserver::class)]
class PartBody extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class, 'part_id', 'id');
    }
}
