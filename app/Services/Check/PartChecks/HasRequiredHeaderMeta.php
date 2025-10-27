<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;

class HasRequiredHeaderMeta implements Check
{
    public bool $stopOnError = true;

    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if (is_null($part->description())) {
            $message(PartError::MissingHeaderMeta, value: 'Description');
        }
        if (is_null($part->author())) {
            $message(PartError::MissingHeaderMeta, value: 'Author:');
        }
        if (is_null($part->name())) {
            $message(PartError::MissingHeaderMeta, value: 'Name:');
        }
        if (is_null($part->type())) {
            $message(PartError::MissingHeaderMeta, value: '!LDRAW_ORG');
        }
        if (is_null($part->license())) {
            $message(PartError::MissingHeaderMeta, value: '!LICENSE');
        }
    }
}
