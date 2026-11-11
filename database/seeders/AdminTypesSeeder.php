<?php

namespace Database\Seeders;

use App\Support\AdminMenuPermissionMap;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $defaultAdminTypes = [
            [
                'name' => 'Admin Keuangan',
                'checkedIds' => ['dashboard', 'admin', 'keuangan', 'ubah_profile', 'ubah_password'],
            ],
            [
                'name' => 'Admin Presensi',
                'checkedIds' => ['dashboard', 'terapis', 'pasien_anak', 'presensi', 'ubah_profile', 'ubah_password'],
            ],
            [
                'name' => 'Admin Layanan',
                'checkedIds' => [
                    'dashboard',
                    'terapis',
                    'pasien_anak',
                    'observasi',
                    'asesmen',
                    'terapi_menu',
                    'laporan_soap',
                    'ubah_profile',
                    'ubah_password',
                ],
            ],
        ];

        foreach ($defaultAdminTypes as $item) {
            $role = Role::firstOrCreate([
                'name' => $item['name'],
                'guard_name' => 'api',
            ]);

            $permissionNames = AdminMenuPermissionMap::menuIdsToPermissions($item['checkedIds']);
            $permissions = Permission::whereIn('name', $permissionNames)
                ->where('guard_name', 'api')
                ->get();

            $role->syncPermissions($permissions);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
