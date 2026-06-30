<?php

namespace App\Services\Part;

use App\Models\Part\Part;
use Illuminate\Support\Facades\Storage;

class Remover
{
    public function putDeletedBackup(Part $part): void
    {
        $t = time();
        Storage::put("deleted/library/{$part->filename}.{$t}", $part->get());
        Storage::put('deleted/library/' . str_replace(['.png', '.dat'], '.evt', $part->filename). ".{$t}", $part->events->toJson());
    }
}
