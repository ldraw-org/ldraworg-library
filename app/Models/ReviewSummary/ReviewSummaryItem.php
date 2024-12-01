<?php

namespace App\Models\ReviewSummary;

use App\Models\Traits\HasOrder;
use App\Models\Traits\HasPart;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperReviewSummaryItem
 */
class ReviewSummaryItem extends Model
{
    use HasPart;
    use HasOrder;

    protected $fillable = [
        'order',
        'review_summary_id',
        'part_id',
        'heading',
    ];

    protected $with = ['part'];

    public function review_summary(): BelongsTo
    {
        return $this->belongsTo(ReviewSummary::class);
    }

    public function toString(): string
    {
        if (is_null($this->part) && is_null($this->heading)) {
            return '/';
        }
        if (is_null($this->part)) {
            return "/ {$this->heading}";
        }

        return $this->part->filename;
    }
}
