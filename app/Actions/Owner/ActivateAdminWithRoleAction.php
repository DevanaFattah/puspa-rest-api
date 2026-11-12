<?php

namespace App\Actions\Owner;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class ActivateAdminWithRoleAction
{
    /**
     * Activate an admin user account and assign the selected admin role.
     *
     * @param User $user
     * @param string|null $roleNameOrId
     * @return User
     */
    public function execute(User $user, ?string $roleNameOrId = null): User
    {
        return DB::transaction(function () use ($user, $roleNameOrId) {
            $user->update([
                'is_active' => true,
            ]);

            if (!empty($roleNameOrId)) {
                // Determine target role by id or name
                $targetRole = Role::where('name', $roleNameOrId)
                    ->orWhere('id', $roleNameOrId)
                    ->first();

                // Map UI role IDs if applicable (e.g. "keuangan" => "Admin Keuangan")
                if (!$targetRole) {
                    $aliases = [
                        'keuangan' => 'Admin Keuangan',
                        'presensi' => 'Admin Presensi',
                        'layanan' => 'Admin Layanan',
                    ];
                    if (isset($aliases[strtolower($roleNameOrId)])) {
                        $targetRole = Role::where('name', $aliases[strtolower($roleNameOrId)])->first();
                    }
                }

                if ($targetRole) {
                    // Assign target role and ensure base 'admin' capability is present
                    $rolesToSync = array_unique([$targetRole->name, 'admin']);
                    $user->syncRoles($rolesToSync);
                } else {
                    // Fallback to default admin role
                    if (!$user->hasRole('admin')) {
                        $user->assignRole('admin');
                    }
                }
            } else {
                if (!$user->hasRole('admin')) {
                    $user->assignRole('admin');
                }
            }

            return $user->fresh(['roles', 'admin']);
        });
    }
}

