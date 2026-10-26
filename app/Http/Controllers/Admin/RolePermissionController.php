<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    use ResponseFormatter;

    /**
     * Get RBAC Matrix of all roles and their respective permissions.
     */
    public function matrix(): JsonResponse
    {
        try {
            // Role internal/klinis Puspa Holistic (Orang Tua / Wali tidak termasuk dalam RBAC internal)
            $roleNames = ['owner', 'admin', 'asesor', 'terapis'];
            $roles = Role::whereIn('name', $roleNames)->with('permissions')->get()->keyBy('name');

            // Master capability list grouped by clinical & operational categories
            $categories = [
                'Manajemen Pasien' => [
                    ['name' => 'show_children', 'label' => 'Melihat Daftar Pasien / Anak'],
                    ['name' => 'show_detail_child', 'label' => 'Melihat Detail Pasien & Profil'],
                    ['name' => 'edit_child', 'label' => 'Mengubah Data Pasien'],
                    ['name' => 'delete_child', 'label' => 'Menghapus Data Pasien'],
                ],
                'Observasi & Skrining (Fase 1)' => [
                    ['name' => 'show_observations', 'label' => 'Melihat Jadwal & Status Observasi'],
                    ['name' => 'show_detail_observation', 'label' => 'Melihat Detail Observasi'],
                    ['name' => 'edit_schedule_observation', 'label' => 'Mengatur Tanggal / Reschedule Observasi'],
                    ['name' => 'submit_observation', 'label' => 'Mengisi Hasil Observasi Klinis'],
                    ['name' => 'show_observation_answer', 'label' => 'Melihat Jawaban Kuesioner Observasi'],
                    ['name' => 'show_observation_summary', 'label' => 'Melihat Ringkasan Observasi'],
                    ['name' => 'set_assessment', 'label' => 'Menyetujui & Melanjutkan ke Asesmen'],
                ],
                'Asesmen Klinis (Fase 2)' => [
                    ['name' => 'show_assessments', 'label' => 'Melihat Daftar Asesmen'],
                    ['name' => 'show_detail_assessment', 'label' => 'Melihat Rincian Asesmen Klinis'],
                    ['name' => 'edit_schedule_assessment', 'label' => 'Mengatur Jadwal Asesmen'],
                    ['name' => 'submit_assessment', 'label' => 'Mengisi & Menyelesaikan Asesmen Klinis'],
                    ['name' => 'upload_report_assessment_file', 'label' => 'Mengunggah Berkas Hasil Asesmen'],
                    ['name' => 'download_child_report_assessment_file', 'label' => 'Mengunduh Laporan Hasil Asesmen'],
                ],
                'Penjadwalan & Presensi (Fase 3 & 4)' => [
                    ['name' => 'manage_schedules', 'label' => 'Membuat & Mengedit Master Jadwal Terapi'],
                    ['name' => 'view_all_therapy_sessions', 'label' => 'Melihat Jadwal Seluruh Terapis (Lintas Terapis)'],
                    ['name' => 'view_own_therapy_sessions', 'label' => 'Melihat Jadwal Sesi Sendiri'],
                    ['name' => 'mark_attendance_qr', 'label' => 'Scan Presensi Kios / QR Code'],
                    ['name' => 'edit_past_attendance', 'label' => 'Koreksi Presensi Sesi Lampau'],
                    ['name' => 'reschedule_therapy_session', 'label' => 'Melakukan Reschedule Sesi Terapi'],
                ],
                'Rekam Medis SOAP & Terapi (Fase 5)' => [
                    ['name' => 'execute_therapy_session', 'label' => 'Menjalankan Sesi Terapi Pasien'],
                    ['name' => 'write_therapy_soap', 'label' => 'Menulis & Mengisi Catatan SOAP Terapi'],
                    ['name' => 'view_therapy_soap', 'label' => 'Melihat Catatan SOAP Pasien'],
                    ['name' => 'export_therapy_soap', 'label' => 'Ekspor Laporan Rekam Medis SOAP'],
                ],
                'Keuangan & Billing (Fase 6)' => [
                    ['name' => 'manage_invoices', 'label' => 'Membuat & Mengelola Tagihan Invoice'],
                    ['name' => 'manage_session_credits', 'label' => 'Mengelola Potongan Kredit Sesi (Session Credit)'],
                    ['name' => 'process_refunds', 'label' => 'Memproses Refund & Pengembalian Dana'],
                    ['name' => 'view_financial_reports', 'label' => 'Melihat Laporan Transaksi & Arus Kas'],
                ],
                'Manajemen Pengguna & Staf' => [
                    ['name' => 'show_admins', 'label' => 'Melihat Daftar Admin'],
                    ['name' => 'add_admin', 'label' => 'Menambah Akun Admin'],
                    ['name' => 'edit_admin', 'label' => 'Mengubah Data Admin'],
                    ['name' => 'delete_admin', 'label' => 'Menghapus Akun Admin'],
                    ['name' => 'show_therapists', 'label' => 'Melihat Daftar Terapis'],
                    ['name' => 'add_therapist', 'label' => 'Menambah Akun Terapis'],
                    ['name' => 'edit_therapist', 'label' => 'Mengubah Data Terapis'],
                    ['name' => 'delete_therapist', 'label' => 'Menghapus Akun Terapis'],
                ],
            ];

            // Capabilities yang bisa dilakukan terapis (fokus ke terapi)
            $terapisCapabilities = [
                'show_observations',
                'show_detail_observation',
                'submit_observation',
                'show_observation_answer',
                'show_observation_summary',
                'view_own_therapy_sessions',
                'execute_therapy_session',
                'write_therapy_soap',
                'view_therapy_soap',
            ];

            // Capabilities khusus asesmen
            $asesmenCapabilities = [
                'show_assessments',
                'show_detail_assessment',
                'submit_assessment',
                'upload_report_assessment_file',
                'download_child_report_assessment_file',
                'set_assessment',
                'view_all_therapy_sessions',
            ];

            // Asesor bisa melakukan asesmen klinis DAN semua hal yang dilakukan terapis
            $asesorCapabilities = array_unique(array_merge($terapisCapabilities, $asesmenCapabilities));

            // Admin bisa melakukan administrasi pasien, penjadwalan, presensi, billing, dan manajemen user
            $adminDisallowed = ['submit_assessment', 'execute_therapy_session', 'write_therapy_soap'];

            // Format matrix response
            $matrix = [];
            foreach ($categories as $categoryName => $perms) {
                $catItems = [];
                foreach ($perms as $p) {
                    $pName = $p['name'];
                    $roleAccess = [];

                    foreach ($roleNames as $rName) {
                        $hasPerm = false;

                        if ($rName === 'owner') {
                            // Owner bisa segalanya (Full Authority)
                            $hasPerm = true;
                        } elseif ($rName === 'asesor') {
                            // Asesor bisa asesmen + semua kerjaan terapis
                            $hasPerm = in_array($pName, $asesorCapabilities);
                        } elseif ($rName === 'terapis') {
                            // Terapis fokus ke pelaksanaan sesi terapi & SOAP
                            $hasPerm = in_array($pName, $terapisCapabilities);
                        } elseif ($rName === 'admin') {
                            // Admin fokus administrasi, presensi, jadwal, invoice, dll
                            $hasPerm = !in_array($pName, $adminDisallowed);
                        }

                        $roleAccess[$rName] = (bool) $hasPerm;
                    }

                    $catItems[] = [
                        'key' => $pName,
                        'label' => $p['label'],
                        'access' => $roleAccess,
                    ];
                }

                $matrix[] = [
                    'category' => $categoryName,
                    'items' => $catItems,
                ];
            }

            return $this->successResponse([
                'roles' => [
                    ['key' => 'owner', 'name' => 'Owner / Direktur', 'badge' => 'Bisa Segalanya'],
                    ['key' => 'asesor', 'name' => 'Asesor Klinis', 'badge' => 'Asesmen & Terapi'],
                    ['key' => 'admin', 'name' => 'Admin Operasional', 'badge' => 'Administrasi & Jadwal'],
                    ['key' => 'terapis', 'name' => 'Terapis', 'badge' => 'Pelaksana Terapi'],
                ],
                'matrix' => $matrix,
            ], 'Berhasil mengambil matriks RBAC role & permission');
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), [], 500);
        }
    }

    /**
     * Get active permissions of the authenticated user.
     */
    public function myPermissions(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Unauthenticated', [], 401);
        }

        $roles = $user->getRoleNames();
        $permissions = $user->getAllPermissions()->pluck('name');

        return $this->successResponse([
            'user_id' => $user->id,
            'email' => $user->email,
            'roles' => $roles,
            'permissions' => $permissions,
        ], 'Berhasil mengambil permission pengguna');
    }

    /**
     * Update capability / permission for a specific role (Only Owner can edit).
     */
    public function updatePermission(Request $request): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|in:owner,admin,asesor,terapis',
            'permission' => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        try {
            $roleName = $request->input('role');
            $permissionName = $request->input('permission');
            $enabled = (bool) $request->input('enabled');

            // Pastikan permission record ada di Spatie
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'api',
            ]);

            $role = Role::where('name', $roleName)->first();
            if (!$role) {
                return $this->errorResponse('Role tidak ditemukan', [], 404);
            }

            if ($enabled) {
                $role->givePermissionTo($permission);
            } else {
                $role->revokePermissionTo($permission);
            }

            // Bersihkan Spatie cache agar izin langsung aktif
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return $this->successResponse([
                'role' => $roleName,
                'permission' => $permissionName,
                'enabled' => $enabled,
            ], 'Hak akses berhasil diperbarui oleh Owner');
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), [], 500);
        }
    }
}
