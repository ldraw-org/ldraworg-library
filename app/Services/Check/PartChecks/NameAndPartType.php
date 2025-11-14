<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;

class NameAndPartType extends BaseCheck
{
    public function check(): iterable
    {
        $name = str_replace('\\', '/', $this->part->name());
        $file = $this->part->type()?->nameFolder() == '' ? basename($name) : $this->part->type()?->nameFolder() . '\\' . basename($name);
        if ($file !== $this->part->name()) {
            yield $this->error(CheckType::Error, error: PartError::NameTypeMismatch, value: $this->part->name(), type: $this->part->type()?->value);
        }
    }
}
