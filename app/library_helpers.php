<?php

use App\Settings\LibrarySettings;

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
