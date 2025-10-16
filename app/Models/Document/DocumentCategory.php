<?php

namespace App\Models\Document;

use App\Models\Traits\HasOrder;
use App\Observers\DocumentCategoryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(DocumentCategoryObserver::class)]
class DocumentCategory extends Model
{
    use HasOrder;
    use HasFactory;

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

    public function published_documents(): HasMany
    {
        return $this->HasMany(Document::class, 'document_category_id', 'id')->where('published', true);
    }

}
