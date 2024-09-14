<?php

namespace App\Models\ReviewSummary;

use App\Models\Traits\HasOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewSummary extends Model
{
    use HasOrder;

    protected $fillable = [
        'header',
        'order',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ReviewSummaryItem::class, 'review_summary_id', 'id')->orderBy('order');
    }

    public function toString(): string
    {
        $text = '';
        foreach ($this->items()->with('part')->orderBy('order')->get() as $item) {
            $text .= "{$item->toString()}\n";
        }
        return $text;
    }
}
