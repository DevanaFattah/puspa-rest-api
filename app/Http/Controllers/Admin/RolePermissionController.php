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
        $roleNames = ['owner', 'admin', 'asesor', 'terapis', 'user'];
        $roles = Role::whereIn('name', $roleNames)->with('permissions')->get()->keyBy('name');

        // Master capability list grouped by clinical & operational categories
        $categories = [
            'Manajemen Pasien & Orang Tua' => [
                ['name' => 'show_children', 'label' => 'Melihat Daftar Pasien / Anak'],
                ['name' => 'show_detail_child', 'label' => 'Melihat Detail Pasien & Rekam Medis'],
                ['name' => 'edit_child', 'label' => 'Mengubah Data Pasien'],
                ['name' => 'delete_child', 'label' => 'Menghapus Pasien'],
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
                ['name' => 'submit_assessment', 'label' => 'Mengisi Hasil Asesmen Klinis (Asesor)'],
                ['name' => 'submit_parent_assessment', 'label' => 'Mengisi Kuesioner Asesmen (Orang Tua)'],
                ['name' => 'upload_report_assessment_file', 'label' => 'Mengunggah Berkas Hasil Asesmen'],
                ['name' => 'download_child_report_assessment_file', 'label' => 'Mengunduh Laporan Hasil Asesmen'],
            ],
            'Penjadwalan & Presensi (Fase 3 & 4)' => [
                ['name' => 'manage_schedules', 'label' => 'Membuat & Mengedit Master Jadwal Terapi'],
                ['name' => 'view_all_therapy_sessions', 'label' => 'Melihat Jadwal Seluruh Terapis (Lintas Terapis)'],
                ['name' => 'view_own_therapy_sessions', 'label' => 'Melihat Jadwal Sesi Sendiri'],
                ['name' => 'mark_attendance_qr', 'label' => 'Scan Presensi Kios / QR Code'],
                ['name' => 'edit_past_attendance', 'label' => 'Koreksi Presensi Sesi Lampau (Manajerial)'],
                ['name' => 'reschedule_therapy_session', 'label' => 'Melakukan Reschedule Sesi Terapi'],
            ],
            'Rekam Medis SOAP (Fase 5)' => [
                ['name' => 'write_therapy_soap', 'label' => 'Menulis Catatan SOAP Terapi'],
                ['name' => 'view_therapy_soap', 'label' => 'Melihat Catatan SOAP Pasien'],
                ['name' => 'export_therapy_soap', 'label' => 'Ekspor Laporan SOAP Pasien'],
            ],
            'Keuangan & Billing (Fase 6)' => [
                ['name' => 'manage_invoices', 'label' => 'Membuat & Mengelola Tagihan Invoice'],
                ['name' => 'manage_session_credits', 'label' => 'Mengelola Potongan Kredit Sesi (Session Credit)'],
                ['name' => 'process_refunds', 'label' => 'Memproses Refund & Pengembalian Dana'],
                ['name' => 'view_financial_reports', 'label' => 'Melihat Laporan Transaksi & Arus Kas'],
            ],
            'Manajemen Pengguna & Sistem' => [
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

        // Format matrix response
        $matrix = [];
        foreach ($categories as $categoryName => $perms) {
            $catItems = [];
            foreach ($perms as $p) {
                $roleAccess = [];
                foreach ($roleNames as $rName) {
                    $roleObj = $roles->get($rName);
                    // Check if role has permission in DB or based on role core responsibilities
                    $hasPerm = false;
                    if ($roleObj) {
                        $hasPerm = $roleObj->hasPermissionTo($p['name'], 'api');
                    }

                    // Fallback capability mapping if permission record not explicitly seeded
                    if (!$hasPerm) {
                        if ($rName === 'owner') {
                            $hasPerm = true; // Owner has full visibility
                        } elseif ($rName === 'admin') {
                            $hasPerm = !in_array($p['name'], ['submit_assessment', 'write_therapy_soap', 'submit_parent_assessment']);
                        } elseif ($rName === 'asesor') {
                            $hasPerm = in_array($p['name'], [
                                'show_observations', 'show_detail_observation', 'submit_observation',
                                'show_observation_answer', 'show_observation_summary', 'show_assessments',
                                'show_detail_assessment', 'submit_assessment', 'upload_report_assessment_file',
                                'view_all_therapy_sessions', 'view_own_therapy_sessions', 'write_therapy_soap',
                                'view_therapy_soap'
                            ]);
                        } elseif ($rName === 'terapis') {
                            $hasPerm = in_array($p['name'], [
                                'show_observations', 'show_detail_observation', 'submit_observation',
                                'show_observation_answer', 'show_observation_summary', 'view_own_therapy_sessions',
                                'write_therapy_soap', 'view_therapy_soap'
                            ]);
                        } elseif ($rName === 'user') {
                            $hasPerm = in_array($p['name'], [
                                'edit_child', 'delete_child', 'submit_parent_assessment',
                                'download_child_report_assessment_file'
                            ]);
                        }
                    }

                    $roleAccess[$rName] = (bool) $hasPerm;
                }

                $catItems[] = [
                    'key' => $p['name'],
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
                ['key' => 'owner', 'name' => 'Owner / Direktur', 'badge' => 'Super Admin'],
                ['key' => 'admin', 'name' => 'Admin Operasional', 'badge' => 'Manager'],
                ['key' => 'asesor', 'name' => 'Asesor Klinis', 'badge' => 'Clinical Specialist'],
                ['key' => 'terapis', 'name' => 'Terapis', 'badge' => 'Practitioner'],
                ['key' => 'user', 'name' => 'Orang Tua / Wali', 'badge' => 'Client'],
            ],
            'matrix' => $matrix,
        ], 'Berhasil mengambil matriks RBAC role & permission');
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
}
