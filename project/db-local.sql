-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS `app_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `app_db`;

-- استيراد هيكل قاعدة البيانات من ملف db.sql
-- يجب استيراد ملف db.sql بعد تنفيذ هذا الملف

-- إنشاء مستخدم افتراضي للوحة التحكم
INSERT INTO `cod_user` (`user_id`, `user_group_id`, `username`, `password`, `salt`, `firstname`, `lastname`, `email`, `image`, `code`, `ip`, `status`, `date_added`) VALUES
(1, 1, 'admin', '$2y$10$5wz9kwQrDaH5lJ/uW.KIAOgFnBGGMXy.MiJ/QzJtxnUOJHQOmqjGC', '', 'Admin', 'User', 'admin@example.com', '', '', '127.0.0.1', 1, NOW());

-- إنشاء مجموعة المستخدمين الافتراضية (المدير)
INSERT INTO `cod_user_group` (`user_group_id`, `name`, `permission`) VALUES
(1, 'Administrator', '{"access":["common\\/column_left","common\\/dashboard","common\\/footer","common\\/header","common\\/login","common\\/logout","common\\/profile","error\\/not_found","error\\/permission","extension\\/dashboard\\/activity","extension\\/dashboard\\/chart","extension\\/dashboard\\/customer","extension\\/dashboard\\/map","extension\\/dashboard\\/online","extension\\/dashboard\\/order","extension\\/dashboard\\/recent","extension\\/dashboard\\/sale","extension\\/event","extension\\/extension","extension\\/extension\\/dashboard","extension\\/module","inventory\\/movement_history"],"modify":["common\\/column_left","common\\/dashboard","common\\/footer","common\\/header","common\\/login","common\\/logout","common\\/profile","error\\/not_found","error\\/permission","extension\\/dashboard\\/activity","extension\\/dashboard\\/chart","extension\\/dashboard\\/customer","extension\\/dashboard\\/map","extension\\/dashboard\\/online","extension\\/dashboard\\/order","extension\\/dashboard\\/recent","extension\\/dashboard\\/sale","extension\\/event","extension\\/extension","extension\\/extension\\/dashboard","extension\\/module","inventory\\/movement_history"]}');

-- إنشاء إعدادات النظام الأساسية
INSERT INTO `cod_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES
(0, 'config', 'config_name', 'AYM ERP System', 0),
(0, 'config', 'config_owner', 'AYM ERP', 0),
(0, 'config', 'config_address', 'Address', 0),
(0, 'config', 'config_email', 'admin@example.com', 0),
(0, 'config', 'config_telephone', '123456789', 0),
(0, 'config', 'config_meta_title', 'AYM ERP System', 0),
(0, 'config', 'config_meta_description', 'AYM ERP System', 0),
(0, 'config', 'config_meta_keyword', 'erp, inventory, accounting', 0),
(0, 'config', 'config_theme', 'default', 0),
(0, 'config', 'config_layout_id', '1', 0),
(0, 'config', 'config_country_id', '63', 0),
(0, 'config', 'config_zone_id', '989', 0),
(0, 'config', 'config_language', 'ar', 0),
(0, 'config', 'config_admin_language', 'ar', 0),
(0, 'config', 'config_currency', 'EGP', 0),
(0, 'config', 'config_currency_auto', '0', 0),
(0, 'config', 'config_length_class_id', '1', 0),
(0, 'config', 'config_weight_class_id', '1', 0),
(0, 'config', 'config_timezone', 'Africa/Cairo', 0),
(0, 'config', 'config_limit_admin', '20', 0),
(0, 'config', 'config_secure', '0', 0),
(0, 'config', 'config_password', '1', 0),
(0, 'config', 'config_shared', '0', 0),
(0, 'config', 'config_encryption', 'aym-erp-key', 0),
(0, 'config', 'config_compression', '0', 0),
(0, 'config', 'config_error_display', '1', 0),
(0, 'config', 'config_error_log', '1', 0),
(0, 'config', 'config_error_filename', 'error.log', 0),
(0, 'config', 'config_api_id', '1', 0);

-- إنشاء API للنظام
INSERT INTO `cod_api` (`api_id`, `username`, `key`, `status`, `date_added`, `date_modified`) VALUES
(1, 'Default', 'api-key-for-your-application', 1, NOW(), NOW());

-- إنشاء فرع افتراضي
INSERT INTO `cod_branch` (`branch_id`, `name`, `address`, `telephone`, `email`, `status`, `date_added`) VALUES
(1, 'الفرع الرئيسي', 'عنوان الفرع الرئيسي', '123456789', 'main@example.com', 1, NOW());

-- إنشاء مستودع افتراضي
INSERT INTO `cod_warehouse` (`warehouse_id`, `branch_id`, `name`, `code`, `address`, `status`, `date_added`) VALUES
(1, 1, 'المستودع الرئيسي', 'MAIN', 'عنوان المستودع الرئيسي', 1, NOW());

-- إنشاء وحدة قياس افتراضية
INSERT INTO `cod_unit` (`unit_id`, `name`, `code`, `value`, `decimal_places`, `status`) VALUES
(1, 'قطعة', 'PCS', 1.00, 0, 1);

-- إنشاء فئة منتجات افتراضية
INSERT INTO `cod_category` (`category_id`, `parent_id`, `name`, `status`, `date_added`) VALUES
(1, 0, 'الفئة الافتراضية', 1, NOW());

-- إنشاء منتج افتراضي
INSERT INTO `cod_product` (`product_id`, `model`, `sku`, `name`, `description`, `meta_title`, `meta_description`, `meta_keyword`, `tag`, `price`, `cost`, `quantity`, `minimum`, `subtract`, `stock_status_id`, `shipping`, `date_available`, `weight`, `weight_class_id`, `length`, `width`, `height`, `length_class_id`, `status`, `date_added`, `date_modified`, `viewed`) VALUES
(1, 'منتج-1', 'SKU001', 'منتج افتراضي', 'وصف المنتج الافتراضي', 'منتج افتراضي', '', '', '', 100.0000, 80.0000, 100, 1, 1, 1, 1, '2023-01-01', 0.00, 1, 0.00, 0.00, 0.00, 1, 1, NOW(), NOW(), 0);

-- ربط المنتج بالفئة
INSERT INTO `cod_product_to_category` (`product_id`, `category_id`) VALUES
(1, 1);

-- إنشاء حالة طلب افتراضية
INSERT INTO `cod_order_status` (`order_status_id`, `language_id`, `name`) VALUES
(1, 1, 'قيد المعالجة'),
(2, 1, 'مكتمل'),
(3, 1, 'ملغي');

-- إنشاء حالة مخزون افتراضية
INSERT INTO `cod_stock_status` (`stock_status_id`, `language_id`, `name`) VALUES
(1, 1, 'متوفر'),
(2, 1, 'غير متوفر');

-- إنشاء حركة مخزون افتراضية
INSERT INTO `cod_stock_movement` (`movement_id`, `product_id`, `warehouse_id`, `unit_id`, `quantity`, `cost`, `movement_type`, `reference_type`, `reference_id`, `notes`, `user_id`, `date_added`) VALUES
(1, 1, 1, 1, 100, 80.0000, 'in', 'initial', 1, 'رصيد افتتاحي', 1, NOW());

-- تحديث رصيد المخزون
INSERT INTO `cod_product_warehouse` (`product_id`, `warehouse_id`, `quantity`) VALUES
(1, 1, 100);
