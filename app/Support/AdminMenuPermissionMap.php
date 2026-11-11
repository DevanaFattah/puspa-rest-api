<?php

namespace App\Support;

class AdminMenuPermissionMap
{
    /**
     * Complete mapping between Frontend 12 Leaf Menu IDs and Spatie Permission Names.
     */
    public const MENU_TO_PERMISSIONS = [
        'dashboard' => [
            'show_admins',
            'show_therapists',
            'show_children',
        ],
        'admin' => [
            'show_admins',
            'show_detail_admin',
            'add_admin',
            'edit_admin',
            'delete_admin',
        ],
        'terapis' => [
            'show_therapists',
            'show_detail_therapist',
            'add_therapist',
            'edit_therapist',
            'delete_therapist',
        ],
        'pasien_anak' => [
            'show_children',
            'show_detail_child',
            'edit_child',
            'delete_child',
        ],
        'observasi' => [
            'show_observations',
            'show_detail_observation',
            'edit_schedule_observation',
            'submit_observation',
            'show_observation_answer',
            'show_observation_summary',
            'set_assessment',
        ],
        'asesmen' => [
            'show_assessments',
            'show_detail_assessment',
            'edit_schedule_assessment',
            'submit_assessment',
            'upload_report_assessment_file',
            'download_child_report_assessment_file',
        ],
        'terapi_menu' => [
            'manage_schedules',
            'view_all_therapy_sessions',
            'reschedule_therapy_session',
        ],
        'presensi' => [
            'mark_attendance_qr',
            'edit_past_attendance',
        ],
        'laporan_soap' => [
            'view_therapy_soap',
            'export_therapy_soap',
        ],
        'keuangan' => [
            'manage_invoices',
            'manage_session_credits',
            'process_refunds',
            'view_financial_reports',
        ],
        'ubah_profile' => [],
        'ubah_password' => [],
    ];

    /**
     * Mandatory leaf IDs always included in any role.
     */
    public const WAJIB_LEAF_IDS = ['dashboard', 'ubah_profile', 'ubah_password'];

    /**
     * Convert list of frontend checked menu IDs to Spatie permission names.
     */
    public static function menuIdsToPermissions(array $menuIds): array
    {
        $permissions = [];
        foreach ($menuIds as $menuId) {
            if (isset(self::MENU_TO_PERMISSIONS[$menuId])) {
                $permissions = array_merge($permissions, self::MENU_TO_PERMISSIONS[$menuId]);
            }
        }
        return array_values(array_unique($permissions));
    }

    /**
     * Deduce frontend checked menu IDs from a collection of Spatie permissions.
     */
    public static function permissionsToMenuIds(array $permissionNames): array
    {
        $menuIds = self::WAJIB_LEAF_IDS;

        foreach (self::MENU_TO_PERMISSIONS as $menuId => $requiredPerms) {
            if (empty($requiredPerms)) {
                continue;
            }

            // If at least one matching permission exists for that menu, mark checked
            $intersect = array_intersect($requiredPerms, $permissionNames);
            if (!empty($intersect)) {
                $menuIds[] = $menuId;
            }
        }

        return array_values(array_unique($menuIds));
    }
}

