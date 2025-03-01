<?php

namespace App\LDraw\Check\Checks;

use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class MissingHeaderMeta implements Check
{
    public $stopOnError;

    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof Part) {
            return;
        } else {
            $missing = [
                'description' => !is_null($part->description),
                'name' => !is_null($part->name),
                'author' => !is_null($part->username) || !is_null($part->realname),
                'ldraw_org' => !is_null($part->type),
                'license' => !is_null($part->license),
            ];
            foreach ($missing as $meta => $status) {
                if ($status == false) {
                    $fail(__('partcheck.missing', ['attribute' => $meta]));
                }
            }
        }
    }
}
