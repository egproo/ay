-- جدول المستودعات
CREATE TABLE IF NOT EXISTS `oc_warehouse` (
  `warehouse_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `address` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`warehouse_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- جدول الوحدات
CREATE TABLE IF NOT EXISTS `oc_unit` (
  `unit_id` varchar(32) NOT NULL,
  `name` varchar(64) NOT NULL,
  `symbol` varchar(8) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`unit_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- جدول تحويل الوحدات
CREATE TABLE IF NOT EXISTS `oc_unit_conversion` (
  `conversion_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_unit_id` varchar(32) NOT NULL,
  `to_unit_id` varchar(32) NOT NULL,
  `conversion_factor` decimal(15,8) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`conversion_id`),
  UNIQUE KEY `from_to_unit` (`from_unit_id`,`to_unit_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- جدول تاريخ تحويل الوحدات
CREATE TABLE IF NOT EXISTS `oc_unit_conversion_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_unit_id` varchar(32) NOT NULL,
  `to_unit_id` varchar(32) NOT NULL,
  `old_factor` decimal(15,8) NOT NULL,
  `new_factor` decimal(15,8) NOT NULL,
  `date_added` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`history_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- جدول مخزون المنتج في المستودعات
CREATE TABLE IF NOT EXISTS `oc_product_warehouse` (
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`product_id`,`warehouse_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- جدول حركة المخزون
CREATE TABLE IF NOT EXISTS `oc_stock_movement` (
  `movement_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `unit_id` varchar(32) NOT NULL,
  `movement_type` varchar(16) NOT NULL COMMENT 'in, out',
  `reference_type` varchar(32) NOT NULL COMMENT 'purchase, sale, adjustment, transfer, etc.',
  `reference_id` int(11) NOT NULL,
  `cost` decimal(15,4) DEFAULT NULL,
  `notes` text,
  `date_added` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`movement_id`),
  KEY `product_id` (`product_id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `reference_type` (`reference_type`,`reference_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- جدول القيود المحاسبية
CREATE TABLE IF NOT EXISTS `oc_accounting_entry` (
  `entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_type` varchar(32) NOT NULL,
  `reference_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(15,4) NOT NULL,
  `date_added` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`entry_id`),
  KEY `reference_type` (`reference_type`,`reference_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- جدول تفاصيل القيود المحاسبية
CREATE TABLE IF NOT EXISTS `oc_accounting_entry_line` (
  `line_id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `credit` decimal(15,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`line_id`),
  KEY `entry_id` (`entry_id`),
  KEY `account_id` (`account_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- إضافة حقل الوحدة الأساسية إلى جدول المنتجات
ALTER TABLE `oc_product` ADD COLUMN `base_unit_id` varchar(32) DEFAULT NULL AFTER `model`;

-- إضافة حقل التكلفة إلى جدول المنتجات
ALTER TABLE `oc_product` ADD COLUMN `cost` decimal(15,4) NOT NULL DEFAULT '0.0000' AFTER `price`;

-- إضافة إعدادات المحاسبة
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES
(0, 'config', 'config_accounting_enabled', '1', 0),
(0, 'config', 'config_inventory_account', '1', 0),
(0, 'config', 'config_cogs_account', '2', 0),
(0, 'config', 'config_purchase_account', '3', 0),
(0, 'config', 'config_sales_account', '4', 0),
(0, 'config', 'config_inventory_adjustment_account', '5', 0);

-- إضافة بيانات أولية للوحدات
INSERT INTO `oc_unit` (`unit_id`, `name`, `symbol`, `status`, `date_added`, `date_modified`) VALUES
('PCS', 'قطعة', 'قطعة', 1, NOW(), NOW()),
('BOX', 'صندوق', 'صندوق', 1, NOW(), NOW()),
('KG', 'كيلوجرام', 'كجم', 1, NOW(), NOW()),
('G', 'جرام', 'جم', 1, NOW(), NOW()),
('L', 'لتر', 'لتر', 1, NOW(), NOW()),
('ML', 'مليلتر', 'مل', 1, NOW(), NOW());

-- إضافة بيانات أولية لتحويل الوحدات
INSERT INTO `oc_unit_conversion` (`from_unit_id`, `to_unit_id`, `conversion_factor`, `date_added`, `date_modified`) VALUES
('KG', 'G', 1000, NOW(), NOW()),
('L', 'ML', 1000, NOW(), NOW());

-- إضافة مستودع افتراضي
INSERT INTO `oc_warehouse` (`name`, `address`, `status`, `date_added`, `date_modified`) VALUES
('المستودع الرئيسي', 'العنوان الرئيسي', 1, NOW(), NOW());
