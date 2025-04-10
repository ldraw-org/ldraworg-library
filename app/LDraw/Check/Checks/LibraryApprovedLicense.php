<?php

namespace App\LDraw\Check\Checks;

use App\Enums\License;
use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class LibraryApprovedLicense implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part->license != License::CC_BY_4) {
            $fail(PartError::LicenseNotLibraryApproved, ['license' => $part->license->value]);
        }
    }
}
