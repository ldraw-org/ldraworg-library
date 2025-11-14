<?php

namespace App\Services\Check\Traits;

use App\Models\Part\Part;

/* @property PartModelAdapter $part */
trait PartOnly
{
    protected function supports(): array
    {
        return [
            Part::class,
        ];
    }
}
