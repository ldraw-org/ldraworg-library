<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\Enums\PartTypeQualifier;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FlexibleHasCorrectSuffix implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof Part) {
            $name = basename($part->filename);
        } else {
            $name = str_replace('\\', '/', $part->name);
        }

        $result = preg_match(config('ldraw.patterns.base'), basename($name), $matches);
        if ($part->type_qualifier == PartTypeQualifier::FlexibleSection &&
            (!$result || !Arr::has($matches, 'suffix1') || !Str::startsWith($matches['suffix1'], 'k'))
        ) {
            $fail(PartError::FlexSectionIncorrectSuffix);
        }
    }
}
