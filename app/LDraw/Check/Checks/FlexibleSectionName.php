<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartTypeQualifier;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;
use Illuminate\Support\Str;

class FlexibleSectionName implements Check
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
            (!$result || (!Str::startsWith($matches[5], 'k') && !Str::startsWith($matches[6], 'k') && !Str::startsWith($matches[7], 'k')))
        ) {
            $fail(__('partcheck.type.flexname'));
        }
    }
}
