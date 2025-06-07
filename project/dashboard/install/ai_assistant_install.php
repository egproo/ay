<?php
/**
 * نظام أيم ERP: سكريبت تثبيت المساعد الذكي
 * هذا الملف يساعد في تثبيت وإعداد المساعد الذكي في نظام أيم ERP
 * يقوم بإنشاء الجداول اللازمة وإعداد التكامل مع مركز الإشعارات ونظام الصلاحيات
 */

// التأكد من أن الملف يتم تنفيذه من داخل النظام
if (!defined('DIR_APPLICATION')) {
    // تحديد المسارات الأساسية
    define('DIR_APPLICATION', str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/');
    define('DIR_SYSTEM', DIR_APPLICATION . 'system/');
    define('DIR_CONFIG', DIR_SYSTEM . 'config/');
    define('DIR_DATABASE', DIR_SYSTEM . 'database/');
}

// تحميل الوظائف الأساسية
require_once(DIR_SYSTEM . 'startup.php');

// تحميل إعدادات قاعدة البيانات
require_once(DIR_CONFIG . 'database.php');

// تعيين الإصدار الحالي للمساعد الذكي
define('AI_ASSISTANT_VERSION', '1.0.0');

// الاتصال بقاعدة البيانات
try {
    $db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
    
    echo "<div class='container'>";
    echo "<div class='panel panel-default'>";
    echo "<div class='panel-heading'>";
    echo "<h1><i class='fa fa-robot'></i> تثبيت المساعد الذكي لنظام أيم ERP</h1>";
    echo "<p>الإصدار: " . AI_ASSISTANT_VERSION . "</p>";
    echo "</div>";
    echo "<div class='panel-body'>";
    
    // إنشاء جداول قاعدة البيانات
    echo "<div class='alert alert-info'><i class='fa fa-database'></i> <strong>إنشاء جداول قاعدة البيانات</strong></div>";
    
    // جدول إعدادات المساعد الذكي
    $db->query("CREATE TABLE IF NOT EXISTS `cod_ai_assistant_settings` (
      `user_id` int(11) NOT NULL,
      `settings` text NOT NULL,
      `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `date_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    
    echo "<div class='alert alert-success'><i class='fa fa-check-circle'></i> تم إنشاء جدول إعدادات المساعد الذكي بنجاح.</div>";
    
    // جدول محادثات المساعد الذكي
    $db->query("CREATE TABLE IF NOT EXISTS `cod_ai_assistant_conversation` (
      `conversation_id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `sender` varchar(10) NOT NULL,
      `message` text NOT NULL,
      `context` text NULL,
      `date_added` datetime NOT NULL,
      PRIMARY KEY (`conversation_id`),
      KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    
    echo "<div class='alert alert-success'><i class='fa fa-check-circle'></i> تم إنشاء جدول محادثات المساعد الذكي بنجاح.</div>";
    
    // جدول تكامل المساعد الذكي مع مركز الإشعارات
    $db->query("CREATE TABLE IF NOT EXISTS `cod_ai_assistant_notification` (
      `notification_id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `type` varchar(50) NOT NULL,
      `title` varchar(255) NOT NULL,
      `message` text NOT NULL,
      `is_read` tinyint(1) NOT NULL DEFAULT '0',
      `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`notification_id`),
      KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    
    echo "<div class='alert alert-success'><i class='fa fa-check-circle'></i> تم إنشاء جدول تكامل المساعد الذكي مع مركز الإشعارات بنجاح.</div>";
    
    // إضافة الإعدادات الافتراضية للمساعد الذكي
    echo "<div class='alert alert-info'><i class='fa fa-cog'></i> <strong>إضافة الإعدادات الافتراضية</strong></div>";
    
    // الإعدادات الافتراضية
    $default_settings = array(
        'ai_model' => 'default',
        'ai_save_history' => 1,
        'ai_suggestions' => 1,
        'ai_auto_complete' => 0,
        'ai_access_sales' => 1,
        'ai_access_inventory' => 1,
        'ai_access_customers' => 1,
        'ai_access_reports' => 1,
        'ai_access_accounting' => 1,
        'ai_access_hr' => 0,
        'ai_access_workflow' => 1,
        'ai_notification_enabled' => 1,
        'ai_notification_frequency' => 'daily',
        'ai_insights_enabled' => 1
    );
    
    // الحصول على قائمة المستخدمين
    $query = $db->query("SELECT user_id FROM cod_user");
    
    foreach ($query->rows as $user) {
        $db->query("INSERT IGNORE INTO cod_ai_assistant_settings SET 
            user_id = '" . (int)$user['user_id'] . "',
            settings = '" . $db->escape(json_encode($default_settings)) . "',
            date_added = NOW(),
            date_modified = NOW()");
    }
    
    echo "<div class='alert alert-success'><i class='fa fa-check-circle'></i> تم إضافة الإعدادات الافتراضية للمستخدمين بنجاح.</div>";
    
    // إضافة صلاحيات المساعد الذكي لمجموعات المستخدمين
    echo "<div class='alert alert-info'><i class='fa fa-lock'></i> <strong>إعداد صلاحيات المساعد الذكي</strong></div>";
    
    // التحقق من وجود صلاحيات المساعد الذكي في جدول الصلاحيات
    $permission_exists = $db->query("SELECT COUNT(*) AS total FROM information_schema.columns 
        WHERE table_schema = '" . DB_DATABASE . "' 
        AND table_name = 'cod_user_group_permission' 
        AND column_name = 'permission'");
    
    if ($permission_exists->row['total'] > 0) {
        // الحصول على مجموعات المستخدمين
        $user_groups = $db->query("SELECT user_group_id FROM cod_user_group");
        
        // إضافة صلاحيات المساعد الذكي لكل مجموعة مستخدمين
        foreach ($user_groups->rows as $user_group) {
            // التحقق من وجود الصلاحيات
            $permission_query = $db->query("SELECT permission FROM cod_user_group_permission 
                WHERE user_group_id = '" . (int)$user_group['user_group_id'] . "'");
            
            if ($permission_query->num_rows) {
                $permissions = json_decode($permission_query->row['permission'], true);
                
                // إضافة صلاحيات المساعد الذكي
                if (!isset($permissions['access']['common/ai_assistant'])) {
                    $permissions['access']['common/ai_assistant'] = 1;
                }
                
                if (!isset($permissions['modify']['common/ai_assistant'])) {
                    $permissions['modify']['common/ai_assistant'] = 1;
                }
                
                // تحديث الصلاحيات
                $db->query("UPDATE cod_user_group_permission SET 
                    permission = '" . $db->escape(json_encode($permissions)) . "' 
                    WHERE user_group_id = '" . (int)$user_group['user_group_id'] . "'");
            }
        }
        
        echo "<div class='alert alert-success'><i class='fa fa-check-circle'></i> تم إعداد صلاحيات المساعد الذكي لمجموعات المستخدمين بنجاح.</div>";
    }
    
    // إضافة تكامل المساعد الذكي مع مركز الإشعارات
    echo "<div class='alert alert-info'><i class='fa fa-bell'></i> <strong>إعداد تكامل المساعد الذكي مع مركز الإشعارات</strong></div>";
    
    // إضافة إشعار ترحيبي للمستخدمين
    $welcome_notification = "مرحباً بك في المساعد الذكي لنظام أيم ERP! يمكنك الآن الاستفادة من قدرات الذكاء الاصطناعي لتحسين إنتاجيتك.";
    
    foreach ($query->rows as $user) {
        $db->query("INSERT INTO cod_ai_assistant_notification SET 
            user_id = '" . (int)$user['user_id'] . "',
            type = 'welcome',
            title = 'مرحباً بك في المساعد الذكي',
            message = '" . $db->escape($welcome_notification) . "',
            is_read = 0,
            date_added = NOW()");
    }
    
    echo "<div class='alert alert-success'><i class='fa fa-check-circle'></i> تم إعداد تكامل المساعد الذكي مع مركز الإشعارات بنجاح.</div>";
    
    echo "<div class='alert alert-success'><i class='fa fa-check-circle'></i> <strong>اكتمل التثبيت</strong></div>";
    echo "<p>تم تثبيت المساعد الذكي بنجاح. يمكنك الآن استخدام المساعد الذكي في نظام أيم ERP.</p>";
    echo "<div class='text-center'><a href='../index.php?route=common/dashboard' class='btn btn-primary'><i class='fa fa-home'></i> العودة إلى لوحة التحكم</a></div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='container'>";
    echo "<div class='panel panel-danger'>";
    echo "<div class='panel-heading'>";
    echo "<h1><i class='fa fa-exclamation-triangle'></i> خطأ في التثبيت</h1>";
    echo "</div>";
    echo "<div class='panel-body'>";
    echo "<div class='alert alert-danger'><i class='fa fa-times-circle'></i> " . $e->getMessage() . "</div>";
    echo "<div class='text-center'><a href='../index.php?route=common/dashboard' class='btn btn-default'><i class='fa fa-home'></i> العودة إلى لوحة التحكم</a></div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

// دالة للتحقق من وجود عمود في جدول
function columnExists($db, $table, $column) {
    $query = $db->query("SELECT COUNT(*) AS total FROM information_schema.columns 
        WHERE table_schema = '" . DB_DATABASE . "' 
        AND table_name = '" . $table . "' 
        AND column_name = '" . $column . "'");
    
    return $query->row['total'] > 0;
}