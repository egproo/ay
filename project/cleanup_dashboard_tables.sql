-- ========================================================================
-- تنظيف جداول Dashboard المضافة سابقاً
-- Cleanup Dashboard Tables Added Previously
-- ========================================================================

-- حذف الجداول المضافة للـ Dashboard المعقد
DROP TABLE IF EXISTS cod_dashboard_widget;
DROP TABLE IF EXISTS cod_user_dashboard;
DROP TABLE IF EXISTS cod_dashboard_kpi;
DROP TABLE IF EXISTS cod_system_notifications;
DROP TABLE IF EXISTS cod_notification_user;
DROP TABLE IF EXISTS cod_internal_conversation;
DROP TABLE IF EXISTS cod_internal_message;
DROP TABLE IF EXISTS cod_message_recipient;

-- حذف أي جداول أخرى مرتبطة بالـ Dashboard المعقد
DROP TABLE IF EXISTS cod_dashboard_settings;
DROP TABLE IF EXISTS cod_dashboard_layout;
DROP TABLE IF EXISTS cod_widget_data;
DROP TABLE IF EXISTS cod_kpi_data;
DROP TABLE IF EXISTS cod_dashboard_permissions;

-- تنظيف أي بيانات مرتبطة في جداول النظام
DELETE FROM cod_user_group_permission WHERE route LIKE 'dashboard/%';
DELETE FROM cod_user_group_permission WHERE route = 'common/dashboard_enhanced';

-- إعادة تعيين صلاحيات Dashboard البسيط
INSERT IGNORE INTO cod_user_group_permission (user_group_id, permission, route) VALUES
(1, 'access', 'common/dashboard'),
(1, 'modify', 'common/dashboard');

-- ========================================================================
-- انتهاء التنظيف
-- ========================================================================
