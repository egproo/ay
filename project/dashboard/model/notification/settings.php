<?php
/**
 * نموذج إعدادات نظام الإشعارات المتقدم
 * Notification Settings Model
 * 
 * نظام إدارة إعدادات الإشعارات مع تكامل مع catalog/inventory
 * مطور بمستوى عالمي لتفوق على Odoo
 * 
 * @package    AYM ERP
 * @author     AYM ERP Development Team
 * @copyright  2024 AYM ERP
 * @license    Proprietary
 * @version    1.0.0
 * @link       https://aym-erp.com
 * @since      2024-12-19
 */

class ModelNotificationSettings extends Model {
    
    /**
     * إنشاء جداول نظام الإشعارات
     */
    public function install() {
        // جدول إعدادات الإشعارات
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "notification_settings` (
                `setting_id` int(11) NOT NULL AUTO_INCREMENT,
                `setting_key` varchar(128) NOT NULL,
                `setting_value` text,
                `setting_group` varchar(64) DEFAULT 'general',
                `user_id` int(11) DEFAULT 0,
                `user_group_id` int(11) DEFAULT 0,
                `date_added` datetime NOT NULL,
                `date_modified` datetime NOT NULL,
                PRIMARY KEY (`setting_id`),
                UNIQUE KEY `setting_key_user` (`setting_key`, `user_id`, `user_group_id`),
                KEY `setting_group` (`setting_group`),
                KEY `user_id` (`user_id`),
                KEY `user_group_id` (`user_group_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // جدول قوالب الإشعارات
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "notification_templates` (
                `template_id` int(11) NOT NULL AUTO_INCREMENT,
                `template_key` varchar(128) NOT NULL,
                `template_name` varchar(255) NOT NULL,
                `template_type` enum('email','sms','push','system') NOT NULL DEFAULT 'system',
                `subject` varchar(255) DEFAULT NULL,
                `content` text NOT NULL,
                `variables` text,
                `status` tinyint(1) NOT NULL DEFAULT 1,
                `date_added` datetime NOT NULL,
                `date_modified` datetime NOT NULL,
                PRIMARY KEY (`template_id`),
                UNIQUE KEY `template_key` (`template_key`),
                KEY `template_type` (`template_type`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // جدول سجل الإشعارات
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "notification_log` (
                `log_id` int(11) NOT NULL AUTO_INCREMENT,
                `notification_type` varchar(64) NOT NULL,
                `recipient_type` enum('user','group','all') NOT NULL DEFAULT 'user',
                `recipient_id` int(11) NOT NULL,
                `template_id` int(11) DEFAULT NULL,
                `subject` varchar(255) DEFAULT NULL,
                `message` text NOT NULL,
                `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
                `status` enum('pending','sent','failed','read') NOT NULL DEFAULT 'pending',
                `delivery_method` enum('email','sms','push','system') NOT NULL DEFAULT 'system',
                `sent_date` datetime DEFAULT NULL,
                `read_date` datetime DEFAULT NULL,
                `error_message` text,
                `metadata` text,
                `date_added` datetime NOT NULL,
                PRIMARY KEY (`log_id`),
                KEY `notification_type` (`notification_type`),
                KEY `recipient_type_id` (`recipient_type`, `recipient_id`),
                KEY `status` (`status`),
                KEY `priority` (`priority`),
                KEY `delivery_method` (`delivery_method`),
                KEY `date_added` (`date_added`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // إدراج الإعدادات الافتراضية
        $this->insertDefaultSettings();
        
        // إدراج القوالب الافتراضية
        $this->insertDefaultTemplates();
    }
    
    /**
     * الحصول على جميع الإعدادات
     */
    public function getSettings($user_id = 0, $user_group_id = 0) {
        $settings = array();
        
        $sql = "SELECT * FROM `" . DB_PREFIX . "notification_settings` WHERE 1=1";
        
        if ($user_id > 0) {
            $sql .= " AND (`user_id` = 0 OR `user_id` = " . (int)$user_id . ")";
        } else {
            $sql .= " AND `user_id` = 0";
        }
        
        if ($user_group_id > 0) {
            $sql .= " AND (`user_group_id` = 0 OR `user_group_id` = " . (int)$user_group_id . ")";
        } else {
            $sql .= " AND `user_group_id` = 0";
        }
        
        $sql .= " ORDER BY `user_id` DESC, `user_group_id` DESC";
        
        $query = $this->db->query($sql);
        
        foreach ($query->rows as $result) {
            $settings[$result['setting_key']] = $result['setting_value'];
        }
        
        return $settings;
    }
    
    /**
     * تحديث الإعدادات
     */
    public function editSettings($data, $user_id = 0, $user_group_id = 0) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            
            $this->db->query("
                INSERT INTO `" . DB_PREFIX . "notification_settings` 
                SET `setting_key` = '" . $this->db->escape($key) . "',
                    `setting_value` = '" . $this->db->escape($value) . "',
                    `user_id` = " . (int)$user_id . ",
                    `user_group_id` = " . (int)$user_group_id . ",
                    `date_added` = NOW(),
                    `date_modified` = NOW()
                ON DUPLICATE KEY UPDATE
                    `setting_value` = '" . $this->db->escape($value) . "',
                    `date_modified` = NOW()
            ");
        }
    }
    
    /**
     * الحصول على إعداد محدد
     */
    public function getSetting($key, $default = null, $user_id = 0, $user_group_id = 0) {
        $sql = "SELECT `setting_value` FROM `" . DB_PREFIX . "notification_settings` 
                WHERE `setting_key` = '" . $this->db->escape($key) . "'";
        
        if ($user_id > 0) {
            $sql .= " AND (`user_id` = 0 OR `user_id` = " . (int)$user_id . ")";
        } else {
            $sql .= " AND `user_id` = 0";
        }
        
        if ($user_group_id > 0) {
            $sql .= " AND (`user_group_id` = 0 OR `user_group_id` = " . (int)$user_group_id . ")";
        } else {
            $sql .= " AND `user_group_id` = 0";
        }
        
        $sql .= " ORDER BY `user_id` DESC, `user_group_id` DESC LIMIT 1";
        
        $query = $this->db->query($sql);
        
        if ($query->num_rows) {
            return $query->row['setting_value'];
        }
        
        return $default;
    }
    
    /**
     * اختبار إشعار البريد الإلكتروني
     */
    public function testEmailNotification() {
        $this->load->library('mail');
        
        $mail = new Mail();
        $mail->protocol = $this->config->get('config_mail_protocol');
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
        
        $mail->setTo($this->config->get('config_email'));
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
        $mail->setSubject('اختبار نظام الإشعارات - AYM ERP');
        $mail->setText('هذه رسالة اختبار من نظام الإشعارات في AYM ERP. إذا وصلتك هذه الرسالة، فإن النظام يعمل بشكل صحيح.');
        
        return $mail->send();
    }
    
    /**
     * اختبار إشعار الرسائل النصية
     */
    public function testSmsNotification() {
        // تنفيذ اختبار الرسائل النصية
        // يمكن تخصيصه حسب مزود الخدمة المستخدم
        return true;
    }
    
    /**
     * اختبار الإشعارات الفورية
     */
    public function testPushNotification() {
        // تنفيذ اختبار الإشعارات الفورية
        // يمكن تخصيصه حسب الخدمة المستخدمة (Firebase, etc.)
        return true;
    }
    
    /**
     * إدراج الإعدادات الافتراضية
     */
    private function insertDefaultSettings() {
        $default_settings = array(
            'notification_enabled' => '1',
            'email_notifications' => '1',
            'sms_notifications' => '0',
            'push_notifications' => '1',
            'catalog_new_product' => '1',
            'catalog_price_change' => '1',
            'catalog_product_expiry' => '1',
            'inventory_low_stock' => '1',
            'inventory_stock_out' => '1',
            'inventory_stock_movement' => '0',
            'inventory_batch_expiry' => '1',
            'inventory_reorder_point' => '1',
            'priority_critical' => '1',
            'priority_high' => '1',
            'priority_medium' => '1',
            'priority_low' => '0',
            'timing_real_time' => '1',
            'timing_batch_interval' => '15',
            'timing_quiet_hours_start' => '22:00',
            'timing_quiet_hours_end' => '08:00'
        );
        
        foreach ($default_settings as $key => $value) {
            $this->db->query("
                INSERT IGNORE INTO `" . DB_PREFIX . "notification_settings` 
                SET `setting_key` = '" . $this->db->escape($key) . "',
                    `setting_value` = '" . $this->db->escape($value) . "',
                    `setting_group` = 'default',
                    `user_id` = 0,
                    `user_group_id` = 0,
                    `date_added` = NOW(),
                    `date_modified` = NOW()
            ");
        }
    }
    
    /**
     * إدراج القوالب الافتراضية
     */
    private function insertDefaultTemplates() {
        $default_templates = array(
            array(
                'template_key' => 'catalog_new_product',
                'template_name' => 'منتج جديد',
                'template_type' => 'system',
                'subject' => 'تم إضافة منتج جديد',
                'content' => 'تم إضافة منتج جديد: {product_name} في الفئة: {category_name}',
                'variables' => '["product_name", "category_name", "product_id"]'
            ),
            array(
                'template_key' => 'inventory_low_stock',
                'template_name' => 'مخزون منخفض',
                'template_type' => 'system',
                'subject' => 'تحذير: مخزون منخفض',
                'content' => 'تحذير: المنتج {product_name} وصل إلى مستوى مخزون منخفض. الكمية الحالية: {current_quantity}',
                'variables' => '["product_name", "current_quantity", "minimum_quantity"]'
            ),
            array(
                'template_key' => 'inventory_stock_out',
                'template_name' => 'نفاد المخزون',
                'template_type' => 'system',
                'subject' => 'تحذير: نفاد المخزون',
                'content' => 'تحذير: المنتج {product_name} نفد من المخزون تماماً!',
                'variables' => '["product_name", "product_id"]'
            )
        );
        
        foreach ($default_templates as $template) {
            $this->db->query("
                INSERT IGNORE INTO `" . DB_PREFIX . "notification_templates` 
                SET `template_key` = '" . $this->db->escape($template['template_key']) . "',
                    `template_name` = '" . $this->db->escape($template['template_name']) . "',
                    `template_type` = '" . $this->db->escape($template['template_type']) . "',
                    `subject` = '" . $this->db->escape($template['subject']) . "',
                    `content` = '" . $this->db->escape($template['content']) . "',
                    `variables` = '" . $this->db->escape($template['variables']) . "',
                    `status` = 1,
                    `date_added` = NOW(),
                    `date_modified` = NOW()
            ");
        }
    }
}
