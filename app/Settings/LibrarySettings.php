<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class LibrarySettings extends Settings
{
    public array $ldview_options = [];
    public array $default_render_views = [];

    public int $max_part_render_height = 300;
    public int $max_part_render_width = 300;

    public int $max_model_render_height = 1200;
    public int $max_model_render_width = 1200;

    public int $max_thumb_height = 75;
    public int $max_thumb_width = 35;

    public array $allowed_header_metas = [
        "Name:",
        "Author:",
        "!LDRAW_ORG",
        "!LICENSE",
        "!HELP",
        "BFC",
        "!CATEGORY",
        "!KEYWORDS",
        "!CMDLINE",
        "!HISTORY",
        "!PREVIEW",
    ];
    public array $allowed_body_metas = ['!TEXMAP', '!:', '//', 'BFC'];

    public string $default_part_license = 'CC_BY_4';
    public int $quick_search_limit = 7;

    public array $pattern_codes = [];

    public bool $tracker_locked = false;

    public static function group(): string
    {
        return 'library';
    }
}
