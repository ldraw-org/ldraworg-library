<?php

namespace App\Console\Commands;

use App\Services\User\SyncPermissions;
use Illuminate\Console\Command;

class DeployUpdate extends Command
{
    protected $signature = 'lib:update';

    protected $description = 'Update the app after update deployments';

    public function handle(SyncPermissions $syncPermissions): void
    {
        $permissions = $syncPermissions->handle();

        foreach ($permissions['created'] as $name) {
            $this->info("Created permission {$name}");
        }
        foreach ($permissions['undefined'] as $name) {
            $this->warn("Permission {$name} is no longer defined in App\\Enums\\Permission; remove it by hand if it is unused");
        }
    }
}
