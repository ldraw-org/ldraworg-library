<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;
use App\Services\Check\Contracts\FilenameAwareCheck;
use App\Services\Check\Traits\ParsedPartOnly;
use Illuminate\Support\Str;

class NameFileNameMatch extends BaseCheck implements FilenameAwareCheck
{
    use ParsedPartOnly;
    
    protected ?string $filename = null;

    public function setFilename(?string $filename): void
    {
        $this->filename = is_string($filename) ? Str::lower($filename) : null;
    }

    public function check(): iterable
    {
        if (is_null($this->filename)) {
            return;
        }
        $name = basename(str_replace('\\', '/', $this->part->name()));
        if ($name !== $this->filename) {
            yield $this->error(CheckType::Error, PartError::NameAndFilenameNotEqual);
        }
    }
}
