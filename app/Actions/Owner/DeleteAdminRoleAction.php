<?php

namespace App\Actions\Owner;

use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class DeleteAdminRoleAction
{
    /**
     * Delete an admin role if it is not a protected system role.
     */
    public function execute(Role $role): void
    {
        $protectedRoles = ['owner', 'admin', 'asesor', 'terapis', 'user', 'sekolah'];

        if (in_array(strtolower($role->name), $protectedRoles)) {
            throw ValidationException::withMessages([
                'role' => ['Role bawaan sistem tidak dapat dihapus.'],
            ]);
        }

        $role->delete();
    }
}

