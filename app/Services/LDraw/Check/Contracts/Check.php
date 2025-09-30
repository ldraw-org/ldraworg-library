<?php

namespace App\Services\LDraw\Check\Contracts;

use App\Services\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

interface Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void;
}
