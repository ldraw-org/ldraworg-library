<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;

enum DocumentType: string
{
    use CanBeOption;
    
    case Markdown = 'Markdown';
    case Html = 'HTML';
    case Link = 'Link';
}
