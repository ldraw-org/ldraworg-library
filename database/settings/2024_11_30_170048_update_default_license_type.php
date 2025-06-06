<?php

use App\Enums\License;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class () extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->rename('library.default_part_license_id', 'library.default_part_license');
        $this->migrator->update(
            'library.default_part_license',
            fn (int $l) => License::CC_BY_4
        );
    }
};
