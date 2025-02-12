<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class () extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('library.max_model_render_width', 1200);
        $this->migrator->add('library.max_model_render_height', 1200);
        $this->migrator->rename('library.max_render_width', 'library.max_part_render_width');
        $this->migrator->rename('library.max_render_height', 'library.max_part_render_height');
    }
};
