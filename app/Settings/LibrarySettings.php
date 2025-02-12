<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class LibrarySettings extends Settings
{
    public array $ldview_options;
    public array $default_render_views;

    public int $max_part_render_height;
    public int $max_part_render_width;

    public int $max_model_render_height;
    public int $max_model_render_width;

    public int $max_thumb_height;
    public int $max_thumb_width;

    public array $allowed_header_metas;
    public array $allowed_body_metas;

    public string $default_part_license;
    public int $quick_search_limit;

    public array $pattern_codes;

    public bool $tracker_locked;

    public static function group(): string
    {
        return 'library';
    }
}
