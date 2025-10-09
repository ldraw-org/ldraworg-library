<?php

namespace App\Observers;

use App\Models\Document\Document;
use Illuminate\Support\Str;

class DocumentObserver
{
    public function saving(Document $document): void
    {
        if ($document->isDirty('title')) {
            $document->slug = Str::slug($document->title);
        }
    }
}
