<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;

class ValidBodyMeta extends BaseCheck
{
    public function check(): iterable
    {
        $invliadMeta = $this->part->bodyLines()
            ->whereNotIn('meta', [
                'comment',
                'texmap',
                'texmap_geometry',
                'bfc',
            ])
            ->where('linetype', 0);
        foreach ($invliadMeta as $line) {
            yield $this->error(CheckType::Error, PartError::InvalidLineType0, $line['line_number']);
        }
    }
}
