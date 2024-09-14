<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        Permission::create(['name' => 'part.submit.regular']);
        Permission::create(['name' => 'part.submit.proxy']);
        Permission::create(['name' => 'part.submit.fix']);

        Permission::create(['name' => 'part.edit.header']);
        Permission::create(['name' => 'part.edit.number']);

        Permission::create(['name' => 'part.delete']);

        Permission::create(['name' => 'part.vote.certify']);
        Permission::create(['name' => 'part.vote.hold']);
        Permission::create(['name' => 'part.vote.admincertify']);
        Permission::create(['name' => 'part.vote.fasttrack']);

        Permission::create(['name' => 'part.comment']);

        Permission::create(['name' => 'part.own.vote.hold']);
        Permission::create(['name' => 'part.own.vote.certify']);
        Permission::create(['name' => 'part.own.comment']);
        Permission::create(['name' => 'part.own.edit.header']);

        Permission::create(['name' => 'user.add']);
        Permission::create(['name' => 'user.add.nonadmin']);
        Permission::create(['name' => 'user.delete']);

        Permission::create(['name' => 'user.modify']);
        Permission::create(['name' => 'user.modify.email']);
        Permission::create(['name' => 'user.modify.role.nonadmin']);

        Permission::create(['name' => 'user.view.email']);

        Permission::create(['name' => 'role.add']);
        Permission::create(['name' => 'role.modify']);
        Permission::create(['name' => 'role.delete']);

        // Wildcard permissions
        Permission::create(['name' => 'user']);
        Permission::create(['name' => 'role']);
        Permission::create(['name' => 'part']);

    }
}
