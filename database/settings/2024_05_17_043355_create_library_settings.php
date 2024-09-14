<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class () extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('library.ldview_options', []);

        $this->migrator->add('library.max_render_height', 300);
        $this->migrator->add('library.max_render_width', 300);

        $this->migrator->add('library.max_thumb_height', 300);
        $this->migrator->add('library.max_thumb_width', 300);

        $this->migrator->add('library.allowed_header_metas', []);
        $this->migrator->add('library.allowed_body_metas', []);

        $this->migrator->add('library.default_part_license_id', 1);

        $this->migrator->add('library.quick_search_limit', 7);
        $this->migrator->add('library.pattern_codes', []);
    }
};
