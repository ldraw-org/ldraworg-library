<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;

class NameAndPartType implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        $name = str_replace('\\', '/', $part->name());
        $file = $part->type()?->nameFolder() == '' ? basename($name) : $part->type()?->nameFolder() . '\\' . basename($name);
        if ($file !== $part->name()) {
            $message(error: PartError::NameTypeMismatch, value: $part->name(), type: $part->type()?->value);
        }
    }
}
