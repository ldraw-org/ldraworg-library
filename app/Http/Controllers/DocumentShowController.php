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
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;

class DocumentShowController extends Controller
{
    public function __construct(
        protected MarkdownService $markdownService
    )
    {}

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
