<?php

namespace App\Services\Part;

use App\Models\Part\Part;
use App\Models\Part\UnknownPartNumber;

class SyncUnknownPartNumber
{
    public function handle(Part $p): void
    {
        $result = preg_match('/parts\/u([0-9]{4}).*\.dat/', $p->filename, $matches);
        if ($result) {
            $number = $matches[1];
            $unk = UnknownPartNumber::firstOrCreate(
                ['number' => $number],
                ['user_id' => $p->user_id]
            );
            $p->unknown_part_number->associate($unk);
        } else {
            $p->unknown_part_number?->dissociate();
        }
    }

}
