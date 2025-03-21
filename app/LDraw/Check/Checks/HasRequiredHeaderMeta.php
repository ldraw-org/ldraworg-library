<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class HasRequiredHeaderMeta implements Check
{
    public bool $stopOnError = true;

    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof Part) {
            return;
        } else {
            $missing = [
                'Description' => !is_null($part->description),
                'Name' => !is_null($part->name),
                'Author' => !is_null($part->username) || !is_null($part->realname),
                '!LDRAW_ORG' => !is_null($part->type),
                '!LICENSE' => !is_null($part->license),
            ];
            foreach ($missing as $meta => $status) {
                if ($status == false) {
                    $fail(PartError::MissingHeaderMeta, ['attribute' => $meta]);
                }
            }
        }
    }
}
