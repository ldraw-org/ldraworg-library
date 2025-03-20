<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class NameAndPartType implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof ParsedPart &&
            !is_null($part->type) &&
            !is_null($part->name) &&
            !$part->type->isImageFormat() &&
            str_replace('\\', '/', $part->name) !== str_replace(['p/', 'parts/'], '', $part->type->folder() . '/' . basename(str_replace('\\', '/', $part->name)))
        ) {
            $fail(PartError::NameTypeMismatch, ['name' => str_replace('\\', '/', $part->name), 'type' => $part->type->value]);
        }
    }
}
