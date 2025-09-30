<?php

namespace App\Services\LDraw\Check\Checks;

use App\Enums\PartError;
use App\Services\LDraw\Check\Contracts\Check;
use App\Services\LDraw\Check\Contracts\FilenameAwareCheck;
use App\Services\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class NameFileNameMatch implements Check, FilenameAwareCheck
{
    protected ?string $filename;

    public function setFilename(?string $filename): void
    {
        $this->filename = $filename;
    }

    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof ParsedPart &&
            !is_null($this->filename) &&
            basename(str_replace('\\', '/', $part->name)) !== mb_strtolower($this->filename)
        ) {
            $fail(PartError::NameAndFilenameNotEqual);
        }
    }
}
