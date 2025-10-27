<?php

namespace App\Services\Check\Contracts;

use App\Services\Parser\ParsedPartCollection;
use Closure;

interface Check
{
    public function check(ParsedPartCollection $part, Closure $message): void;
}
