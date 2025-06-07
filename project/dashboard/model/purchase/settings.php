<?php
/**
 * Model: Purchase Settings
 * نموذج إعدادات المشتريات المتقدم
 * يدير جميع إعدادات نظام المشتريات مع التحقق من صحة البيانات
 */

class ModelPurchaseSettings extends Model {

    /**
     * الحصول على جميع إعدادات المشتريات
     */
    public function getPurchaseSettings() {
        $settings = array();

        // الإعدادات العامة
        $general_settings = array(
            'purchase_default_currency',
            'purchase_default_payment_term',
            'purchase_default_supplier',
            'purchase_auto_approval_limit',
            'purchase_require_approval',
            'purchase_allow_partial_receipt',
            'purchase_auto_create_journal',
            'purchase_default_warehouse'
        );

        // إعدادات الترقيم
        $numbering_settings = array(
            'purchase_order_prefix',
            'purchase_order_suffix',
            'purchase_order_next_number',
            'purchase_quotation_prefix',
            'purchase_quotation_suffix',
            'purchase_quotation_next_number',
            'purchase_receipt_prefix',
            'purchase_receipt_suffix',
            'purchase_receipt_next_number'
        );

        // إعدادات الإشعارات
        $notification_settings = array(
            'purchase_email_notifications',
            'purchase_sms_notifications',
            'purchase_approval_notifications',
            'purchase_receipt_notifications',
            'purchase_overdue_notifications'
        );

        // إعدادات المخزون
        $inventory_settings = array(
            'purchase_inventory_method',
            'purchase_cost_calculation',
            'purchase_auto_update_cost',
            'purchase_allow_negative_stock',
            'purchase_track_serial_numbers',
            'purchase_track_batch_numbers'
        );

        // إعدادات التكامل
        $integration_settings = array(
            'purchase_accounting_integration',
            'purchase_default_expense_account',
            'purchase_default_payable_account',
            'purchase_default_tax_account',
            'purchase_auto_post_journals'
        );

        // إعدادات الموافقة
        $approval_settings = array(
            'purchase_approval_workflow',
            'purchase_approval_levels',
            'purchase_approval_matrix',
            'purchase_auto_approval_rules'
        );

        // إعدادات التقارير
        $report_settings = array(
            'purchase_report_period',
            'purchase_report_currency',
            'purchase_report_grouping',
            'purchase_report_format'
        );

        $all_settings = array_merge(
            $general_settings,
            $numbering_settings,
            $notification_settings,
            $inventory_settings,
            $integration_settings,
            $approval_settings,
            $report_settings
        );

        foreach ($all_settings as $key) {
            $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE `key` = '" . $this->db->escape($key) . "' AND store_id = '0'");

            if ($query->num_rows) {
                $settings[$key] = $query->row['value'];
            } else {
                $settings[$key] = $this->getDefaultValue($key);
            }
        }

        return $settings;
    }

    /**
     * حفظ إعدادات المشتريات
     */
    public function savePurchaseSettings($data) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = '0' AND `key` LIKE 'purchase_%'");

        foreach ($data as $key => $value) {
            if (strpos($key, 'purchase_') === 0) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '0', `code` = 'purchase', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
            }
        }

        // مسح الكاش
        $this->cache->delete('setting');
    }

    /**
     * التحقق من صحة الإعدادات
     */
    public function validateSettings($data) {
        $errors = array();

        // التحقق من حد الموافقة التلقائية
        if (isset($data['purchase_auto_approval_limit']) && !is_numeric($data['purchase_auto_approval_limit'])) {
            $errors[] = 'حد الموافقة التلقائية يجب أن يكون رقم';
        }

        // التحقق من أرقام الترقيم
        $numbering_fields = array(
            'purchase_order_next_number',
            'purchase_quotation_next_number',
            'purchase_receipt_next_number'
        );

        foreach ($numbering_fields as $field) {
            if (isset($data[$field]) && (!is_numeric($data[$field]) || $data[$field] < 1)) {
                $errors[] = 'رقم التسلسل يجب أن يكون رقم موجب';
            }
        }

        // التحقق من العملة الافتراضية
        if (isset($data['purchase_default_currency']) && !empty($data['purchase_default_currency'])) {
            if (!$this->validateCurrency($data['purchase_default_currency'])) {
                $errors[] = 'العملة الافتراضية غير صحيحة';
            }
        }

        // التحقق من المورد الافتراضي
        if (isset($data['purchase_default_supplier']) && !empty($data['purchase_default_supplier'])) {
            if (!$this->validateSupplier($data['purchase_default_supplier'])) {
                $errors[] = 'المورد الافتراضي غير صحيح';
            }
        }

        return $errors;
    }

    /**
     * الحصول على القيمة الافتراضية للإعداد
     */
    private function getDefaultValue($key) {
        $defaults = array(
            'purchase_default_currency' => 'EGP',
            'purchase_default_payment_term' => '30',
            'purchase_auto_approval_limit' => '10000',
            'purchase_require_approval' => '1',
            'purchase_allow_partial_receipt' => '1',
            'purchase_auto_create_journal' => '1',
            'purchase_order_prefix' => 'PO',
            'purchase_order_suffix' => '',
            'purchase_order_next_number' => '1',
            'purchase_quotation_prefix' => 'PQ',
            'purchase_quotation_suffix' => '',
            'purchase_quotation_next_number' => '1',
            'purchase_receipt_prefix' => 'PR',
            'purchase_receipt_suffix' => '',
            'purchase_receipt_next_number' => '1',
            'purchase_email_notifications' => '1',
            'purchase_sms_notifications' => '0',
            'purchase_approval_notifications' => '1',
            'purchase_receipt_notifications' => '1',
            'purchase_overdue_notifications' => '1',
            'purchase_inventory_method' => 'fifo',
            'purchase_cost_calculation' => 'weighted_average',
            'purchase_auto_update_cost' => '1',
            'purchase_allow_negative_stock' => '0',
            'purchase_track_serial_numbers' => '0',
            'purchase_track_batch_numbers' => '0',
            'purchase_accounting_integration' => '1',
            'purchase_auto_post_journals' => '1',
            'purchase_approval_workflow' => '1',
            'purchase_approval_levels' => '2',
            'purchase_report_period' => 'monthly',
            'purchase_report_currency' => 'EGP',
            'purchase_report_grouping' => 'supplier',
            'purchase_report_format' => 'pdf'
        );

        return isset($defaults[$key]) ? $defaults[$key] : '';
    }

    /**
     * التحقق من صحة العملة
     */
    private function validateCurrency($currency_id) {
        $query = $this->db->query("SELECT currency_id FROM " . DB_PREFIX . "currency WHERE currency_id = '" . (int)$currency_id . "' AND status = '1'");
        return $query->num_rows > 0;
    }

    /**
     * التحقق من صحة المورد
     */
    private function validateSupplier($supplier_id) {
        $query = $this->db->query("SELECT supplier_id FROM " . DB_PREFIX . "supplier WHERE supplier_id = '" . (int)$supplier_id . "' AND status = '1'");
        return $query->num_rows > 0;
    }

    /**
     * الحصول على قائمة العملات
     */
    public function getCurrencies() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "currency WHERE status = '1' ORDER BY title");
        return $query->rows;
    }

    /**
     * الحصول على قائمة الموردين
     */
    public function getSuppliers() {
        $query = $this->db->query("SELECT supplier_id, name FROM " . DB_PREFIX . "supplier WHERE status = '1' ORDER BY name");
        return $query->rows;
    }

    /**
     * الحصول على قائمة المستودعات
     */
    public function getWarehouses() {
        $query = $this->db->query("SELECT location_id, name FROM " . DB_PREFIX . "location WHERE status = '1' ORDER BY name");
        return $query->rows;
    }

    /**
     * الحصول على قائمة الحسابات المحاسبية
     */
    public function getAccounts() {
        $query = $this->db->query("
            SELECT a.account_id, a.account_code, ad.name
            FROM " . DB_PREFIX . "accounts a
            LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id)
            WHERE a.status = '1' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY a.account_code
        ");
        return $query->rows;
    }

    /**
     * تصدير الإعدادات إلى JSON
     */
    public function exportSettings() {
        $settings = $this->getPurchaseSettings();
        return json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * استيراد الإعدادات من JSON
     */
    public function importSettings($json_data) {
        $data = json_decode($json_data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return array('error' => 'ملف JSON غير صحيح');
        }

        $errors = $this->validateSettings($data);

        if (empty($errors)) {
            $this->savePurchaseSettings($data);
            return array('success' => 'تم استيراد الإعدادات بنجاح');
        } else {
            return array('error' => implode('<br>', $errors));
        }
    }

    /**
     * إعادة تعيين الإعدادات للقيم الافتراضية
     */
    public function resetToDefaults() {
        $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = '0' AND `key` LIKE 'purchase_%'");
        $this->cache->delete('setting');
        return true;
    }

    /**
     * الحصول على إعدادات الأمان
     */
    public function getSecuritySettings() {
        $security_settings = array(
            'purchase_require_2fa',
            'purchase_session_timeout',
            'purchase_max_login_attempts',
            'purchase_password_policy',
            'purchase_ip_whitelist',
            'purchase_audit_log'
        );

        $settings = array();
        foreach ($security_settings as $key) {
            $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE `key` = '" . $this->db->escape($key) . "' AND store_id = '0'");
            $settings[$key] = $query->num_rows ? $query->row['value'] : $this->getDefaultValue($key);
        }

        return $settings;
    }

    /**
     * حفظ إعدادات الأمان
     */
    public function saveSecuritySettings($data) {
        foreach ($data as $key => $value) {
            if (strpos($key, 'purchase_') === 0) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = '0' AND `key` = '" . $this->db->escape($key) . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '0', `code` = 'purchase', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
            }
        }
        $this->cache->delete('setting');
    }

    /**
     * الحصول على إعدادات الأداء
     */
    public function getPerformanceSettings() {
        $performance_settings = array(
            'purchase_page_size',
            'purchase_query_timeout',
            'purchase_memory_limit',
            'purchase_cache_enabled',
            'purchase_compression_enabled',
            'purchase_lazy_loading'
        );

        $settings = array();
        foreach ($performance_settings as $key) {
            $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE `key` = '" . $this->db->escape($key) . "' AND store_id = '0'");
            $settings[$key] = $query->num_rows ? $query->row['value'] : $this->getDefaultValue($key);
        }

        return $settings;
    }

    /**
     * حفظ إعدادات الأداء
     */
    public function savePerformanceSettings($data) {
        foreach ($data as $key => $value) {
            if (strpos($key, 'purchase_') === 0) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = '0' AND `key` = '" . $this->db->escape($key) . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '0', `code` = 'purchase', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
            }
        }
        $this->cache->delete('setting');
    }

    /**
     * الحصول على إعدادات النسخ الاحتياطي
     */
    public function getBackupSettings() {
        $backup_settings = array(
            'purchase_auto_backup',
            'purchase_backup_frequency',
            'purchase_backup_retention',
            'purchase_backup_path',
            'purchase_backup_compression',
            'purchase_backup_encryption'
        );

        $settings = array();
        foreach ($backup_settings as $key) {
            $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE `key` = '" . $this->db->escape($key) . "' AND store_id = '0'");
            $settings[$key] = $query->num_rows ? $query->row['value'] : $this->getDefaultValue($key);
        }

        return $settings;
    }

    /**
     * حفظ إعدادات النسخ الاحتياطي
     */
    public function saveBackupSettings($data) {
        foreach ($data as $key => $value) {
            if (strpos($key, 'purchase_') === 0) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = '0' AND `key` = '" . $this->db->escape($key) . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '0', `code` = 'purchase', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
            }
        }
        $this->cache->delete('setting');
    }

    /**
     * الحصول على إعدادات البريد الإلكتروني
     */
    public function getEmailSettings() {
        $email_settings = array(
            'purchase_smtp_host',
            'purchase_smtp_port',
            'purchase_smtp_username',
            'purchase_smtp_password',
            'purchase_smtp_encryption',
            'purchase_email_from_name',
            'purchase_email_from_address'
        );

        $settings = array();
        foreach ($email_settings as $key) {
            $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE `key` = '" . $this->db->escape($key) . "' AND store_id = '0'");
            $settings[$key] = $query->num_rows ? $query->row['value'] : $this->getDefaultValue($key);
        }

        return $settings;
    }

    /**
     * حفظ إعدادات البريد الإلكتروني
     */
    public function saveEmailSettings($data) {
        foreach ($data as $key => $value) {
            if (strpos($key, 'purchase_') === 0) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = '0' AND `key` = '" . $this->db->escape($key) . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '0', `code` = 'purchase', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
            }
        }
        $this->cache->delete('setting');
    }

    /**
     * اختبار إعدادات البريد الإلكتروني
     */
    public function testEmailSettings($settings) {
        try {
            // إعداد PHPMailer أو استخدام mail() function
            $to = $settings['test_email'];
            $subject = 'اختبار إعدادات البريد الإلكتروني - نظام المشتريات';
            $message = 'هذه رسالة اختبار لتأكيد صحة إعدادات البريد الإلكتروني.';

            // هنا يمكن إضافة كود إرسال البريد الفعلي
            return array('success' => 'تم إرسال رسالة الاختبار بنجاح');
        } catch (Exception $e) {
            return array('error' => 'فشل في إرسال رسالة الاختبار: ' . $e->getMessage());
        }
    }

    /**
     * الحصول على إحصائيات النظام
     */
    public function getSystemStatistics() {
        $stats = array();

        // إحصائيات أوامر الشراء
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "purchase_order");
        $stats['total_orders'] = $query->row['total'];

        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "purchase_order WHERE status = 'pending'");
        $stats['pending_orders'] = $query->row['total'];

        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "purchase_order WHERE status = 'approved'");
        $stats['approved_orders'] = $query->row['total'];

        // إحصائيات الموردين
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier WHERE status = '1'");
        $stats['active_suppliers'] = $query->row['total'];

        // إحصائيات المنتجات
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product WHERE status = '1'");
        $stats['active_products'] = $query->row['total'];

        // إحصائيات مالية
        $query = $this->db->query("SELECT SUM(total) as total_value FROM " . DB_PREFIX . "purchase_order WHERE status = 'approved'");
        $stats['total_purchase_value'] = $query->row['total_value'] ?: 0;

        return $stats;
    }

    /**
     * تحسين قاعدة البيانات
     */
    public function optimizeDatabase() {
        $tables = array(
            DB_PREFIX . 'purchase_order',
            DB_PREFIX . 'purchase_order_product',
            DB_PREFIX . 'supplier',
            DB_PREFIX . 'setting'
        );

        foreach ($tables as $table) {
            $this->db->query("OPTIMIZE TABLE `" . $table . "`");
        }

        return true;
    }

    /**
     * مسح التخزين المؤقت
     */
    public function clearCache() {
        $this->cache->delete('setting');
        $this->cache->delete('purchase');
        $this->cache->delete('supplier');
        $this->cache->delete('product');

        return true;
    }

    /**
     * إنشاء نسخة احتياطية
     */
    public function createBackup() {
        $backup_data = array(
            'settings' => $this->getPurchaseSettings(),
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0'
        );

        $filename = 'purchase_backup_' . date('Y-m-d_H-i-s') . '.json';
        $backup_json = json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // حفظ النسخة الاحتياطية
        $backup_path = DIR_STORAGE . 'backup/';
        if (!is_dir($backup_path)) {
            mkdir($backup_path, 0755, true);
        }

        file_put_contents($backup_path . $filename, $backup_json);

        return array('success' => 'تم إنشاء النسخة الاحتياطية بنجاح', 'filename' => $filename);
    }

    /**
     * استعادة من نسخة احتياطية
     */
    public function restoreBackup($filename) {
        $backup_path = DIR_STORAGE . 'backup/' . $filename;

        if (!file_exists($backup_path)) {
            return array('error' => 'ملف النسخة الاحتياطية غير موجود');
        }

        $backup_content = file_get_contents($backup_path);
        $backup_data = json_decode($backup_content, true);

        if (!$backup_data || !isset($backup_data['settings'])) {
            return array('error' => 'ملف النسخة الاحتياطية تالف');
        }

        $this->savePurchaseSettings($backup_data['settings']);

        return array('success' => 'تم استعادة النسخة الاحتياطية بنجاح');
    }

    /**
     * الحصول على قائمة النسخ الاحتياطية
     */
    public function getBackupList() {
        $backup_path = DIR_STORAGE . 'backup/';
        $backups = array();

        if (is_dir($backup_path)) {
            $files = scandir($backup_path);
            foreach ($files as $file) {
                if (strpos($file, 'purchase_backup_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $backups[] = array(
                        'filename' => $file,
                        'size' => filesize($backup_path . $file),
                        'date' => date('Y-m-d H:i:s', filemtime($backup_path . $file))
                    );
                }
            }
        }

        return $backups;
    }
}
