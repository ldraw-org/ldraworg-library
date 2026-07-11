<?php

namespace App\Services\Support\Enums;

enum ReleaseOutput: string
{
    case Tab = 'tab';
    case Xml = 'xml';

    public function contentType(): string
    {
        return match ($this) {
            ReleaseOutput::Tab => 'text/plain; charset=utf-8',
            ReleaseOutput::Xml => 'application/xml; charset=utf-8',
        };
    }
}
