<?php

namespace App\Http\Controllers;

use App\Models\Document\DocumentCategory;

class DocumentIndexController extends Controller
{
    public function __invoke()
    {
        $categories = DocumentCategory::with('documents')->has('published_documents')->ordered()->get();

        return view('documents.index', compact('categories'));
    }
}
