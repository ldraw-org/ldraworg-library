<?php

namespace App\Services\Check\PartChecks;

use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartError;
use Illuminate\Support\Str;

class UnknownPartNumber extends BaseCheck
{
    public function check(): iterable
    {
        $name = basename(str_replace('\\', '/', $this->part->name()));
        if (Str::startsWith($name, 'x')) {
            yield $this->error(PartError::UnknownPartNumberName);
        }
    }
}
