<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Models\Document\Document;
use App\Models\Document\DocumentCategory;
use Illuminate\Http\Request;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

class DocumentShowController extends Controller
{
    public function __invoke(Request $request, DocumentCategory $document_category, Document $document)
    {
        Gate::authorize('view', $document);
        
        if ($document->type == DocumentType::Link) {
            return redirect($document->content);
        } elseif ($document->type == DocumentType::Markdown) {
            $doc_content = str($document->content . "\n[TOC]")
                ->markdown(
                    [
                        'default_attributes' => [
                            Heading::class => [
                                'class' => static function (Heading $node) {
                                  return 'py-2 font-bold ' . match ($node->getLevel()) {
                                      1 => 'text-4xl',
                                      2 => 'text-3xl',
                                      3 => 'text-2xl',
                                      4 => 'text-xl',
                                      5 => 'text-lg',
                                      6 => 'text-base',
                                      default => ''
                                  };
                                },
                            ],
                            BlockQuote::class => [
                                'class' => 'p-2 rounded-lg border-l-8 border-green-600 bg-gray-100',                  
                            ],
                            ThematicBreak::class => [
                                'class' => 'border border-gray-300 my-6 rounded',                  
                            ],
                            ListBlock::class => [
                                'class' => static function (ListBlock $node) {
                                    return 'pl-6 ' . match ($node->getListData()->type) {
                                        'bullet' => 'list-disc',
                                        'ordered' => 'list-decimal',
                                        default => ''
                                    };
                                }                  
                            ],
                            ListItem::class => [
                                'class' => '',                  
                            ],
                            FencedCode::class => [
                                'class' => 'whitespace-pre-wrap wrap-break-word font-mono',                  
                            ],
                            Code::class => [
                                'class' => 'whitespace-pre-wrap wrap-break-word font-mono',                  
                            ],
                            Emphasis::class => [
                                'class' => 'italic',                  
                            ],
                            Strong::class => [
                                'class' => 'font-bold',                  
                            ],
                            Link::class => [
                                'class' => 'text-sky-400 underline decoration-dotted hover:decoration-solid',
                                'target' => '_blank',
                            ],
                        ],
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
                            'normalize' => 'flat',
                            'placeholder' => '[TOC]'
                        ],
                    ],
                    [
                        new ExternalLinkExtension(),
                        new HeadingPermalinkExtension(),
                        new TableOfContentsExtension(),
                        new DefaultAttributesExtension(),
                    ]
                )
                ->sanitizeHtml();
            //dd($doc_content);
            $toc = substr($doc_content, strpos($doc_content, '<ul class="table-of-contents">'));
            $doc_content = str_replace($toc, '', $doc_content);
            $toc = str_replace('table-of-contents', 'mx-6 my-0 text-sm mx-4 my-0 space-y-4', $toc);
            $toc = str_replace('<a ', '<a class="underline decoration-dotted hover:decoration-solid"', $toc);          
            return view('documents.document', compact('document', 'doc_content', 'toc'));
        }
        
        return view('documents.document', ['document' => $document, 'doc_content' => $document->content, 'toc' => '']); 
    }
}
