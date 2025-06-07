<?php
/**
 * نموذج الصلاحيات المتقدمة للمستخدمين
 * مستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
 */
class ModelUserUserPermissionAdvanced extends Model {

    /**
     * التحقق من الصلاحية المتقدمة
     */
    public function hasAdvancedPermission($user_id, $permission_code) {
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "user_advanced_permissions uap
            INNER JOIN " . DB_PREFIX . "user u ON uap.user_id = u.user_id
            WHERE uap.user_id = '" . (int)$user_id . "'
            AND uap.permission_code = '" . $this->db->escape($permission_code) . "'
            AND uap.is_active = 1
            AND u.status = 1
            AND (uap.expiry_date IS NULL OR uap.expiry_date > NOW())
        ");

        return $query->row['count'] > 0;
    }

    /**
     * منح صلاحية متقدمة
     */
    public function grantAdvancedPermission($user_id, $permission_code, $granted_by, $expiry_date = null, $reason = '') {
        // التحقق من وجود الصلاحية مسبقاً
        $existing = $this->db->query("
            SELECT permission_id 
            FROM " . DB_PREFIX . "user_advanced_permissions
            WHERE user_id = '" . (int)$user_id . "'
            AND permission_code = '" . $this->db->escape($permission_code) . "'
            AND is_active = 1
        ");

        if ($existing->num_rows) {
            throw new Exception('المستخدم يملك هذه الصلاحية مسبقاً');
        }

        // منح الصلاحية
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "user_advanced_permissions SET
            user_id = '" . (int)$user_id . "',
            permission_code = '" . $this->db->escape($permission_code) . "',
            granted_by = '" . (int)$granted_by . "',
            granted_date = NOW(),
            expiry_date = " . ($expiry_date ? "'" . $this->db->escape($expiry_date) . "'" : "NULL") . ",
            reason = '" . $this->db->escape($reason) . "',
            is_active = 1
        ");

        $permission_id = $this->db->getLastId();

        // تسجيل في سجل المراجعة
        $this->logPermissionChange($user_id, $permission_code, 'granted', $reason, $granted_by);

        return $permission_id;
    }

    /**
     * إلغاء صلاحية متقدمة
     */
    public function revokeAdvancedPermission($user_id, $permission_code, $revoked_by, $reason = '') {
        // إلغاء الصلاحية
        $this->db->query("
            UPDATE " . DB_PREFIX . "user_advanced_permissions SET
            is_active = 0,
            revoked_by = '" . (int)$revoked_by . "',
            revoked_date = NOW(),
            revoke_reason = '" . $this->db->escape($reason) . "'
            WHERE user_id = '" . (int)$user_id . "'
            AND permission_code = '" . $this->db->escape($permission_code) . "'
            AND is_active = 1
        ");

        // تسجيل في سجل المراجعة
        $this->logPermissionChange($user_id, $permission_code, 'revoked', $reason, $revoked_by);

        return $this->db->countAffected() > 0;
    }

    /**
     * الحصول على صلاحيات المستخدم المتقدمة
     */
    public function getUserAdvancedPermissions($user_id) {
        $query = $this->db->query("
            SELECT 
                uap.*,
                ap.permission_name,
                ap.permission_description,
                ap.risk_level,
                ap.category,
                u1.firstname as granted_by_name,
                u2.firstname as revoked_by_name
            FROM " . DB_PREFIX . "user_advanced_permissions uap
            INNER JOIN " . DB_PREFIX . "advanced_permissions ap ON uap.permission_code = ap.permission_code
            LEFT JOIN " . DB_PREFIX . "user u1 ON uap.granted_by = u1.user_id
            LEFT JOIN " . DB_PREFIX . "user u2 ON uap.revoked_by = u2.user_id
            WHERE uap.user_id = '" . (int)$user_id . "'
            ORDER BY uap.granted_date DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على جميع الصلاحيات المتقدمة المتاحة
     */
    public function getAvailableAdvancedPermissions() {
        $query = $this->db->query("
            SELECT *
            FROM " . DB_PREFIX . "advanced_permissions
            WHERE is_active = 1
            ORDER BY category, risk_level DESC, permission_name
        ");

        return $query->rows;
    }

    /**
     * إنشاء صلاحية متقدمة جديدة
     */
    public function createAdvancedPermission($data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "advanced_permissions SET
            permission_code = '" . $this->db->escape($data['permission_code']) . "',
            permission_name = '" . $this->db->escape($data['permission_name']) . "',
            permission_description = '" . $this->db->escape($data['permission_description']) . "',
            category = '" . $this->db->escape($data['category']) . "',
            risk_level = '" . $this->db->escape($data['risk_level']) . "',
            requires_approval = '" . (int)$data['requires_approval'] . "',
            max_duration_days = " . (isset($data['max_duration_days']) ? (int)$data['max_duration_days'] : "NULL") . ",
            created_by = '" . (int)$this->user->getId() . "',
            created_date = NOW(),
            is_active = 1
        ");

        return $this->db->getLastId();
    }

    /**
     * تسجيل تغيير الصلاحيات
     */
    private function logPermissionChange($user_id, $permission_code, $action, $reason, $action_by) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "permission_audit_log SET
            user_id = '" . (int)$user_id . "',
            permission_code = '" . $this->db->escape($permission_code) . "',
            action = '" . $this->db->escape($action) . "',
            reason = '" . $this->db->escape($reason) . "',
            action_by = '" . (int)$action_by . "',
            action_date = NOW(),
            ip_address = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "',
            user_agent = '" . $this->db->escape($this->request->server['HTTP_USER_AGENT']) . "'
        ");
    }

    /**
     * التحقق من صلاحيات القيود المحاسبية
     */
    public function checkJournalPermissions($user_id, $action, $journal_id = null) {
        $permissions = array();

        switch ($action) {
            case 'create':
                $permissions['basic'] = $this->user->hasPermission('modify', 'accounts/journal');
                $permissions['advanced'] = $this->hasAdvancedPermission($user_id, 'journal_create_advanced');
                break;

            case 'edit':
                $permissions['basic'] = $this->user->hasPermission('modify', 'accounts/journal');
                $permissions['advanced'] = $this->hasAdvancedPermission($user_id, 'journal_edit_advanced');
                
                // التحقق من حالة القيد إذا تم تمرير معرفه
                if ($journal_id) {
                    $this->load->model('accounts/journal_security_advanced');
                    $can_modify = $this->model_accounts_journal_security_advanced->canModifyEntry($journal_id);
                    $permissions['can_modify'] = $can_modify['allowed'];
                    $permissions['modify_reason'] = $can_modify['reason'];
                }
                break;

            case 'delete':
                $permissions['basic'] = $this->user->hasPermission('modify', 'accounts/journal');
                $permissions['advanced'] = $this->hasAdvancedPermission($user_id, 'journal_delete_advanced');
                
                // التحقق من إمكانية الحذف إذا تم تمرير معرف القيد
                if ($journal_id) {
                    $this->load->model('accounts/journal_security_advanced');
                    $can_delete = $this->model_accounts_journal_security_advanced->canDeleteEntry($journal_id);
                    $permissions['can_delete'] = $can_delete['allowed'];
                    $permissions['delete_reason'] = $can_delete['reason'];
                }
                break;

            case 'review':
                $permissions['basic'] = $this->user->hasPermission('access', 'accounts/journal_review');
                $permissions['advanced'] = $this->hasAdvancedPermission($user_id, 'journal_review_advanced');
                break;

            case 'post':
                $permissions['basic'] = $this->user->hasPermission('modify', 'accounts/journal');
                $permissions['advanced'] = $this->hasAdvancedPermission($user_id, 'journal_post_advanced');
                break;

            case 'unsecure':
                $permissions['basic'] = false; // لا توجد صلاحية أساسية لإلغاء التأمين
                $permissions['advanced'] = $this->hasAdvancedPermission($user_id, 'journal_unsecure_super_admin');
                break;
        }

        return $permissions;
    }

    /**
     * الحصول على تقرير الصلاحيات
     */
    public function getPermissionsReport($filter_data = array()) {
        $sql = "
            SELECT 
                u.user_id,
                u.username,
                u.firstname,
                u.lastname,
                u.email,
                COUNT(uap.permission_id) as total_advanced_permissions,
                COUNT(CASE WHEN ap.risk_level = 'critical' THEN 1 END) as critical_permissions,
                COUNT(CASE WHEN ap.risk_level = 'high' THEN 1 END) as high_permissions,
                COUNT(CASE WHEN uap.expiry_date IS NOT NULL AND uap.expiry_date < NOW() THEN 1 END) as expired_permissions,
                MAX(uap.granted_date) as last_permission_granted
            FROM " . DB_PREFIX . "user u
            LEFT JOIN " . DB_PREFIX . "user_advanced_permissions uap ON u.user_id = uap.user_id AND uap.is_active = 1
            LEFT JOIN " . DB_PREFIX . "advanced_permissions ap ON uap.permission_code = ap.permission_code
            WHERE u.status = 1
        ";

        if (!empty($filter_data['user_group'])) {
            $sql .= " AND u.user_group_id = '" . (int)$filter_data['user_group'] . "'";
        }

        if (!empty($filter_data['permission_category'])) {
            $sql .= " AND ap.category = '" . $this->db->escape($filter_data['permission_category']) . "'";
        }

        $sql .= " GROUP BY u.user_id ORDER BY u.firstname, u.lastname";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * تنظيف الصلاحيات المنتهية الصلاحية
     */
    public function cleanupExpiredPermissions() {
        $query = $this->db->query("
            SELECT uap.*, u.username, ap.permission_name
            FROM " . DB_PREFIX . "user_advanced_permissions uap
            INNER JOIN " . DB_PREFIX . "user u ON uap.user_id = u.user_id
            INNER JOIN " . DB_PREFIX . "advanced_permissions ap ON uap.permission_code = ap.permission_code
            WHERE uap.is_active = 1 
            AND uap.expiry_date IS NOT NULL 
            AND uap.expiry_date < NOW()
        ");

        $expired_permissions = $query->rows;

        if (!empty($expired_permissions)) {
            // إلغاء الصلاحيات المنتهية
            $this->db->query("
                UPDATE " . DB_PREFIX . "user_advanced_permissions SET
                is_active = 0,
                revoked_by = 0,
                revoked_date = NOW(),
                revoke_reason = 'انتهت صلاحية الإذن تلقائياً'
                WHERE is_active = 1 
                AND expiry_date IS NOT NULL 
                AND expiry_date < NOW()
            ");

            // تسجيل في سجل المراجعة
            foreach ($expired_permissions as $permission) {
                $this->logPermissionChange(
                    $permission['user_id'], 
                    $permission['permission_code'], 
                    'auto_expired', 
                    'انتهت صلاحية الإذن تلقائياً', 
                    0
                );
            }
        }

        return count($expired_permissions);
    }

    /**
     * إنشاء الصلاحيات الافتراضية للنظام
     */
    public function createDefaultAdvancedPermissions() {
        $default_permissions = array(
            // صلاحيات القيود المحاسبية
            array(
                'permission_code' => 'journal_delete_advanced',
                'permission_name' => 'حذف القيود المحاسبية',
                'permission_description' => 'صلاحية حذف القيود المحاسبية غير المراجعة',
                'category' => 'accounting',
                'risk_level' => 'high',
                'requires_approval' => 1
            ),
            array(
                'permission_code' => 'journal_unsecure_super_admin',
                'permission_name' => 'إلغاء تأمين القيود',
                'permission_description' => 'صلاحية إلغاء تأمين القيود المحاسبية المراجعة',
                'category' => 'accounting',
                'risk_level' => 'critical',
                'requires_approval' => 1
            ),
            array(
                'permission_code' => 'journal_review_advanced',
                'permission_name' => 'مراجعة القيود المتقدمة',
                'permission_description' => 'صلاحية مراجعة واعتماد القيود المحاسبية',
                'category' => 'accounting',
                'risk_level' => 'medium',
                'requires_approval' => 0
            ),
            array(
                'permission_code' => 'journal_post_advanced',
                'permission_name' => 'ترحيل القيود المتقدمة',
                'permission_description' => 'صلاحية ترحيل القيود إلى دفتر الأستاذ',
                'category' => 'accounting',
                'risk_level' => 'medium',
                'requires_approval' => 0
            ),
            // صلاحيات النظام
            array(
                'permission_code' => 'system_backup_restore',
                'permission_name' => 'النسخ الاحتياطي والاستعادة',
                'permission_description' => 'صلاحية إنشاء واستعادة النسخ الاحتياطية',
                'category' => 'system',
                'risk_level' => 'critical',
                'requires_approval' => 1
            ),
            array(
                'permission_code' => 'user_permission_management',
                'permission_name' => 'إدارة صلاحيات المستخدمين',
                'permission_description' => 'صلاحية منح وإلغاء الصلاحيات المتقدمة',
                'category' => 'user_management',
                'risk_level' => 'high',
                'requires_approval' => 1
            )
        );

        foreach ($default_permissions as $permission) {
            // التحقق من عدم وجود الصلاحية مسبقاً
            $existing = $this->db->query("
                SELECT permission_id 
                FROM " . DB_PREFIX . "advanced_permissions
                WHERE permission_code = '" . $this->db->escape($permission['permission_code']) . "'
            ");

            if (!$existing->num_rows) {
                $this->createAdvancedPermission($permission);
            }
        }
    }
}
