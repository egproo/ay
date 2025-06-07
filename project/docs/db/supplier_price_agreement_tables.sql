-- AYM ERP - Supplier Price Agreement Database Tables
-- Created: 2024
-- Purpose: Manage price agreements with suppliers including tiered pricing and bulk discounts

-- Main price agreement table
CREATE TABLE IF NOT EXISTS `oc_supplier_price_agreement` (
  `price_agreement_id` int(11) NOT NULL AUTO_INCREMENT,
  `agreement_name` varchar(64) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `description` text,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `terms` text,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`price_agreement_id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_status` (`status`),
  KEY `idx_dates` (`start_date`, `end_date`),
  KEY `idx_agreement_name` (`agreement_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Price agreement items (products with pricing tiers)
CREATE TABLE IF NOT EXISTS `oc_supplier_price_agreement_item` (
  `price_agreement_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `price_agreement_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_min` decimal(15,4) NOT NULL DEFAULT '1.0000',
  `quantity_max` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `price` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `currency_id` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`price_agreement_item_id`),
  KEY `idx_price_agreement_id` (`price_agreement_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_quantity_range` (`quantity_min`, `quantity_max`),
  KEY `idx_status` (`status`),
  UNIQUE KEY `unique_agreement_product_qty` (`price_agreement_id`, `product_id`, `quantity_min`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Price agreement history (track changes)
CREATE TABLE IF NOT EXISTS `oc_supplier_price_agreement_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `price_agreement_id` int(11) NOT NULL,
  `action` varchar(32) NOT NULL,
  `old_values` text,
  `new_values` text,
  `user_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_price_agreement_id` (`price_agreement_id`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Price agreement notifications (for expiring agreements)
CREATE TABLE IF NOT EXISTS `oc_supplier_price_agreement_notification` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `price_agreement_id` int(11) NOT NULL,
  `notification_type` varchar(32) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `idx_price_agreement_id` (`price_agreement_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_notification_type` (`notification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Price agreement usage tracking
CREATE TABLE IF NOT EXISTS `oc_supplier_price_agreement_usage` (
  `usage_id` int(11) NOT NULL AUTO_INCREMENT,
  `price_agreement_id` int(11) NOT NULL,
  `price_agreement_item_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `purchase_order_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `unit_price` decimal(15,4) NOT NULL,
  `total_amount` decimal(15,4) NOT NULL,
  `discount_applied` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `date_used` datetime NOT NULL,
  PRIMARY KEY (`usage_id`),
  KEY `idx_price_agreement_id` (`price_agreement_id`),
  KEY `idx_price_agreement_item_id` (`price_agreement_item_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_purchase_order_id` (`purchase_order_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_date_used` (`date_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Foreign key constraints
ALTER TABLE `oc_supplier_price_agreement`
  ADD CONSTRAINT `fk_spa_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `oc_supplier` (`supplier_id`) ON DELETE CASCADE;

ALTER TABLE `oc_supplier_price_agreement_item`
  ADD CONSTRAINT `fk_spai_agreement` FOREIGN KEY (`price_agreement_id`) REFERENCES `oc_supplier_price_agreement` (`price_agreement_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_spai_product` FOREIGN KEY (`product_id`) REFERENCES `oc_product` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_spai_currency` FOREIGN KEY (`currency_id`) REFERENCES `oc_currency` (`currency_id`) ON DELETE RESTRICT;

ALTER TABLE `oc_supplier_price_agreement_history`
  ADD CONSTRAINT `fk_spah_agreement` FOREIGN KEY (`price_agreement_id`) REFERENCES `oc_supplier_price_agreement` (`price_agreement_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_spah_user` FOREIGN KEY (`user_id`) REFERENCES `oc_user` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `oc_supplier_price_agreement_notification`
  ADD CONSTRAINT `fk_span_agreement` FOREIGN KEY (`price_agreement_id`) REFERENCES `oc_supplier_price_agreement` (`price_agreement_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_span_user` FOREIGN KEY (`user_id`) REFERENCES `oc_user` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `oc_supplier_price_agreement_usage`
  ADD CONSTRAINT `fk_spau_agreement` FOREIGN KEY (`price_agreement_id`) REFERENCES `oc_supplier_price_agreement` (`price_agreement_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_spau_agreement_item` FOREIGN KEY (`price_agreement_item_id`) REFERENCES `oc_supplier_price_agreement_item` (`price_agreement_item_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_spau_product` FOREIGN KEY (`product_id`) REFERENCES `oc_product` (`product_id`) ON DELETE CASCADE;

-- Sample data for testing
INSERT INTO `oc_supplier_price_agreement` (`agreement_name`, `supplier_id`, `description`, `start_date`, `end_date`, `terms`, `status`, `date_added`, `date_modified`) VALUES
('اتفاقية أسعار 2024 - مورد رئيسي', 1, 'اتفاقية أسعار سنوية مع المورد الرئيسي تتضمن خصومات على الكميات الكبيرة', '2024-01-01', '2024-12-31', 'شروط الدفع: 30 يوم\nالتسليم: خلال 7 أيام عمل\nالحد الأدنى للطلب: 100 قطعة', 1, NOW(), NOW()),
('اتفاقية أسعار خاصة - منتجات إلكترونية', 2, 'اتفاقية خاصة للمنتجات الإلكترونية مع خصومات تدريجية', '2024-01-01', '2024-06-30', 'شروط الدفع: 15 يوم\nضمان: سنة واحدة\nالشحن مجاني للطلبات أكثر من 1000 ريال', 1, NOW(), NOW());

-- Sample price agreement items
INSERT INTO `oc_supplier_price_agreement_item` (`price_agreement_id`, `product_id`, `quantity_min`, `quantity_max`, `price`, `discount_percentage`, `currency_id`, `status`) VALUES
(1, 1, 1.0000, 99.0000, 100.0000, 0.00, 1, 1),
(1, 1, 100.0000, 499.0000, 95.0000, 5.00, 1, 1),
(1, 1, 500.0000, 0.0000, 90.0000, 10.00, 1, 1),
(2, 2, 1.0000, 49.0000, 250.0000, 0.00, 1, 1),
(2, 2, 50.0000, 199.0000, 240.0000, 4.00, 1, 1),
(2, 2, 200.0000, 0.0000, 230.0000, 8.00, 1, 1);

-- Indexes for performance optimization
CREATE INDEX idx_spa_supplier_status ON oc_supplier_price_agreement(supplier_id, status);
CREATE INDEX idx_spa_date_range ON oc_supplier_price_agreement(start_date, end_date, status);
CREATE INDEX idx_spai_product_qty ON oc_supplier_price_agreement_item(product_id, quantity_min, quantity_max);
CREATE INDEX idx_spau_date_product ON oc_supplier_price_agreement_usage(date_used, product_id);

-- Views for reporting
CREATE OR REPLACE VIEW v_active_price_agreements AS
SELECT 
    spa.price_agreement_id,
    spa.agreement_name,
    s.name as supplier_name,
    spa.start_date,
    spa.end_date,
    COUNT(spai.price_agreement_item_id) as total_items,
    DATEDIFF(spa.end_date, CURDATE()) as days_remaining
FROM oc_supplier_price_agreement spa
LEFT JOIN oc_supplier s ON spa.supplier_id = s.supplier_id
LEFT JOIN oc_supplier_price_agreement_item spai ON spa.price_agreement_id = spai.price_agreement_id
WHERE spa.status = 1 
AND spa.start_date <= CURDATE() 
AND spa.end_date >= CURDATE()
GROUP BY spa.price_agreement_id;

CREATE OR REPLACE VIEW v_expiring_price_agreements AS
SELECT 
    spa.price_agreement_id,
    spa.agreement_name,
    s.name as supplier_name,
    spa.end_date,
    DATEDIFF(spa.end_date, CURDATE()) as days_remaining
FROM oc_supplier_price_agreement spa
LEFT JOIN oc_supplier s ON spa.supplier_id = s.supplier_id
WHERE spa.status = 1 
AND spa.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
ORDER BY spa.end_date ASC;

-- Triggers for audit trail
DELIMITER $$

CREATE TRIGGER tr_spa_after_update
AFTER UPDATE ON oc_supplier_price_agreement
FOR EACH ROW
BEGIN
    INSERT INTO oc_supplier_price_agreement_history 
    (price_agreement_id, action, old_values, new_values, user_id, date_added)
    VALUES 
    (NEW.price_agreement_id, 'UPDATE', 
     CONCAT('agreement_name:', OLD.agreement_name, '|status:', OLD.status), 
     CONCAT('agreement_name:', NEW.agreement_name, '|status:', NEW.status),
     @user_id, NOW());
END$$

CREATE TRIGGER tr_spa_after_delete
AFTER DELETE ON oc_supplier_price_agreement
FOR EACH ROW
BEGIN
    INSERT INTO oc_supplier_price_agreement_history 
    (price_agreement_id, action, old_values, new_values, user_id, date_added)
    VALUES 
    (OLD.price_agreement_id, 'DELETE', 
     CONCAT('agreement_name:', OLD.agreement_name, '|status:', OLD.status), 
     '',
     @user_id, NOW());
END$$

DELIMITER ;
