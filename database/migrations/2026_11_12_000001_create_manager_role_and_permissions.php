<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create or retrieve manager role with api guard
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'api']);

        // 2. Clone all permissions from owner role to manager role
        $owner = Role::where(['name' => 'owner', 'guard_name' => 'api'])->first();
        if ($owner) {
            $manager->syncPermissions($owner->permissions);
        } else {
            $manager->syncPermissions(Permission::where('guard_name', 'api')->get());
        }

        // 3. Ensure any existing owner user also receives the manager role for dual compatibility
        $owners = User::role('owner')->get();
        foreach ($owners as $user) {
            if (!$user->hasRole('manager')) {
                $user->assignRole('manager');
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::where(['name' => 'manager', 'guard_name' => 'api'])->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
