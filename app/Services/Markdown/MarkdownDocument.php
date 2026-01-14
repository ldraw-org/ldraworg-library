<?php

namespace App\Services\Markdown;

use Illuminate\Support\HtmlString;

class MarkdownDocument
{
    public function __construct(
        public readonly HtmlString $content,
        public readonly HtmlString $toc
    )
    {}
}
