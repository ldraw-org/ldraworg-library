<?php

namespace App\Services\Check\PartChecks;

use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartError;

class ValidBodyMeta extends BaseCheck
{
    public function check(): iterable
    {
        $invalidMeta = $this->part->bodyLines()
            ->whereNotIn('meta', [
                'comment',
                'texmap',
                'texmap_geometry',
                'bfc',
            ])
            ->where('linetype', 0);
        foreach ($invalidMeta as $line) {
            yield $this->error(PartError::InvalidLineType0, $line['line_number']);
        }
    }
}
