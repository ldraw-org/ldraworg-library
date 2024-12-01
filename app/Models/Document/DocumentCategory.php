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

    protected $fillable = [
        'category',
        'order'
    ];

    public $timestamps = false;

    public function documents(): HasMany
    {
        return $this->HasMany(Document::class, 'document_category_id', 'id');
    }

}
