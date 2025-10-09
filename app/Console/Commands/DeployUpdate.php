<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Enums\Permission;
use Spatie\Permission\Models\Permission as PermissionModel;

class DeployUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the app after update deployments';

    /**
     * Execute the console command.
     */
    public function handle(LibraryImport $import): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value);
        }
    }
}
