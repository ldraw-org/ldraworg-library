<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Check\Contracts\FilenameAwareCheck;
use App\Services\Parser\ParsedPartCollection;
use Illuminate\Support\Str;
use Closure;

class NameFileNameMatch implements Check, FilenameAwareCheck
{
    protected ?string $filename = null;

    public function setFilename(?string $filename): void
    {
        $this->filename = is_string($filename) ? Str::lower($filename) : null;
    }

    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if (is_null($this->filename)) {
            return;
        }
        $name = basename(str_replace('\\', '/', $part->name()));
        if ($name !== $this->filename) {
            $message(PartError::NameAndFilenameNotEqual);
        }
    }
}
