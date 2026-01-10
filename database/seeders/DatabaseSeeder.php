<?php

namespace Database\Seeders;

use App\Enums\License;
use App\Models\User;
use App\Enums\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value);
        }
        Role::findOrCreate('Super Admin');
        $user = User::updateOrCreate(
            [
                'name' => 'admin',
            ],
            [
                'realname' => 'admin',
                'password' => Str::random(32),
                'email' => 'admin@admin.com',
                'license' => License::CC_BY_4,
                'ca_confirm' => true,
            ]
        );
        $user->assignRole('Super Admin');
    }
}
