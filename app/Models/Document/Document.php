<?php

namespace App\Models\Document;

use App\Models\Traits\HasOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperDocument
 */
class Document extends Model
{
    use HasOrder;

    protected $guarded = [];

    protected $with = [
        'category'
    ];

    protected function casts(): array
    {
        return [
            'restricted' => 'boolean',
            'published' => 'boolean',
        ];

    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id', 'id');
    }
}
