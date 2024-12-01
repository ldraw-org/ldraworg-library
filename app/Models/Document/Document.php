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

    protected $fillable = [
        'title',
        'nav_title',
        'maintainer',
        'content',
        'published',
        'revision_history',
        'document_category_id',
        'restricted',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'restricted' => 'boolean',
            'published' => 'boolean',
        ];

    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id', 'id');
    }
}
