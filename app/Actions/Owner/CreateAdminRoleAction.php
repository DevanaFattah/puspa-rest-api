<?php

namespace App\Actions\Owner;

use App\Support\AdminMenuPermissionMap;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateAdminRoleAction
{
    /**
     * Create a new custom admin role and assign selected menu permissions.
     */
    public function execute(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'api',
            ]);

            $menuIds = $data['checkedIds'] ?? AdminMenuPermissionMap::WAJIB_LEAF_IDS;
            $permissionNames = AdminMenuPermissionMap::menuIdsToPermissions($menuIds);

            $permissions = collect($permissionNames)->map(function ($permName) {
                return Permission::firstOrCreate([
                    'name' => $permName,
                    'guard_name' => 'api',
                ]);
            });

            $role->syncPermissions($permissions);
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return $role;
        });
    }
}

