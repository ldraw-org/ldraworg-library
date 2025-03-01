<?php

namespace App\LDraw\Check\Contracts;

use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

interface Check
{
    public function check(ParsedPart|Part $part, Closure $message): void;
}
