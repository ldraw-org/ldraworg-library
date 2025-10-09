<?php

namespace App\Observers;

use App\Models\Document\DocumentCategory;
use Illuminate\Support\Str;

class DocumentCategoryObserver
{
    public function saving(DocumentCategory $category): void
    {
        if ($category->isDirty('title')) {
            $category->slug = Str::slug($category->title);
        }
    }
}
