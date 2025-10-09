<?php

namespace App\Models\Document;

use App\Enums\DocumentType;
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
            'type' => DocumentType::class,
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
