<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;
use Illuminate\Support\Str;

class UnknownPartNumber implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        $name = basename(str_replace('\\', '/', $part->name()));
        if (Str::startsWith($name, 'x')) {
            $message(PartError::UnknownPartNumberName);
        }
    }
}
