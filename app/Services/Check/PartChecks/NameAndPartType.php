<?php

namespace App\Services\Check\PartChecks;

use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartError;

class NameAndPartType extends BaseCheck
{
    public function check(): iterable
    {
        $name = str_replace('\\', '/', $this->part->name());
        $file = $this->part->type()?->nameFolder() == '' ? basename($name) : $this->part->type()?->nameFolder() . '\\' . basename($name);
        if ($file !== $this->part->name()) {
            yield $this->error(PartError::NameTypeMismatch, value: $this->part->name(), type: $this->part->type()?->value);
        }
    }
}
