<?php

namespace App\Models\Document;

use App\Models\Traits\HasOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperDocumentCategory
 */
class DocumentCategory extends Model
{
    use HasOrder;

    protected $guarded = [];

    public $timestamps = false;

    public function getRouteKeyName()
    {
        return 'slug';
    }
    
    public function documents(): HasMany
    {
        return $this->HasMany(Document::class, 'document_category_id', 'id');
    }

}
