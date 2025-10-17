<?php

namespace App\Http\Controllers;

use App\Models\Document\DocumentCategory;
use Illuminate\View\View;

class DocumentIndexController extends Controller
{
    public function __invoke(): View
    {
        $categories = DocumentCategory::with('documents')->has('published_documents')->ordered()->get();

        return view('documents.index', compact('categories'));
    }
}
