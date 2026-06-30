<?php

namespace App\Services\Part;

use App\Models\Part\Part;

class ToggleManualHold
{
    public function handle(Part $part): void
    {
        // Note: this does not consider the case where a part has 2 ancestor that
        // toggle differently.  The last toggle wins.
        $part->descendantsandSelf()
            ->unofficial()
            ->update(['manual_hold_flag' => !$part->manual_hold_flag]);
    }
}
