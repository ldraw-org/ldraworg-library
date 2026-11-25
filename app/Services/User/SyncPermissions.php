<?php

namespace App\Services\User;

use App\Enums\Permission;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates every permission the Permission enum defines but the database lacks.
 * Permissions the enum no longer defines are only reported: deleting one would silently strip it from every role.
 */
class SyncPermissions
{
    public function __construct(
        protected PermissionRegistrar $registrar,
    ) {
    }

    /**
     * @return array{created: list<string>, undefined: list<string>}
     */
    public function handle(): array
    {
        $this->registrar->forgetCachedPermissions();

        $defined = array_map(fn (Permission $permission): string => $permission->value, Permission::cases());
        $stored = PermissionModel::pluck('name')->all();

        $created = array_values(array_diff($defined, $stored));
        foreach ($created as $name) {
            PermissionModel::findOrCreate($name);
        }

        if ($created !== []) {
            $this->registrar->forgetCachedPermissions();
        }

        return [
            'created' => $created,
            'undefined' => array_values(array_diff($stored, $defined)),
        ];
    }
}
