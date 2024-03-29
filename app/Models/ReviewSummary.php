<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewSummary extends Model
{
    protected $fillable = [
        'header',
        'order',
    ];
    
    protected $with = ['items'];

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
