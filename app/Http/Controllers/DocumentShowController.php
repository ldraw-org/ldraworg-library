<?php

namespace App\Http\Controllers;

use App\Models\Document\Document;
use Illuminate\Http\Request;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;

class DocumentShowController extends Controller
{
    public function __invoke(Request $request, Document $document)
    {
        $doc_content = str($document->content . "\n[TOC]")
            ->markdown(
                [
                    'external_link' => [
                        'internal_hosts' => 'library.ldraw.org',
                        'open_in_new_window' => true,
                        'html_class' => 'external-link',
                        'nofollow' => '',
                        'noopener' => 'external',
                        'noreferrer' => 'external',
                    ],
                    'heading_permalink' => [
                        'html_class' => 'heading-permalink',
                        'insert' => 'before',
                        'symbol' => '',
                        'title' => "Permalink",
                    ],
                    'table_of_contents' => [
                        'position' => 'placeholder',
                        'placeholder' => '[TOC]'
                    ],
                ],
                [
                    new ExternalLinkExtension(),
                    new HeadingPermalinkExtension(),
                    new TableOfContentsExtension(),
                ]
            )
            ->sanitizeHtml();
        $toc = substr($doc_content, strpos($doc_content, '<ul class="table-of-contents">'));
        $doc_content = str_replace($toc, '', $doc_content);
        return view('documents.document', compact('document', 'doc_content', 'toc'));
    }
}
