-- ========================================================================
-- AYM ERP - Screen Review Database Corrections
-- File: screenreview.sql
-- Purpose: Fix database schema issues discovered during code review
-- Date: 2024-12-19 (Updated)
-- ========================================================================

-- ========================================================================
-- 0. DASHBOARD SCREEN FIXES (PRIORITY 1)
-- ========================================================================

-- 0.1 إصلاح شاشة common/dashboard - التكامل الكامل
-- ✅ Controller: dashboard/controller/common/dashboard.php - تم إصلاح جميع استدعاءات الـ Model
-- ✅ Model: dashboard/model/common/dashboard.php - تم إنشاء Model محسن مع التكامل الكامل
-- ✅ View: dashboard/view/template/common/dashboard.twig - تم تحديث المتغيرات لتتطابق مع Model
-- ✅ Language: dashboard/language/ar/common/dashboard.php - تم التأكد من وجود جميع النصوص
-- ✅ Database: تم إضافة الجداول المفقودة وإصلاح استخدام DB_PREFIX
-- الحالة: ✅ مكتمل 100% (Controller ↔ Model ↔ View ↔ Language ↔ Database)

-- إضافة جداول مفقودة لدعم الـ Dashboard المحسن
CREATE TABLE IF NOT EXISTS `oc_user_activity` (
  `activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `last_activity` datetime NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  PRIMARY KEY (`activity_id`),
  KEY `idx_user_activity` (`user_id`, `last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `oc_customer_activity` (
  `activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `last_activity` datetime NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  PRIMARY KEY (`activity_id`),
  KEY `idx_customer_activity` (`customer_id`, `last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `oc_user_dashboard_widget` (
  `widget_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `widget_type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `position` varchar(20) NOT NULL DEFAULT 'left',
  `size` varchar(20) NOT NULL DEFAULT 'medium',
  `sort_order` int(3) NOT NULL DEFAULT 0,
  `settings` text,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`widget_id`),
  KEY `idx_user_widget` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة بيانات افتراضية للـ widgets
INSERT IGNORE INTO `oc_user_dashboard_widget` (`user_id`, `widget_type`, `title`, `position`, `size`, `sort_order`, `settings`, `status`, `date_added`, `date_modified`) VALUES
(1, 'quick_stats', 'الإحصائيات السريعة', 'left', 'large', 1, '{}', 1, NOW(), NOW()),
(1, 'sales_chart', 'مخطط المبيعات', 'center', 'large', 2, '{}', 1, NOW(), NOW()),
(1, 'inventory_alerts', 'تنبيهات المخزون', 'right', 'medium', 3, '{}', 1, NOW(), NOW()),
(1, 'pending_approvals', 'الموافقات المعلقة', 'right', 'medium', 4, '{}', 1, NOW(), NOW()),
(1, 'recent_activities', 'الأنشطة الحديثة', 'right', 'medium', 5, '{}', 1, NOW(), NOW());

-- ========================================================================
-- 1. PURCHASE/REQUISITION SCREEN FIXES
-- ========================================================================

-- Fix 1: Add missing fields to cod_purchase_requisition table
ALTER TABLE `cod_purchase_requisition`
ADD COLUMN `approved_by` int DEFAULT NULL COMMENT 'User who approved the requisition',
ADD COLUMN `approved_at` datetime DEFAULT NULL COMMENT 'Approval timestamp',
ADD COLUMN `rejected_by` int DEFAULT NULL COMMENT 'User who rejected the requisition',
ADD COLUMN `rejected_at` datetime DEFAULT NULL COMMENT 'Rejection timestamp';

-- Fix 2: Add foreign key constraints for new fields
ALTER TABLE `cod_purchase_requisition`
ADD CONSTRAINT `fk_purchase_req_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `cod_user` (`user_id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_purchase_req_rejected_by` FOREIGN KEY (`rejected_by`) REFERENCES `cod_user` (`user_id`) ON DELETE SET NULL;

-- Fix 3: Add indexes for performance
ALTER TABLE `cod_purchase_requisition`
ADD INDEX `idx_approved_by` (`approved_by`),
ADD INDEX `idx_rejected_by` (`rejected_by`),
ADD INDEX `idx_approved_at` (`approved_at`),
ADD INDEX `idx_rejected_at` (`rejected_at`);

-- Fix 4: Ensure proper table structure for cod_purchase_requisition_history
-- (Table exists but verify structure)
CREATE TABLE IF NOT EXISTS `cod_purchase_requisition_history` (
  `history_id` int NOT NULL AUTO_INCREMENT,
  `requisition_id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  KEY `idx_requisition_id` (`requisition_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_req_history_requisition` FOREIGN KEY (`requisition_id`) REFERENCES `cod_purchase_requisition` (`requisition_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_req_history_user` FOREIGN KEY (`user_id`) REFERENCES `cod_user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fix 5: Ensure proper table structure for cod_purchase_requisition_item
-- (Table exists but verify structure and add missing fields if needed)
ALTER TABLE `cod_purchase_requisition_item`
ADD COLUMN IF NOT EXISTS `item_id` int NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
ADD INDEX IF NOT EXISTS `idx_requisition_product` (`requisition_id`, `product_id`),
ADD INDEX IF NOT EXISTS `idx_product_unit` (`product_id`, `unit_id`);

-- Fix 6: Add missing tables that might be referenced

-- Table for product inventory (if not exists)
CREATE TABLE IF NOT EXISTS `cod_product_inventory` (
  `inventory_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `branch_id` int NOT NULL,
  `unit_id` int NOT NULL,
  `quantity_available` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `quantity_reserved` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `average_cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `last_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`inventory_id`),
  UNIQUE KEY `uk_product_branch_unit` (`product_id`, `branch_id`, `unit_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_unit_id` (`unit_id`),
  CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `cod_product` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inventory_branch` FOREIGN KEY (`branch_id`) REFERENCES `cod_branch` (`branch_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inventory_unit` FOREIGN KEY (`unit_id`) REFERENCES `cod_unit` (`unit_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fix 7: Add missing product_unit table (if not exists)
CREATE TABLE IF NOT EXISTS `cod_product_unit` (
  `product_unit_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `unit_id` int NOT NULL,
  `conversion_factor` decimal(15,4) NOT NULL DEFAULT '1.0000',
  `is_base_unit` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`product_unit_id`),
  UNIQUE KEY `uk_product_unit` (`product_id`, `unit_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_unit_id` (`unit_id`),
  CONSTRAINT `fk_product_unit_product` FOREIGN KEY (`product_id`) REFERENCES `cod_product` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_unit_unit` FOREIGN KEY (`unit_id`) REFERENCES `cod_unit` (`unit_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fix 8: Add missing unit table (if not exists)
CREATE TABLE IF NOT EXISTS `cod_unit` (
  `unit_id` int NOT NULL AUTO_INCREMENT,
  `desc_en` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `desc_ar` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `symbol` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`unit_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fix 9: Add missing branch table (if not exists)
CREATE TABLE IF NOT EXISTS `cod_branch` (
  `branch_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `manager_id` int DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`branch_id`),
  UNIQUE KEY `uk_branch_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_manager_id` (`manager_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fix 10: Add missing user_group table (if not exists)
CREATE TABLE IF NOT EXISTS `cod_user_group` (
  `user_group_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `permission` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`user_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================================
-- DATA VALIDATION AND CLEANUP
-- ========================================================================

-- Ensure all existing requisitions have proper status values
UPDATE `cod_purchase_requisition`
SET `status` = 'draft'
WHERE `status` NOT IN ('draft','pending','approved','rejected','cancelled','processing','completed');

-- Ensure all existing requisitions have proper priority values
UPDATE `cod_purchase_requisition`
SET `priority` = 'medium'
WHERE `priority` NOT IN ('low','medium','high','urgent');

-- ========================================================================
-- PERFORMANCE OPTIMIZATIONS
-- ========================================================================

-- Add composite indexes for common queries
ALTER TABLE `cod_purchase_requisition`
ADD INDEX `idx_status_created` (`status`, `created_at`),
ADD INDEX `idx_branch_status` (`branch_id`, `status`),
ADD INDEX `idx_user_group_status` (`user_group_id`, `status`),
ADD INDEX `idx_created_by_status` (`created_by`, `status`);

-- Add indexes for requisition items
ALTER TABLE `cod_purchase_requisition_item`
ADD INDEX `idx_product_quantity` (`product_id`, `quantity`),
ADD INDEX `idx_unit_quantity` (`unit_id`, `quantity`);

-- ========================================================================
-- TRIGGERS FOR DATA INTEGRITY
-- ========================================================================

-- Trigger to update requisition updated_at when items change
DELIMITER $$
CREATE TRIGGER `tr_requisition_item_update`
AFTER INSERT ON `cod_purchase_requisition_item`
FOR EACH ROW
BEGIN
    UPDATE `cod_purchase_requisition`
    SET `updated_at` = NOW()
    WHERE `requisition_id` = NEW.`requisition_id`;
END$$

CREATE TRIGGER `tr_requisition_item_update_on_change`
AFTER UPDATE ON `cod_purchase_requisition_item`
FOR EACH ROW
BEGIN
    UPDATE `cod_purchase_requisition`
    SET `updated_at` = NOW()
    WHERE `requisition_id` = NEW.`requisition_id`;
END$$

CREATE TRIGGER `tr_requisition_item_update_on_delete`
AFTER DELETE ON `cod_purchase_requisition_item`
FOR EACH ROW
BEGIN
    UPDATE `cod_purchase_requisition`
    SET `updated_at` = NOW()
    WHERE `requisition_id` = OLD.`requisition_id`;
END$$
DELIMITER ;

-- ========================================================================
-- VIEWS FOR REPORTING
-- ========================================================================

-- View for requisition summary
CREATE OR REPLACE VIEW `v_purchase_requisition_summary` AS
SELECT
    r.`requisition_id`,
    r.`req_number`,
    r.`status`,
    r.`priority`,
    r.`required_date`,
    r.`created_at`,
    b.`name` AS `branch_name`,
    ug.`name` AS `user_group_name`,
    CONCAT(u1.`firstname`, ' ', u1.`lastname`) AS `created_by_name`,
    CONCAT(u2.`firstname`, ' ', u2.`lastname`) AS `approved_by_name`,
    CONCAT(u3.`firstname`, ' ', u3.`lastname`) AS `rejected_by_name`,
    COUNT(ri.`requisition_item_id`) AS `items_count`,
    SUM(ri.`quantity`) AS `total_quantity`
FROM `cod_purchase_requisition` r
LEFT JOIN `cod_branch` b ON r.`branch_id` = b.`branch_id`
LEFT JOIN `cod_user_group` ug ON r.`user_group_id` = ug.`user_group_id`
LEFT JOIN `cod_user` u1 ON r.`created_by` = u1.`user_id`
LEFT JOIN `cod_user` u2 ON r.`approved_by` = u2.`user_id`
LEFT JOIN `cod_user` u3 ON r.`rejected_by` = u3.`user_id`
LEFT JOIN `cod_purchase_requisition_item` ri ON r.`requisition_id` = ri.`requisition_id`
GROUP BY r.`requisition_id`;

-- ========================================================================
-- SCREEN REVIEW LOG
-- ========================================================================

-- Log this screen review
INSERT INTO `cod_activity_log` (`user_id`, `action_type`, `module`, `description`, `created_at`)
VALUES (1, 'SCREEN_REVIEW', 'purchase/requisition', 'Database schema corrected for purchase requisition screen', NOW());

-- ========================================================================
-- END OF PURCHASE/REQUISITION FIXES
-- ========================================================================

-- ========================================================================
-- 2. COMMON/DASHBOARD SCREEN FIXES
-- ========================================================================

-- Fix 1: Add missing transaction table for cash flow data
CREATE TABLE IF NOT EXISTS `cod_transaction` (
  `transaction_id` int NOT NULL AUTO_INCREMENT,
  `journal_id` int NOT NULL DEFAULT '0',
  `branch_id` int NOT NULL DEFAULT '0',
  `type` enum('income','expense') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(15,4) NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_id` int DEFAULT NULL,
  `created_by` int NOT NULL,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  KEY `idx_type` (`type`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_date_added` (`date_added`),
  KEY `idx_account_id` (`account_id`),
  CONSTRAINT `fk_transaction_branch` FOREIGN KEY (`branch_id`) REFERENCES `cod_branch` (`branch_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transaction_account` FOREIGN KEY (`account_id`) REFERENCES `cod_accounts` (`account_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_transaction_user` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fix 2: Add missing accounts_receivable table
CREATE TABLE IF NOT EXISTS `cod_accounts_receivable` (
  `receivable_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `invoice_id` int DEFAULT NULL,
  `amount` decimal(15,4) NOT NULL,
  `paid_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `balance` decimal(15,4) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','partial','paid','overdue','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `branch_id` int NOT NULL DEFAULT '0',
  `created_by` int NOT NULL,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`receivable_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_branch_id` (`branch_id`),
  CONSTRAINT `fk_receivable_customer` FOREIGN KEY (`customer_id`) REFERENCES `cod_customer` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_receivable_order` FOREIGN KEY (`order_id`) REFERENCES `cod_order` (`order_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_receivable_branch` FOREIGN KEY (`branch_id`) REFERENCES `cod_branch` (`branch_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_receivable_user` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fix 3: Add missing accounts_payable table
CREATE TABLE IF NOT EXISTS `cod_accounts_payable` (
  `payable_id` int NOT NULL AUTO_INCREMENT,
  `supplier_id` int NOT NULL,
  `purchase_order_id` int DEFAULT NULL,
  `invoice_id` int DEFAULT NULL,
  `amount` decimal(15,4) NOT NULL,
  `paid_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `balance` decimal(15,4) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','partial','paid','overdue','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `branch_id` int NOT NULL DEFAULT '0',
  `created_by` int NOT NULL,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`payable_id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_purchase_order_id` (`purchase_order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_branch_id` (`branch_id`),
  CONSTRAINT `fk_payable_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `cod_supplier` (`supplier_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payable_purchase_order` FOREIGN KEY (`purchase_order_id`) REFERENCES `cod_purchase_order` (`purchase_order_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_payable_branch` FOREIGN KEY (`branch_id`) REFERENCES `cod_branch` (`branch_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payable_user` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fix 4: Add missing CRM tables
CREATE TABLE IF NOT EXISTS `cod_crm_lead` (
  `lead_id` int NOT NULL AUTO_INCREMENT,
  `lead_source` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contact_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('new','contacted','qualified','proposal','negotiation','closed_won','closed_lost') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'new',
  `priority` enum('low','medium','high','urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'medium',
  `estimated_value` decimal(15,4) DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `assigned_to` int DEFAULT NULL,
  `created_by` int NOT NULL,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lead_id`),
  KEY `idx_status` (`status`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_date_added` (`date_added`),
  CONSTRAINT `fk_lead_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `cod_user` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_lead_created_by` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `cod_crm_opportunity` (
  `opportunity_id` int NOT NULL AUTO_INCREMENT,
  `lead_id` int DEFAULT NULL,
  `customer_id` int DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `stage` enum('prospecting','qualification','proposal','negotiation','closed_won','closed_lost') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'prospecting',
  `probability` decimal(5,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `expected_close_date` date DEFAULT NULL,
  `actual_close_date` date DEFAULT NULL,
  `assigned_to` int DEFAULT NULL,
  `created_by` int NOT NULL,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`opportunity_id`),
  KEY `idx_lead_id` (`lead_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_stage` (`stage`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_expected_close_date` (`expected_close_date`),
  CONSTRAINT `fk_opportunity_lead` FOREIGN KEY (`lead_id`) REFERENCES `cod_crm_lead` (`lead_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_opportunity_customer` FOREIGN KEY (`customer_id`) REFERENCES `cod_customer` (`customer_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_opportunity_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `cod_user` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_opportunity_created_by` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fix 5: Add missing product minimum field to cod_product table
ALTER TABLE `cod_product`
ADD COLUMN IF NOT EXISTS `minimum` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Minimum stock level';

-- Fix 6: Ensure workflow_approval table has correct structure
-- (Table exists but verify status field values)
UPDATE `cod_workflow_approval`
SET `action` = 'approved'
WHERE `action` NOT IN ('approved','rejected','delegated','commented');

-- Fix 7: Add indexes for dashboard performance
ALTER TABLE `cod_order`
ADD INDEX IF NOT EXISTS `idx_date_status` (`date_added`, `order_status_id`),
ADD INDEX IF NOT EXISTS `idx_branch_date` (`branch_id`, `date_added`);

ALTER TABLE `cod_product_inventory`
ADD INDEX IF NOT EXISTS `idx_quantity_minimum` (`quantity`, `product_id`),
ADD INDEX IF NOT EXISTS `idx_branch_product` (`branch_id`, `product_id`);

ALTER TABLE `cod_customer`
ADD INDEX IF NOT EXISTS `idx_date_added` (`date_added`);

-- ========================================================================
-- END OF COMMON/DASHBOARD FIXES
-- ========================================================================

-- ========================================================================
-- 3. DASHBOARD/KPI SCREEN FIXES
-- ========================================================================

-- Fix 1: Create dashboard_kpi table for storing KPI values
CREATE TABLE IF NOT EXISTS `cod_dashboard_kpi` (
  `kpi_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'general',
  `value` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `previous_value` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `trend` decimal(8,2) NOT NULL DEFAULT '0.00',
  `date_range` enum('current','today','week','month','year') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'today',
  `last_calculated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive','calculating','error') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`kpi_id`),
  UNIQUE KEY `uk_kpi_name_range` (`name`, `date_range`),
  KEY `idx_category` (`category`),
  KEY `idx_date_range` (`date_range`),
  KEY `idx_last_calculated` (`last_calculated`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fix 2: Create customer_online table for tracking online customers
CREATE TABLE IF NOT EXISTS `cod_customer_online` (
  `customer_online_id` int NOT NULL AUTO_INCREMENT,
  `ip` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `referer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`customer_online_id`),
  KEY `idx_ip` (`ip`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_date_added` (`date_added`),
  CONSTRAINT `fk_customer_online_customer` FOREIGN KEY (`customer_id`) REFERENCES `cod_customer` (`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fix 3: Create customer_transaction table for payment tracking
CREATE TABLE IF NOT EXISTS `cod_customer_transaction` (
  `customer_transaction_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(15,4) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('paid','unpaid','partial','overdue','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unpaid',
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`customer_transaction_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_amount` (`amount`),
  CONSTRAINT `fk_customer_transaction_customer` FOREIGN KEY (`customer_id`) REFERENCES `cod_customer` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_customer_transaction_order` FOREIGN KEY (`order_id`) REFERENCES `cod_order` (`order_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_customer_transaction_user` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fix 4: Ensure cod_product has minimum field for low stock calculation
ALTER TABLE `cod_product`
ADD COLUMN IF NOT EXISTS `minimum` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Minimum stock level for low stock alerts';

-- Fix 5: Add indexes for KPI performance optimization
ALTER TABLE `cod_order`
ADD INDEX IF NOT EXISTS `idx_date_status_total` (`date_added`, `order_status_id`, `total`),
ADD INDEX IF NOT EXISTS `idx_status_date_modified` (`order_status_id`, `date_modified`);

ALTER TABLE `cod_product`
ADD INDEX IF NOT EXISTS `idx_minimum_quantity` (`minimum`, `quantity`);

ALTER TABLE `cod_customer`
ADD INDEX IF NOT EXISTS `idx_date_added_status` (`date_added`, `status`);

-- Fix 6: Create view for low stock items using proper inventory table
CREATE OR REPLACE VIEW `v_low_stock_items` AS
SELECT
    p.product_id,
    pd.name as product_name,
    p.model,
    p.sku,
    pi.quantity as current_quantity,
    p.minimum as minimum_quantity,
    (p.minimum - pi.quantity) as shortage_quantity,
    b.name as branch_name,
    pi.branch_id
FROM `cod_product` p
LEFT JOIN `cod_product_description` pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
LEFT JOIN `cod_product_inventory` pi ON (p.product_id = pi.product_id)
LEFT JOIN `cod_branch` b ON (pi.branch_id = b.branch_id)
WHERE pi.quantity <= p.minimum AND pi.quantity >= 0 AND p.minimum > 0;

-- Fix 7: Create view for KPI dashboard summary
CREATE OR REPLACE VIEW `v_kpi_dashboard_summary` AS
SELECT
    k.name,
    k.category,
    k.value,
    k.previous_value,
    k.trend,
    k.date_range,
    k.last_calculated,
    k.status,
    CASE
        WHEN k.trend > 0 THEN 'up'
        WHEN k.trend < 0 THEN 'down'
        ELSE 'stable'
    END as trend_direction,
    CASE
        WHEN k.name IN ('sales_today', 'total_revenue_month', 'avg_order_value', 'inventory_value') THEN 'currency'
        WHEN k.name IN ('overdue_payments', 'pending_orders', 'orders_today', 'customers_online', 'low_stock_items') THEN 'count'
        ELSE 'number'
    END as value_type
FROM `cod_dashboard_kpi` k
WHERE k.status = 'active'
ORDER BY k.category, k.name;

-- Fix 8: Insert default KPI configurations
INSERT IGNORE INTO `cod_dashboard_kpi` (`name`, `category`, `value`, `previous_value`, `trend`, `date_range`, `status`) VALUES
('sales_today', 'sales', 0.0000, 0.0000, 0.00, 'today', 'active'),
('orders_today', 'sales', 0.0000, 0.0000, 0.00, 'today', 'active'),
('customers_online', 'customers', 0.0000, 0.0000, 0.00, 'current', 'active'),
('low_stock_items', 'inventory', 0.0000, 0.0000, 0.00, 'current', 'active'),
('total_revenue_month', 'sales', 0.0000, 0.0000, 0.00, 'month', 'active'),
('pending_orders', 'operations', 0.0000, 0.0000, 0.00, 'current', 'active'),
('avg_order_value', 'sales', 0.0000, 0.0000, 0.00, 'month', 'active'),
('inventory_value', 'inventory', 0.0000, 0.0000, 0.00, 'current', 'active'),
('overdue_payments', 'finance', 0.0000, 0.0000, 0.00, 'current', 'active');

-- Fix 9: Create stored procedure for KPI calculation optimization
DELIMITER $$
CREATE PROCEDURE `sp_calculate_kpi_sales_today`()
BEGIN
    DECLARE today_sales DECIMAL(15,4) DEFAULT 0;
    DECLARE yesterday_sales DECIMAL(15,4) DEFAULT 0;
    DECLARE trend_value DECIMAL(8,2) DEFAULT 0;

    -- Calculate today's sales
    SELECT COALESCE(SUM(total), 0) INTO today_sales
    FROM `cod_order`
    WHERE DATE(date_added) = CURDATE() AND order_status_id > 0;

    -- Calculate yesterday's sales
    SELECT COALESCE(SUM(total), 0) INTO yesterday_sales
    FROM `cod_order`
    WHERE DATE(date_added) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND order_status_id > 0;

    -- Calculate trend
    IF yesterday_sales > 0 THEN
        SET trend_value = ((today_sales - yesterday_sales) / yesterday_sales) * 100;
    END IF;

    -- Update KPI table
    INSERT INTO `cod_dashboard_kpi` (`name`, `category`, `value`, `previous_value`, `trend`, `date_range`, `last_calculated`)
    VALUES ('sales_today', 'sales', today_sales, yesterday_sales, trend_value, 'today', NOW())
    ON DUPLICATE KEY UPDATE
        `value` = today_sales,
        `previous_value` = yesterday_sales,
        `trend` = trend_value,
        `last_calculated` = NOW();
END$$
DELIMITER ;

-- Fix 10: Create triggers for automatic KPI updates
DELIMITER $$
CREATE TRIGGER `tr_order_kpi_update`
AFTER INSERT ON `cod_order`
FOR EACH ROW
BEGIN
    -- Update sales KPIs when new order is added
    IF NEW.order_status_id > 0 THEN
        CALL sp_calculate_kpi_sales_today();
    END IF;
END$$

CREATE TRIGGER `tr_order_status_kpi_update`
AFTER UPDATE ON `cod_order`
FOR EACH ROW
BEGIN
    -- Update KPIs when order status changes
    IF OLD.order_status_id != NEW.order_status_id THEN
        CALL sp_calculate_kpi_sales_today();
    END IF;
END$$
DELIMITER ;

-- ========================================================================
-- END OF DASHBOARD/KPI FIXES
-- ========================================================================

-- ========================================================================
-- 4. DASHBOARD/GOALS SCREEN FIXES
-- ========================================================================

-- Fix 1: Create dashboard_goals table for storing goals
CREATE TABLE IF NOT EXISTS `cod_dashboard_goals` (
  `goal_id` int NOT NULL AUTO_INCREMENT,
  `goal_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `goal_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `goal_type` enum('sales','revenue','profit','customers','orders','inventory','productivity','quality','cost_reduction','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'sales',
  `target_value` decimal(15,4) NOT NULL,
  `current_value` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `assigned_to` int NOT NULL,
  `department_id` int DEFAULT NULL,
  `priority` enum('low','medium','high','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'medium',
  `status` enum('active','paused','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_by` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`goal_id`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_department_id` (`department_id`),
  KEY `idx_status` (`status`),
  KEY `idx_goal_type` (`goal_type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_end_date` (`end_date`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_goal_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `cod_user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_goal_department` FOREIGN KEY (`department_id`) REFERENCES `cod_user_group` (`user_group_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_goal_created_by` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fix 2: Create dashboard_goal_progress table for tracking progress
CREATE TABLE IF NOT EXISTS `cod_dashboard_goal_progress` (
  `progress_id` int NOT NULL AUTO_INCREMENT,
  `goal_id` int NOT NULL,
  `progress_value` decimal(15,4) NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `recorded_by` int NOT NULL,
  `recorded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`progress_id`),
  KEY `idx_goal_id` (`goal_id`),
  KEY `idx_recorded_by` (`recorded_by`),
  KEY `idx_recorded_at` (`recorded_at`),
  CONSTRAINT `fk_progress_goal` FOREIGN KEY (`goal_id`) REFERENCES `cod_dashboard_goals` (`goal_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progress_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `cod_user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fix 3: Create view for goals summary
CREATE OR REPLACE VIEW `v_goals_summary` AS
SELECT
    g.goal_id,
    g.goal_title,
    g.goal_type,
    g.target_value,
    g.current_value,
    g.start_date,
    g.end_date,
    g.priority,
    g.status,
    CASE
        WHEN g.target_value > 0 THEN ROUND((g.current_value / g.target_value) * 100, 2)
        ELSE 0
    END as progress_percentage,
    DATEDIFF(g.end_date, CURDATE()) as days_remaining,
    CONCAT(u1.firstname, ' ', u1.lastname) as assigned_to_name,
    CONCAT(u2.firstname, ' ', u2.lastname) as created_by_name,
    ug.name as department_name,
    CASE
        WHEN g.status = 'completed' THEN 'completed'
        WHEN g.status = 'paused' THEN 'paused'
        WHEN CURDATE() > g.end_date THEN 'overdue'
        WHEN CURDATE() < g.start_date THEN 'not_started'
        WHEN g.target_value > 0 AND (g.current_value / g.target_value) >= 1 THEN 'completed'
        WHEN g.target_value > 0 AND (g.current_value / g.target_value) >= 0.8 THEN 'on_track'
        WHEN g.target_value > 0 AND (g.current_value / g.target_value) >= 0.5 THEN 'behind'
        ELSE 'at_risk'
    END as calculated_status
FROM `cod_dashboard_goals` g
LEFT JOIN `cod_user` u1 ON g.assigned_to = u1.user_id
LEFT JOIN `cod_user` u2 ON g.created_by = u2.user_id
LEFT JOIN `cod_user_group` ug ON g.department_id = ug.user_group_id;

-- Fix 4: Insert sample goals data
INSERT IGNORE INTO `cod_dashboard_goals` (`goal_title`, `goal_description`, `goal_type`, `target_value`, `current_value`, `start_date`, `end_date`, `assigned_to`, `department_id`, `priority`, `status`, `created_by`) VALUES
('زيادة المبيعات الشهرية', 'زيادة المبيعات بنسبة 20% خلال الشهر الحالي', 'sales', 1000000.0000, 650000.0000, '2024-12-01', '2024-12-31', 1, 1, 'high', 'active', 1),
('تحسين رضا العملاء', 'الوصول لمعدل رضا 95% من العملاء', 'customers', 95.0000, 87.0000, '2024-12-01', '2025-02-28', 1, 2, 'medium', 'active', 1),
('تقليل التكاليف التشغيلية', 'تقليل التكاليف بنسبة 15%', 'cost_reduction', 15.0000, 8.5000, '2024-12-01', '2025-03-31', 1, 3, 'high', 'active', 1);

-- Fix 5: Create stored procedures for goal calculations
DELIMITER $$
CREATE PROCEDURE `sp_update_goal_status`()
BEGIN
    -- Update completed goals based on target achievement
    UPDATE `cod_dashboard_goals`
    SET `status` = 'completed', `completed_at` = NOW()
    WHERE `current_value` >= `target_value`
    AND `status` != 'completed';

    -- Update overdue goals
    UPDATE `cod_dashboard_goals`
    SET `status` = 'cancelled'
    WHERE `end_date` < CURDATE()
    AND `status` NOT IN ('completed', 'cancelled')
    AND `current_value` < `target_value` * 0.5;
END$$
DELIMITER ;

-- Fix 6: Create triggers for automatic goal updates
DELIMITER $$
CREATE TRIGGER `tr_goal_progress_update`
AFTER INSERT ON `cod_dashboard_goal_progress`
FOR EACH ROW
BEGIN
    -- Update current value in goals table
    UPDATE `cod_dashboard_goals`
    SET `current_value` = NEW.`progress_value`, `updated_at` = NOW()
    WHERE `goal_id` = NEW.`goal_id`;

    -- Check if goal is completed
    UPDATE `cod_dashboard_goals`
    SET `status` = 'completed', `completed_at` = NOW()
    WHERE `goal_id` = NEW.`goal_id`
    AND `current_value` >= `target_value`
    AND `status` != 'completed';
END$$
DELIMITER ;

-- Fix 7: Add indexes for performance optimization
ALTER TABLE `cod_dashboard_goals`
ADD INDEX `idx_status_end_date` (`status`, `end_date`),
ADD INDEX `idx_type_status` (`goal_type`, `status`),
ADD INDEX `idx_assigned_status` (`assigned_to`, `status`),
ADD INDEX `idx_department_status` (`department_id`, `status`);

ALTER TABLE `cod_dashboard_goal_progress`
ADD INDEX `idx_goal_recorded_at` (`goal_id`, `recorded_at`);

-- Fix 8: Create function for goal progress calculation
DELIMITER $$
CREATE FUNCTION `fn_calculate_goal_progress`(goal_id INT)
RETURNS DECIMAL(5,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE target_val DECIMAL(15,4) DEFAULT 0;
    DECLARE current_val DECIMAL(15,4) DEFAULT 0;
    DECLARE progress_pct DECIMAL(5,2) DEFAULT 0;

    SELECT `target_value`, `current_value`
    INTO target_val, current_val
    FROM `cod_dashboard_goals`
    WHERE `goal_id` = goal_id;

    IF target_val > 0 THEN
        SET progress_pct = ROUND((current_val / target_val) * 100, 2);
    END IF;

    RETURN progress_pct;
END$$
DELIMITER ;

-- ========================================================================
-- END OF DASHBOARD/GOALS FIXES
-- ========================================================================

-- ========================================================================
-- 5. DASHBOARD/ALERTS SCREEN FIXES
-- ========================================================================

-- Fix 1: Create dashboard_alerts table for storing alerts
CREATE TABLE IF NOT EXISTS `cod_dashboard_alerts` (
  `alert_id` int NOT NULL AUTO_INCREMENT,
  `alert_type` enum('low_stock','overdue_payment','pending_order','goal_deadline','system_performance','custom','info','warning','error','success') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'custom',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `priority` enum('low','medium','high','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'medium',
  `target_type` enum('all','specific') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'all',
  `expires_at` datetime DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`alert_id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_target_type` (`target_type`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_alert_created_by` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fix 2: Create dashboard_alert_recipients table for tracking read/dismissed status
CREATE TABLE IF NOT EXISTS `cod_dashboard_alert_recipients` (
  `recipient_id` int NOT NULL AUTO_INCREMENT,
  `alert_id` int NOT NULL,
  `user_id` int NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `is_dismissed` tinyint(1) NOT NULL DEFAULT '0',
  `dismissed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`recipient_id`),
  UNIQUE KEY `uk_alert_user` (`alert_id`, `user_id`),
  KEY `idx_alert_id` (`alert_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_is_dismissed` (`is_dismissed`),
  CONSTRAINT `fk_recipient_alert` FOREIGN KEY (`alert_id`) REFERENCES `cod_dashboard_alerts` (`alert_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_recipient_user` FOREIGN KEY (`user_id`) REFERENCES `cod_user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fix 3: Create view for alerts with recipient status
CREATE OR REPLACE VIEW `v_alerts_with_status` AS
SELECT
    a.alert_id,
    a.alert_type,
    a.title,
    a.message,
    a.priority,
    a.target_type,
    a.expires_at,
    a.created_by,
    a.created_at,
    ar.user_id,
    ar.is_read,
    ar.read_at,
    ar.is_dismissed,
    ar.dismissed_at,
    CONCAT(u1.firstname, ' ', u1.lastname) as created_by_name,
    CASE
        WHEN a.priority = 'critical' THEN 'danger'
        WHEN a.priority = 'high' THEN 'warning'
        WHEN a.priority = 'medium' THEN 'info'
        ELSE 'default'
    END as color_class,
    CASE
        WHEN a.alert_type = 'low_stock' THEN 'fa-exclamation-triangle'
        WHEN a.alert_type = 'overdue_payment' THEN 'fa-money'
        WHEN a.alert_type = 'pending_order' THEN 'fa-clock-o'
        WHEN a.alert_type = 'goal_deadline' THEN 'fa-target'
        WHEN a.alert_type = 'system_performance' THEN 'fa-server'
        WHEN a.alert_type = 'error' THEN 'fa-times-circle'
        WHEN a.alert_type = 'warning' THEN 'fa-exclamation-triangle'
        WHEN a.alert_type = 'success' THEN 'fa-check-circle'
        WHEN a.alert_type = 'info' THEN 'fa-info-circle'
        ELSE 'fa-bell'
    END as icon_class
FROM `cod_dashboard_alerts` a
LEFT JOIN `cod_dashboard_alert_recipients` ar ON a.alert_id = ar.alert_id
LEFT JOIN `cod_user` u1 ON a.created_by = u1.user_id
WHERE (a.expires_at IS NULL OR a.expires_at > NOW());

-- Fix 4: Insert sample alerts data
INSERT IGNORE INTO `cod_dashboard_alerts` (`alert_type`, `title`, `message`, `priority`, `target_type`, `created_by`) VALUES
('system_performance', 'مرحباً بك في نظام التنبيهات', 'تم تفعيل نظام التنبيهات بنجاح. ستتلقى إشعارات حول الأحداث المهمة في النظام.', 'medium', 'all', 1),
('info', 'تحديث النظام', 'تم تحديث النظام إلى أحدث إصدار مع تحسينات في الأداء والأمان.', 'low', 'all', 1),
('warning', 'صيانة مجدولة', 'ستتم صيانة النظام يوم الجمعة من الساعة 2:00 إلى 4:00 صباحاً.', 'medium', 'all', 1);

-- Fix 5: Create stored procedures for alert management
DELIMITER $$
CREATE PROCEDURE `sp_generate_low_stock_alerts`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE product_count INT DEFAULT 0;

    -- Count low stock products
    SELECT COUNT(*) INTO product_count
    FROM `cod_product` p
    LEFT JOIN `cod_product_inventory` pi ON p.product_id = pi.product_id
    WHERE pi.quantity <= p.minimum AND p.minimum > 0 AND pi.quantity >= 0;

    -- Create alert if products found and no similar alert exists today
    IF product_count > 0 THEN
        INSERT IGNORE INTO `cod_dashboard_alerts`
        (`alert_type`, `title`, `message`, `priority`, `target_type`, `created_by`)
        SELECT 'low_stock',
               'تحذير: أصناف منخفضة المخزون',
               CONCAT('يوجد ', product_count, ' صنف وصل للحد الأدنى من المخزون'),
               'high',
               'all',
               1
        WHERE NOT EXISTS (
            SELECT 1 FROM `cod_dashboard_alerts`
            WHERE `alert_type` = 'low_stock'
            AND DATE(`created_at`) = CURDATE()
        );
    END IF;
END$$
DELIMITER ;

-- Fix 6: Create triggers for automatic alert generation
DELIMITER $$
CREATE TRIGGER `tr_product_low_stock_alert`
AFTER UPDATE ON `cod_product_inventory`
FOR EACH ROW
BEGIN
    DECLARE min_quantity DECIMAL(15,4) DEFAULT 0;

    -- Get minimum quantity for product
    SELECT `minimum` INTO min_quantity
    FROM `cod_product`
    WHERE `product_id` = NEW.`product_id`;

    -- Create alert if quantity drops below minimum
    IF NEW.`quantity` <= min_quantity AND OLD.`quantity` > min_quantity AND min_quantity > 0 THEN
        INSERT INTO `cod_dashboard_alerts`
        (`alert_type`, `title`, `message`, `priority`, `target_type`, `created_by`)
        VALUES ('low_stock',
                'تحذير: مخزون منخفض',
                CONCAT('المنتج رقم ', NEW.`product_id`, ' وصل للحد الأدنى من المخزون'),
                'high',
                'all',
                1);
    END IF;
END$$
DELIMITER ;

-- Fix 7: Add indexes for performance optimization
ALTER TABLE `cod_dashboard_alerts`
ADD INDEX `idx_type_priority` (`alert_type`, `priority`),
ADD INDEX `idx_created_at_type` (`created_at`, `alert_type`),
ADD INDEX `idx_expires_priority` (`expires_at`, `priority`);

ALTER TABLE `cod_dashboard_alert_recipients`
ADD INDEX `idx_user_read_dismissed` (`user_id`, `is_read`, `is_dismissed`),
ADD INDEX `idx_alert_read` (`alert_id`, `is_read`);

-- Fix 8: Create function for time ago calculation
DELIMITER $$
CREATE FUNCTION `fn_time_ago`(datetime_value DATETIME)
RETURNS VARCHAR(50)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE time_diff INT;
    DECLARE result VARCHAR(50);

    SET time_diff = TIMESTAMPDIFF(SECOND, datetime_value, NOW());

    IF time_diff < 60 THEN
        SET result = 'الآن';
    ELSEIF time_diff < 3600 THEN
        SET result = CONCAT(FLOOR(time_diff / 60), ' دقيقة');
    ELSEIF time_diff < 86400 THEN
        SET result = CONCAT(FLOOR(time_diff / 3600), ' ساعة');
    ELSEIF time_diff < 604800 THEN
        SET result = CONCAT(FLOOR(time_diff / 86400), ' يوم');
    ELSEIF time_diff < 2592000 THEN
        SET result = CONCAT(FLOOR(time_diff / 604800), ' أسبوع');
    ELSEIF time_diff < 31536000 THEN
        SET result = CONCAT(FLOOR(time_diff / 2592000), ' شهر');
    ELSE
        SET result = CONCAT(FLOOR(time_diff / 31536000), ' سنة');
    END IF;

    RETURN result;
END$$
DELIMITER ;

-- ========================================================================
-- END OF DASHBOARD/ALERTS FIXES
-- ========================================================================
