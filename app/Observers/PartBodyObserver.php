<?php

namespace App\Observers;

use App\Models\Part\PartBody;

class PartBodyObserver
{
    public function __construct(
    ) {
    }
    public function saved(PartBody $body): void
    {
    }
}
