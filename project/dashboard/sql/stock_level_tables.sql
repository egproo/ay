-- جدول مستويات المخزون
CREATE TABLE IF NOT EXISTS `oc_product_stock_level` (
  `stock_level_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `minimum_stock` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `reorder_point` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `maximum_stock` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`stock_level_id`),
  UNIQUE KEY `product_branch_unit` (`product_id`,`branch_id`,`unit_id`),
  KEY `product_id` (`product_id`),
  KEY `branch_id` (`branch_id`),
  KEY `unit_id` (`unit_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- إضافة إعدادات مستويات المخزون إلى جدول الإعدادات
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES
(0, 'config', 'config_auto_reorder', '0', 0),
(0, 'config', 'config_stock_level_notification', '1', 0),
(0, 'config', 'config_stock_level_notification_email', '1', 0),
(0, 'config', 'config_stock_level_notification_system', '1', 0)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
