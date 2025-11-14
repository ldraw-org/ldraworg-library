<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;
use Illuminate\Support\Str;

class UnknownPartNumber extends BaseCheck
{
    public function check(): iterable
    {
        $name = basename(str_replace('\\', '/', $this->part->name()));
        if (Str::startsWith($name, 'x')) {
            yield $this->error(CheckType::Error, PartError::UnknownPartNumberName);
        }
    }
}
