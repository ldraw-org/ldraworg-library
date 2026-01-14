<?php

namespace App\Services\Markdown;

use Illuminate\Support\HtmlString;
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

class MarkdownService
{
    public function parse(string $content, bool $withToc = false): MarkdownDocument
    {
        $markdown = $withToc ? $content . "\n[TOC]" : $content;

        $html = (string) str($markdown)
            ->markdown($this->getOptions(), $this->getExtensions($withToc))
            ->sanitizeHtml();

        // If no TOC was requested, or the extension didn't find headings
        if (!$withToc || !str_contains($html, '<ul class="table-of-contents">')) {
            return new MarkdownDocument(
                new HtmlString($html),
                new HtmlString('')
            );
        }

        $tocStart = strpos($html, '<ul class="table-of-contents">');
        $tocHtml  = substr($html, $tocStart);
        $bodyHtml = str_replace($tocHtml, '', $html);

        return new MarkdownDocument(
            new HtmlString(trim($bodyHtml)),
            new HtmlString($this->styleToc($tocHtml)),
        );
    }

    protected function styleToc(string $toc): string
    {
        return (string) str($toc)
            ->replace('table-of-contents', 'mx-6 my-0 text-sm space-y-4')
            ->replace('<a ', '<a class="underline decoration-dotted hover:decoration-solid" ');
    }

    protected function getExtensions(bool $withToc): array
    {
        $exts = [
            new ExternalLinkExtension(),
            new HeadingPermalinkExtension(),
            new DefaultAttributesExtension(),
        ];

        if ($withToc) {
            $exts[] = new TableOfContentsExtension();
        }

        return $exts;
    }
    protected function getOptions(): array
    {
        return                     [
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
        ];
    }
}
