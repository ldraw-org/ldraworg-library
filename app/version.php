<?php

function version($path): string
{
    $filePath = public_path($path);
    if (!file_exists($filePath)) {
        return asset($path);
    }

    return asset($path) . "?v=" . filemtime($filePath);
}
