-- Tabla para almacenar información de lotes/dفعات
CREATE TABLE IF NOT EXISTS `oc_product_batch` (
  `batch_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `batch_number` varchar(100) NOT NULL,
  `quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `manufacturing_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `notes` text,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`batch_id`),
  KEY `product_id` (`product_id`),
  KEY `branch_id` (`branch_id`),
  KEY `unit_id` (`unit_id`),
  KEY `batch_number` (`batch_number`),
  KEY `expiry_date` (`expiry_date`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabla para almacenar el historial de movimientos de lotes/دفعات
CREATE TABLE IF NOT EXISTS `oc_product_batch_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `user_id` int(11) NOT NULL,
  `notes` text,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`history_id`),
  KEY `batch_id` (`batch_id`),
  KEY `action` (`action`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Agregar campo para días de advertencia de caducidad a la tabla de productos
ALTER TABLE `oc_product` ADD COLUMN IF NOT EXISTS `expiry_warning_days` int(11) NOT NULL DEFAULT '30';

-- Agregar campo para método de selección de inventario (FIFO/FEFO) a la tabla de configuración
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES
(0, 'config', 'config_inventory_picking_method', 'fefo', 0),
(0, 'config', 'config_enable_batch_tracking', '1', 0),
(0, 'config', 'config_batch_number_required', '1', 0),
(0, 'config', 'config_expiry_date_required', '1', 0),
(0, 'config', 'config_manufacturing_date_required', '0', 0),
(0, 'config', 'config_expiry_notification_days', '30', 0),
(0, 'config', 'config_expiry_notification_email', '1', 0),
(0, 'config', 'config_expiry_notification_system', '1', 0)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
