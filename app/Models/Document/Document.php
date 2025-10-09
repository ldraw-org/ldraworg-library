<?php

namespace App\Models\Document;

use App\Enums\DocumentType;
use App\Models\Traits\HasOrder;
use App\Observers\DocumentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperDocument
 */
#[ObservedBy(DocumentObserver::class)]
class Document extends Model
{
    use HasOrder;
    use HasFactory;
    
    protected $guarded = [];

    protected $with = [
        'category'
    ];

    protected function casts(): array
    {
        return [
            'restricted' => 'boolean',
            'published' => 'boolean',
            'draft' => 'boolean',
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
