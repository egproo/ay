-- AYM ERP - Inventory Current Stock Database Tables
-- Version: 1.0.0
-- Date: 2024

-- Stock Movement Table (for tracking all inventory movements)
CREATE TABLE IF NOT EXISTS `cod_stock_movement` (
  `movement_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL DEFAULT 1,
  `type` varchar(50) NOT NULL COMMENT 'in, out, adjustment_in, adjustment_out, transfer_in, transfer_out',
  `quantity` decimal(15,4) NOT NULL,
  `unit_cost` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `total_cost` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'purchase, sale, adjustment, transfer, etc',
  `reference_id` int(11) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `notes` text,
  `user_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`movement_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_warehouse_id` (`warehouse_id`),
  KEY `idx_type` (`type`),
  KEY `idx_reference` (`reference_type`, `reference_id`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Reservation Table (for reserved stock)
CREATE TABLE IF NOT EXISTS `cod_stock_reservation` (
  `reservation_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL DEFAULT 1,
  `quantity` decimal(15,4) NOT NULL,
  `reference_type` varchar(50) NOT NULL COMMENT 'order, quote, etc',
  `reference_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, released, expired',
  `expiry_date` datetime DEFAULT NULL,
  `notes` text,
  `user_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`reservation_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_warehouse_id` (`warehouse_id`),
  KEY `idx_status` (`status`),
  KEY `idx_reference` (`reference_type`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Warehouse Table (if not exists)
CREATE TABLE IF NOT EXISTS `cod_warehouse` (
  `warehouse_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'main' COMMENT 'main, branch, virtual',
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `country_id` int(11) DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(3) NOT NULL DEFAULT 0,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`warehouse_id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default warehouse if not exists
INSERT IGNORE INTO `cod_warehouse` (`warehouse_id`, `name`, `code`, `type`, `status`, `date_added`, `date_modified`) 
VALUES (1, 'المستودع الرئيسي', 'MAIN', 'main', 1, NOW(), NOW());

-- Stock Level Alerts Table
CREATE TABLE IF NOT EXISTS `cod_stock_alert` (
  `alert_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `alert_type` varchar(20) NOT NULL COMMENT 'low_stock, out_of_stock, overstock',
  `current_stock` decimal(15,4) NOT NULL,
  `threshold_value` decimal(15,4) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, acknowledged, resolved',
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_date` datetime DEFAULT NULL,
  `resolved_date` datetime DEFAULT NULL,
  `notes` text,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`alert_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_warehouse_id` (`warehouse_id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_status` (`status`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Snapshot Table (for historical tracking)
CREATE TABLE IF NOT EXISTS `cod_stock_snapshot` (
  `snapshot_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `snapshot_date` date NOT NULL,
  `opening_stock` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `stock_in` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `stock_out` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `adjustments` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `closing_stock` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `total_value` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`snapshot_id`),
  UNIQUE KEY `unique_snapshot` (`product_id`, `warehouse_id`, `snapshot_date`),
  KEY `idx_snapshot_date` (`snapshot_date`),
  KEY `idx_product_warehouse` (`product_id`, `warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Valuation Methods Table
CREATE TABLE IF NOT EXISTS `cod_stock_valuation_method` (
  `method_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text,
  `formula` text,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`method_id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default valuation methods
INSERT IGNORE INTO `cod_stock_valuation_method` (`name`, `code`, `description`, `is_default`, `status`, `date_added`) VALUES
('متوسط التكلفة المرجح', 'WEIGHTED_AVERAGE', 'حساب التكلفة بناء على المتوسط المرجح للمشتريات', 1, 1, NOW()),
('الوارد أولاً صادر أولاً', 'FIFO', 'الوارد أولاً صادر أولاً - First In First Out', 0, 1, NOW()),
('الوارد أخيراً صادر أولاً', 'LIFO', 'الوارد أخيراً صادر أولاً - Last In First Out', 0, 1, NOW()),
('التكلفة المحددة', 'SPECIFIC_COST', 'تحديد التكلفة لكل وحدة بشكل منفصل', 0, 1, NOW());

-- Stock Aging Analysis Table
CREATE TABLE IF NOT EXISTS `cod_stock_aging` (
  `aging_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `unit_cost` decimal(15,4) NOT NULL,
  `total_value` decimal(15,4) NOT NULL,
  `receipt_date` date NOT NULL,
  `age_days` int(11) NOT NULL,
  `age_group` varchar(20) NOT NULL COMMENT '0-30, 31-60, 61-90, 91-180, 180+',
  `last_movement_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `date_calculated` datetime NOT NULL,
  PRIMARY KEY (`aging_id`),
  KEY `idx_product_warehouse` (`product_id`, `warehouse_id`),
  KEY `idx_age_group` (`age_group`),
  KEY `idx_receipt_date` (`receipt_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Reorder Rules Table
CREATE TABLE IF NOT EXISTS `cod_stock_reorder_rule` (
  `rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `min_quantity` decimal(15,4) NOT NULL,
  `max_quantity` decimal(15,4) NOT NULL,
  `reorder_quantity` decimal(15,4) NOT NULL,
  `lead_time_days` int(11) NOT NULL DEFAULT 0,
  `safety_stock` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `auto_reorder` tinyint(1) NOT NULL DEFAULT 0,
  `supplier_id` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`rule_id`),
  UNIQUE KEY `unique_rule` (`product_id`, `warehouse_id`),
  KEY `idx_auto_reorder` (`auto_reorder`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Transfer Requests Table
CREATE TABLE IF NOT EXISTS `cod_stock_transfer_request` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `request_number` varchar(50) NOT NULL,
  `from_warehouse_id` int(11) NOT NULL,
  `to_warehouse_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, rejected, completed',
  `priority` varchar(10) NOT NULL DEFAULT 'normal' COMMENT 'low, normal, high, urgent',
  `requested_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `request_date` datetime NOT NULL,
  `required_date` datetime DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `completion_date` datetime DEFAULT NULL,
  `notes` text,
  `total_items` int(11) NOT NULL DEFAULT 0,
  `total_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `total_value` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`request_id`),
  UNIQUE KEY `request_number` (`request_number`),
  KEY `idx_from_warehouse` (`from_warehouse_id`),
  KEY `idx_to_warehouse` (`to_warehouse_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_request_date` (`request_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Transfer Request Items Table
CREATE TABLE IF NOT EXISTS `cod_stock_transfer_request_item` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `requested_quantity` decimal(15,4) NOT NULL,
  `approved_quantity` decimal(15,4) DEFAULT NULL,
  `transferred_quantity` decimal(15,4) DEFAULT NULL,
  `unit_cost` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `total_cost` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `lot_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `notes` text,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`item_id`),
  KEY `idx_request_id` (`request_id`),
  KEY `idx_product_id` (`product_id`),
  FOREIGN KEY (`request_id`) REFERENCES `cod_stock_transfer_request` (`request_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_product_warehouse_date` ON `cod_stock_movement` (`product_id`, `warehouse_id`, `date_added`);
CREATE INDEX IF NOT EXISTS `idx_stock_calculation` ON `cod_stock_movement` (`product_id`, `warehouse_id`, `type`);
CREATE INDEX IF NOT EXISTS `idx_reservation_active` ON `cod_stock_reservation` (`product_id`, `warehouse_id`, `status`);

-- Create views for easier querying
CREATE OR REPLACE VIEW `view_current_stock` AS
SELECT 
    p.product_id,
    p.sku,
    pd.name as product_name,
    p.model,
    w.warehouse_id,
    w.name as warehouse_name,
    COALESCE(stock_in.total_in, 0) - COALESCE(stock_out.total_out, 0) as current_stock,
    COALESCE(reserved.total_reserved, 0) as reserved_stock,
    (COALESCE(stock_in.total_in, 0) - COALESCE(stock_out.total_out, 0) - COALESCE(reserved.total_reserved, 0)) as available_stock,
    p.cost as unit_cost,
    ((COALESCE(stock_in.total_in, 0) - COALESCE(stock_out.total_out, 0)) * p.cost) as total_value,
    p.minimum as reorder_level,
    p.maximum as max_level,
    last_movement.last_date as last_movement_date,
    CASE 
        WHEN (COALESCE(stock_in.total_in, 0) - COALESCE(stock_out.total_out, 0)) <= 0 THEN 'out_of_stock'
        WHEN (COALESCE(stock_in.total_in, 0) - COALESCE(stock_out.total_out, 0)) <= p.minimum THEN 'low_stock'
        WHEN (COALESCE(stock_in.total_in, 0) - COALESCE(stock_out.total_out, 0)) >= p.maximum THEN 'overstock'
        ELSE 'in_stock'
    END as stock_status
FROM cod_product p
CROSS JOIN cod_warehouse w
LEFT JOIN cod_product_description pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
LEFT JOIN (
    SELECT product_id, warehouse_id, SUM(quantity) as total_in
    FROM cod_stock_movement 
    WHERE type IN ('in', 'adjustment_in', 'transfer_in')
    GROUP BY product_id, warehouse_id
) stock_in ON (p.product_id = stock_in.product_id AND w.warehouse_id = stock_in.warehouse_id)
LEFT JOIN (
    SELECT product_id, warehouse_id, SUM(quantity) as total_out
    FROM cod_stock_movement 
    WHERE type IN ('out', 'adjustment_out', 'transfer_out')
    GROUP BY product_id, warehouse_id
) stock_out ON (p.product_id = stock_out.product_id AND w.warehouse_id = stock_out.warehouse_id)
LEFT JOIN (
    SELECT product_id, warehouse_id, SUM(quantity) as total_reserved
    FROM cod_stock_reservation 
    WHERE status = 'active'
    GROUP BY product_id, warehouse_id
) reserved ON (p.product_id = reserved.product_id AND w.warehouse_id = reserved.warehouse_id)
LEFT JOIN (
    SELECT product_id, warehouse_id, MAX(date_added) as last_date
    FROM cod_stock_movement
    GROUP BY product_id, warehouse_id
) last_movement ON (p.product_id = last_movement.product_id AND w.warehouse_id = last_movement.warehouse_id)
WHERE p.status = 1 AND w.status = 1;
