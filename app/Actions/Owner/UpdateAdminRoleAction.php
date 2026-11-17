<?php

namespace App\Actions\Owner;

use App\Support\AdminMenuPermissionMap;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UpdateAdminRoleAction
{
    /**
     * Update an admin role's name and its assigned menu permissions.
     */
    public function execute(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            if (!empty($data['name'])) {
                $role->name = $data['name'];
                $role->save();
            }

            if (isset($data['checkedIds'])) {
                $permissionNames = AdminMenuPermissionMap::menuIdsToPermissions($data['checkedIds']);
                $permissions = collect($permissionNames)->map(function ($permName) {
                    return Permission::firstOrCreate([
                        'name' => $permName,
                        'guard_name' => 'api',
                    ]);
                });
                $role->syncPermissions($permissions);
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            }

            return $role->fresh(['permissions']);
        });
    }
}

