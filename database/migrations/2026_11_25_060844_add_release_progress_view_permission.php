<?php

use App\Enums\Permission;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        PermissionModel::findOrCreate(Permission::PartReleaseViewProgress->value);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PermissionModel::where('name', Permission::PartReleaseViewProgress->value)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
