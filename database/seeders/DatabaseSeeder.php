<?php

namespace Database\Seeders;

use App\Settings\LibrarySettings;
use App\Enums\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $ls = app(LibrarySettings::class);
        $ls->allowed_header_metas = [
                'Name:',
                'Author:',
                '!LDRAW_ORG',
                '!LICENSE',
                '!HELP',
                'BFC',
                '!CATEGORY',
                '!KEYWORDS',
                '!CMDLINE',
                '!HISTORY'
            ];
        $ls->allowed_body_metas = [
                '!TEXMAP',
                '!:',
                'BFC',
                '//',
            ];

        $ls->save();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (Permission::cases() as $permission) {
            PermissionModel::create(['name' => $permission->value]);
        }
    }
}
