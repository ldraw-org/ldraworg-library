<?php

namespace App\Services\Check\Traits;

use App\Services\Parser\ParsedPartCollection;

/* @property ParsedPartAdapter $part */
trait ParsedPartOnly
{
    protected function supports(): array
    {
        return [
            ParsedPartCollection::class,
        ];
    }
}
