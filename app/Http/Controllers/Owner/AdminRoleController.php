<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Owner\CreateAdminRoleAction;
use App\Actions\Owner\DeleteAdminRoleAction;
use App\Actions\Owner\UpdateAdminRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Requests\Owner\StoreAdminRoleRequest;
use App\Http\Requests\Owner\UpdateAdminRoleRequest;
use App\Models\User;
use App\Support\AdminMenuPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class AdminRoleController extends Controller
{
    use ResponseFormatter;

    /**
     * Display a listing of admin roles with checkedIds and user count.
     */
    public function index(): JsonResponse
    {
        // Exclude clinical/parent/school/executive roles to focus on Admin variations
        $roles = Role::with('permissions')
            ->whereNotIn('name', ['owner', 'manager', 'asesor', 'terapis', 'user', 'sekolah'])
            ->get();

        $data = $roles->map(function ($role) {
            $permissionNames = $role->permissions->pluck('name')->toArray();
            $checkedIds = AdminMenuPermissionMap::permissionsToMenuIds($permissionNames);
            $usersCount = DB::table('model_has_roles')->where('role_id', $role->id)->count();

            return [
                'id' => (string) $role->id,
                'name' => $role->name,
                'desc' => $role->name === 'admin' ? 'Akses penuh seluruh modul operasional admin.' : "Mengelola tugas {$role->name}.",
                'checkedIds' => $checkedIds,
                'users_count' => $usersCount,
            ];
        });

        return $this->successResponse($data, 'Daftar Jenis Admin Berhasil Diambil', 200);
    }

    /**
     * Store a newly created admin role.
     */
    public function store(StoreAdminRoleRequest $request, CreateAdminRoleAction $action): JsonResponse
    {
        $role = $action->execute($request->validated());
        $permissionNames = $role->permissions->pluck('name')->toArray();

        return $this->successResponse([
            'id' => (string) $role->id,
            'name' => $role->name,
            'desc' => "Mengelola tugas {$role->name}.",
            'checkedIds' => AdminMenuPermissionMap::permissionsToMenuIds($permissionNames),
        ], "Jenis admin '{$role->name}' berhasil dibuat", 201);
    }

    /**
     * Update the specified admin role.
     */
    public function update(UpdateAdminRoleRequest $request, Role $role, UpdateAdminRoleAction $action): JsonResponse
    {
        $updated = $action->execute($role, $request->validated());
        $permissionNames = $updated->permissions->pluck('name')->toArray();

        return $this->successResponse([
            'id' => (string) $updated->id,
            'name' => $updated->name,
            'checkedIds' => AdminMenuPermissionMap::permissionsToMenuIds($permissionNames),
        ], 'Hak akses berhasil disimpan', 200);
    }

    /**
     * Remove the specified admin role.
     */
    public function destroy(Role $role, DeleteAdminRoleAction $action): JsonResponse
    {
        $roleName = $role->name;
        $action->execute($role);

        return $this->successResponse([], "Jenis admin '{$roleName}' berhasil dihapus", 200);
    }

    /**
     * Get list of admin users assigned to this role ("siapa saja yang terlibat").
     */
    public function users(Role $role): JsonResponse
    {
        $users = User::role($role->name)->with('admin')->get();

        $data = $users->map(function (User $user) {
            return [
                'user_id' => $user->id,
                'admin_id' => $user->admin?->id,
                'admin_name' => $user->admin?->admin_name ?? $user->username,
                'email' => $user->email,
                'admin_phone' => $user->admin?->admin_phone,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at?->format('d F Y H:i:s'),
            ];
        });

        return $this->successResponse($data, "Daftar pengguna dengan peran '{$role->name}'", 200);
    }
}

