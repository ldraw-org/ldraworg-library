<?php

use App\Settings\LibrarySettings;
use Illuminate\Support\Facades\Storage;

if (!function_exists('version')) {
    function version(string $path): string
    {
        $filePath = public_path($path);
        if (!file_exists($filePath)) {
            return asset($path);
        }

        return asset($path) . "?v=" . filemtime($filePath);
    }
}

if (!function_exists('tracker_locked')) {
    function tracker_locked(): bool
    {
        return app(LibrarySettings::class)->tracker_locked;
    }
}

if (!function_exists('store_backup')) {
    function store_backup(string $filename, string $contents): void
    {
        $filename = time() . "-{$filename}";
        Storage::put("backup/files/{$filename}", $contents);
    }
}
