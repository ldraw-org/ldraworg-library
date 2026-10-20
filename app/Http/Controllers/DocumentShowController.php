<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Models\Document\Document;
use App\Models\Document\DocumentCategory;
use App\Services\Markdown\MarkdownService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DocumentShowController extends Controller
{
    public function __construct(
        protected MarkdownService $markdownService
    ) {
    }

    public function __invoke(Request $request, DocumentCategory $document_category, Document $document): View|RedirectResponse
    {
        Gate::authorize('view', $document);

        if ($document->type == DocumentType::Link) {
            return redirect($document->content);
        } elseif ($document->type == DocumentType::Markdown) {
            $html = $this->markdownService->parse($document->content, true);
            return view('documents.document', ['document' => $document, 'doc_content' => $html->content, 'toc' => $html->toc]);
        }

        return view('documents.document', ['document' => $document, 'doc_content' => $document->content, 'toc' => '']);
    }
}
