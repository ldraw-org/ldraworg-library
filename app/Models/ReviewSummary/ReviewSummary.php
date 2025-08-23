<?php

namespace App\Models\ReviewSummary;

use App\Models\Part\Part;
use App\Models\Traits\HasOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * @mixin IdeHelperReviewSummary
 */
class ReviewSummary extends Model
{
    use HasOrder;

    protected $guarded = [];

    public function items(): HasMany
    {
        return $this->hasMany(ReviewSummaryItem::class, 'review_summary_id', 'id')->orderBy('order');
    }

    public function parts(): Collection
    {
        $parts = [];
        
        foreach (explode("\n", $this->list) as $item) {
            if (Str::of($item)->trim()->doesntStartWith('/')) {
                $parts[] = $item;
            }
        }
        
        return Part::doesntHave('unofficial_part')->whereIn('filename', $parts)->get();
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
