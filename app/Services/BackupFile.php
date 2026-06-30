<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class BackupFile
{
    public function handle(string $filename, string $contents): void
    {
        $filename = time() . "-{$filename}";
        Storage::put("backup/files/{$filename}", $contents);
    }
}
