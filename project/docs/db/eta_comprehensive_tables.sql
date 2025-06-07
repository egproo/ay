-- AYM ERP - Comprehensive ETA (Egyptian Tax Authority) Database Schema
-- Enhanced for complete tax integration with queue system and order modifications
-- Version: 2.0.0
-- Date: 2024

-- ETA Queue System for reliable invoice/receipt sending
CREATE TABLE IF NOT EXISTS `cod_eta_queue` (
  `queue_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'invoice, receipt, credit_note, debit_note, modification_note',
  `data` longtext COMMENT 'JSON data for the document',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, processing, completed, failed',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `max_attempts` int(11) NOT NULL DEFAULT 5,
  `error_message` text,
  `priority` int(11) NOT NULL DEFAULT 0 COMMENT 'Higher number = higher priority',
  `scheduled_time` datetime DEFAULT NULL COMMENT 'For delayed processing',
  `created_date` datetime NOT NULL,
  `last_attempt` datetime DEFAULT NULL,
  `next_attempt` datetime DEFAULT NULL,
  `completed_date` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`queue_id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_next_attempt` (`next_attempt`),
  KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ETA Invoices tracking
CREATE TABLE IF NOT EXISTS `cod_eta_invoices` (
  `invoice_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `internal_id` varchar(100) NOT NULL,
  `eta_uuid` varchar(100) DEFAULT NULL,
  `submission_uuid` varchar(100) DEFAULT NULL,
  `long_id` varchar(200) DEFAULT NULL,
  `hash_key` varchar(200) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, sent, accepted, rejected, cancelled',
  `document_type` varchar(10) NOT NULL DEFAULT 'I' COMMENT 'I=Invoice, C=Credit, D=Debit',
  `total_amount` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `currency_code` varchar(3) NOT NULL DEFAULT 'EGP',
  `request_data` longtext COMMENT 'Original request JSON',
  `response_data` longtext COMMENT 'ETA response JSON',
  `error_data` text COMMENT 'Error details if failed',
  `pdf_url` varchar(500) DEFAULT NULL,
  `qr_code` text,
  `sent_date` datetime DEFAULT NULL,
  `accepted_date` datetime DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`invoice_id`),
  UNIQUE KEY `order_document` (`order_id`, `document_type`),
  KEY `idx_eta_uuid` (`eta_uuid`),
  KEY `idx_status` (`status`),
  KEY `idx_internal_id` (`internal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ETA Receipts tracking
CREATE TABLE IF NOT EXISTS `cod_eta_receipts` (
  `receipt_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `internal_id` varchar(100) NOT NULL,
  `eta_uuid` varchar(100) DEFAULT NULL,
  `receipt_number` varchar(100) NOT NULL,
  `receipt_type` varchar(50) NOT NULL DEFAULT 'retail' COMMENT 'retail, coffee_restaurant, general_services, etc',
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `total_amount` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `currency_code` varchar(3) NOT NULL DEFAULT 'EGP',
  `request_data` longtext,
  `response_data` longtext,
  `error_data` text,
  `qr_code` text,
  `sent_date` datetime DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`receipt_id`),
  UNIQUE KEY `order_receipt` (`order_id`),
  KEY `idx_eta_uuid` (`eta_uuid`),
  KEY `idx_status` (`status`),
  KEY `idx_receipt_type` (`receipt_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ETA Modification Notes (Credit/Debit notes for order changes)
CREATE TABLE IF NOT EXISTS `cod_eta_modification_note` (
  `note_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `original_invoice_id` int(11) NOT NULL,
  `note_type` varchar(10) NOT NULL COMMENT 'credit, debit',
  `modification_type` varchar(20) NOT NULL COMMENT 'increase, decrease, cancel, return',
  `internal_id` varchar(100) NOT NULL,
  `eta_uuid` varchar(100) DEFAULT NULL,
  `submission_uuid` varchar(100) DEFAULT NULL,
  `long_id` varchar(200) DEFAULT NULL,
  `hash_key` varchar(200) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `total_amount` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `reason` text,
  `modification_data` longtext COMMENT 'JSON data of what was modified',
  `request_data` longtext,
  `response_data` longtext,
  `error_data` text,
  `sent_date` datetime DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`note_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_original_invoice` (`original_invoice_id`),
  KEY `idx_note_type` (`note_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ETA Logs for detailed tracking
CREATE TABLE IF NOT EXISTS `cod_eta_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `document_type` varchar(50) NOT NULL COMMENT 'invoice, receipt, credit_note, debit_note',
  `action` varchar(50) NOT NULL COMMENT 'send, retry, success, failure, cancel',
  `status` varchar(20) NOT NULL,
  `message` text,
  `request_data` longtext,
  `response_data` longtext,
  `error_data` text,
  `execution_time` decimal(8,3) DEFAULT NULL COMMENT 'Execution time in seconds',
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_document_type` (`document_type`),
  KEY `idx_action` (`action`),
  KEY `idx_status` (`status`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ETA Settings for granular control
CREATE TABLE IF NOT EXISTS `cod_eta_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text,
  `group` varchar(50) NOT NULL DEFAULT 'general',
  `description` text,
  `type` varchar(20) NOT NULL DEFAULT 'text' COMMENT 'text, number, boolean, json',
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`key`),
  KEY `idx_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default ETA settings
INSERT IGNORE INTO `cod_eta_settings` (`key`, `value`, `group`, `description`, `type`, `date_added`, `date_modified`) VALUES
('eta_environment', 'preprod', 'connection', 'ETA Environment (production/preprod)', 'text', NOW(), NOW()),
('eta_invoice_enabled', '0', 'invoice', 'Enable automatic invoice sending', 'boolean', NOW(), NOW()),
('eta_invoice_auto_send', '0', 'invoice', 'Send invoices automatically on order creation', 'boolean', NOW(), NOW()),
('eta_credit_note_enabled', '0', 'invoice', 'Enable credit note sending', 'boolean', NOW(), NOW()),
('eta_debit_note_enabled', '0', 'invoice', 'Enable debit note sending', 'boolean', NOW(), NOW()),
('eta_order_modification_enabled', '0', 'invoice', 'Enable order modification notes', 'boolean', NOW(), NOW()),
('eta_receipt_enabled', '0', 'receipt', 'Enable automatic receipt sending', 'boolean', NOW(), NOW()),
('eta_receipt_type', 'retail', 'receipt', 'Default receipt type', 'text', NOW(), NOW()),
('eta_pos_auto_receipt', '0', 'receipt', 'Send receipts automatically from POS', 'boolean', NOW(), NOW()),
('eta_queue_enabled', '1', 'queue', 'Enable queue system', 'boolean', NOW(), NOW()),
('eta_max_attempts', '5', 'queue', 'Maximum retry attempts', 'number', NOW(), NOW()),
('eta_retry_interval', '5', 'queue', 'Retry interval in minutes', 'number', NOW(), NOW()),
('eta_auto_process_queue', '1', 'queue', 'Auto process queue', 'boolean', NOW(), NOW()),
('eta_failure_notifications', '1', 'notifications', 'Send failure notifications', 'boolean', NOW(), NOW()),
('eta_notification_email', '', 'notifications', 'Email for notifications', 'text', NOW(), NOW()),
('eta_detailed_logging', '1', 'logging', 'Enable detailed logging', 'boolean', NOW(), NOW());

-- ETA Document Status tracking
CREATE TABLE IF NOT EXISTS `cod_eta_document_status` (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `document_uuid` varchar(100) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `status_reason` text,
  `status_date` datetime NOT NULL,
  `checked_date` datetime DEFAULT NULL,
  `next_check` datetime DEFAULT NULL,
  `check_count` int(11) NOT NULL DEFAULT 0,
  `is_final` tinyint(1) NOT NULL DEFAULT 0,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `document_status` (`document_uuid`, `status_date`),
  KEY `idx_document_uuid` (`document_uuid`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_next_check` (`next_check`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ETA API Rate Limiting
CREATE TABLE IF NOT EXISTS `cod_eta_rate_limit` (
  `limit_id` int(11) NOT NULL AUTO_INCREMENT,
  `endpoint` varchar(100) NOT NULL,
  `requests_count` int(11) NOT NULL DEFAULT 0,
  `window_start` datetime NOT NULL,
  `window_end` datetime NOT NULL,
  `limit_exceeded` tinyint(1) NOT NULL DEFAULT 0,
  `reset_time` datetime DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`limit_id`),
  UNIQUE KEY `endpoint_window` (`endpoint`, `window_start`),
  KEY `idx_window_end` (`window_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ETA Webhook Events
CREATE TABLE IF NOT EXISTS `cod_eta_webhook` (
  `webhook_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `document_uuid` varchar(100) NOT NULL,
  `payload` longtext NOT NULL,
  `signature` varchar(500) DEFAULT NULL,
  `processed` tinyint(1) NOT NULL DEFAULT 0,
  `processed_date` datetime DEFAULT NULL,
  `error_message` text,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `date_received` datetime NOT NULL,
  PRIMARY KEY (`webhook_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_document_uuid` (`document_uuid`),
  KEY `idx_processed` (`processed`),
  KEY `idx_date_received` (`date_received`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ETA Statistics for dashboard
CREATE TABLE IF NOT EXISTS `cod_eta_statistics` (
  `stat_id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `invoices_sent` int(11) NOT NULL DEFAULT 0,
  `invoices_accepted` int(11) NOT NULL DEFAULT 0,
  `invoices_rejected` int(11) NOT NULL DEFAULT 0,
  `receipts_sent` int(11) NOT NULL DEFAULT 0,
  `credit_notes_sent` int(11) NOT NULL DEFAULT 0,
  `debit_notes_sent` int(11) NOT NULL DEFAULT 0,
  `queue_processed` int(11) NOT NULL DEFAULT 0,
  `queue_failed` int(11) NOT NULL DEFAULT 0,
  `total_amount` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `total_tax` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `avg_response_time` decimal(8,3) DEFAULT NULL,
  `date_updated` datetime NOT NULL,
  PRIMARY KEY (`stat_id`),
  UNIQUE KEY `date_unique` (`date`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for performance optimization
CREATE INDEX IF NOT EXISTS `idx_eta_queue_processing` ON `cod_eta_queue` (`status`, `next_attempt`, `priority`);
CREATE INDEX IF NOT EXISTS `idx_eta_invoices_lookup` ON `cod_eta_invoices` (`order_id`, `status`, `document_type`);
CREATE INDEX IF NOT EXISTS `idx_eta_log_search` ON `cod_eta_log` (`order_id`, `document_type`, `date_added`);

-- Create triggers for automatic statistics updates
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS `eta_invoice_stats_insert`
AFTER INSERT ON `cod_eta_invoices`
FOR EACH ROW
BEGIN
    INSERT INTO `cod_eta_statistics` (`date`, `invoices_sent`, `total_amount`, `total_tax`, `date_updated`)
    VALUES (DATE(NEW.date_added), 1, NEW.total_amount, NEW.tax_amount, NOW())
    ON DUPLICATE KEY UPDATE
        `invoices_sent` = `invoices_sent` + 1,
        `total_amount` = `total_amount` + NEW.total_amount,
        `total_tax` = `total_tax` + NEW.tax_amount,
        `date_updated` = NOW();
END$$

CREATE TRIGGER IF NOT EXISTS `eta_invoice_stats_update`
AFTER UPDATE ON `cod_eta_invoices`
FOR EACH ROW
BEGIN
    IF NEW.status = 'accepted' AND OLD.status != 'accepted' THEN
        INSERT INTO `cod_eta_statistics` (`date`, `invoices_accepted`, `date_updated`)
        VALUES (DATE(NEW.accepted_date), 1, NOW())
        ON DUPLICATE KEY UPDATE
            `invoices_accepted` = `invoices_accepted` + 1,
            `date_updated` = NOW();
    END IF;

    IF NEW.status = 'rejected' AND OLD.status != 'rejected' THEN
        INSERT INTO `cod_eta_statistics` (`date`, `invoices_rejected`, `date_updated`)
        VALUES (DATE(NEW.date_modified), 1, NOW())
        ON DUPLICATE KEY UPDATE
            `invoices_rejected` = `invoices_rejected` + 1,
            `date_updated` = NOW();
    END IF;
END$$

DELIMITER ;

-- Create views for easier data access
CREATE OR REPLACE VIEW `view_eta_dashboard_stats` AS
SELECT
    DATE(CURDATE()) as today,
    COALESCE(SUM(CASE WHEN DATE(ei.date_added) = CURDATE() THEN 1 ELSE 0 END), 0) as today_invoices,
    COALESCE(SUM(CASE WHEN DATE(er.date_added) = CURDATE() THEN 1 ELSE 0 END), 0) as today_receipts,
    COALESCE(SUM(CASE WHEN eq.status = 'pending' THEN 1 ELSE 0 END), 0) as pending_queue,
    COALESCE(SUM(CASE WHEN eq.status = 'failed' THEN 1 ELSE 0 END), 0) as failed_queue,
    COALESCE(AVG(CASE WHEN ei.status = 'sent' THEN 1 ELSE 0 END) * 100, 0) as success_rate
FROM `cod_eta_invoices` ei
CROSS JOIN `cod_eta_receipts` er
CROSS JOIN `cod_eta_queue` eq;

-- Add foreign key constraints
ALTER TABLE `cod_eta_modification_note`
ADD CONSTRAINT `fk_eta_modification_invoice`
FOREIGN KEY (`original_invoice_id`) REFERENCES `cod_eta_invoices` (`invoice_id`) ON DELETE CASCADE;

-- Add stored procedures for common operations
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `sp_eta_cleanup_old_logs`(IN days_to_keep INT)
BEGIN
    DELETE FROM `cod_eta_log`
    WHERE `date_added` < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);

    DELETE FROM `cod_eta_queue`
    WHERE `status` IN ('completed', 'failed')
    AND `completed_date` < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
END$$

CREATE PROCEDURE IF NOT EXISTS `sp_eta_get_queue_stats`()
BEGIN
    SELECT
        `status`,
        COUNT(*) as count,
        AVG(`attempts`) as avg_attempts,
        MIN(`created_date`) as oldest,
        MAX(`created_date`) as newest
    FROM `cod_eta_queue`
    GROUP BY `status`;
END$$

DELIMITER ;

-- Order Modification System Tables
-- Comprehensive order modification tracking with ETA integration

-- Main order modifications table
CREATE TABLE IF NOT EXISTS `cod_order_modification` (
  `modification_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `modification_type` varchar(20) NOT NULL COMMENT 'increase, decrease, mixed, cancel',
  `amount_change` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `tax_change` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `reason` varchar(255) DEFAULT NULL,
  `notes` text,
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, rejected, completed',
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `eta_status` varchar(20) DEFAULT NULL COMMENT 'not_required, pending, sent, accepted, rejected',
  `eta_note_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`modification_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_modification_type` (`modification_type`),
  KEY `idx_status` (`status`),
  KEY `idx_eta_status` (`eta_status`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Individual modification items
CREATE TABLE IF NOT EXISTS `cod_order_modification_item` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `modification_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `order_product_id` int(11) DEFAULT NULL,
  `modification_type` varchar(50) NOT NULL COMMENT 'quantity, price, add_product, remove_product, change_option, change_unit',
  `old_value` text COMMENT 'JSON of old values',
  `new_value` text COMMENT 'JSON of new values',
  `amount_change` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `tax_change` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `idx_modification_id` (`modification_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_order_product_id` (`order_product_id`),
  KEY `idx_modification_type` (`modification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modification approval workflow
CREATE TABLE IF NOT EXISTS `cod_order_modification_approval` (
  `approval_id` int(11) NOT NULL AUTO_INCREMENT,
  `modification_id` int(11) NOT NULL,
  `approver_id` int(11) NOT NULL,
  `action` varchar(20) NOT NULL COMMENT 'approve, reject, request_changes',
  `comments` text,
  `approval_level` int(11) NOT NULL DEFAULT 1,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`approval_id`),
  KEY `idx_modification_id` (`modification_id`),
  KEY `idx_approver_id` (`approver_id`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modification log for audit trail
CREATE TABLE IF NOT EXISTS `cod_order_modification_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `modification_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL COMMENT 'create, approve, reject, complete, eta_send, eta_success, eta_fail',
  `data` longtext COMMENT 'JSON data of the action',
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_modification_id` (`modification_id`),
  KEY `idx_action` (`action`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product units for multi-unit support
CREATE TABLE IF NOT EXISTS `cod_product_unit` (
  `product_unit_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `conversion_factor` decimal(15,8) NOT NULL DEFAULT 1.00000000,
  `price_factor` decimal(15,8) NOT NULL DEFAULT 1.00000000,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`product_unit_id`),
  UNIQUE KEY `product_unit` (`product_id`, `unit_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_unit_id` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Units master table
CREATE TABLE IF NOT EXISTS `cod_unit` (
  `unit_id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `eta_code` varchar(10) DEFAULT NULL COMMENT 'ETA unit code mapping',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`unit_id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Unit descriptions
CREATE TABLE IF NOT EXISTS `cod_unit_description` (
  `unit_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `abbreviation` varchar(10) NOT NULL,
  PRIMARY KEY (`unit_id`, `language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default units
INSERT IGNORE INTO `cod_unit` (`unit_id`, `code`, `eta_code`, `sort_order`, `status`, `date_added`) VALUES
(37, 'PCE', 'PCE', 1, 1, NOW()),
(38, 'KGM', 'KGM', 2, 1, NOW()),
(39, 'LTR', 'LTR', 3, 1, NOW()),
(40, 'MTR', 'MTR', 4, 1, NOW()),
(41, 'BOX', 'BOX', 5, 1, NOW()),
(42, 'SET', 'SET', 6, 1, NOW()),
(43, 'DOZ', 'DOZ', 7, 1, NOW()),
(44, 'GRM', 'GRM', 8, 1, NOW()),
(45, 'TON', 'TNE', 9, 1, NOW()),
(46, 'SQM', 'MTK', 10, 1, NOW());

-- Insert unit descriptions (Arabic)
INSERT IGNORE INTO `cod_unit_description` (`unit_id`, `language_id`, `name`, `abbreviation`) VALUES
(37, 1, 'قطعة', 'قطعة'),
(38, 1, 'كيلوجرام', 'كجم'),
(39, 1, 'لتر', 'لتر'),
(40, 1, 'متر', 'متر'),
(41, 1, 'صندوق', 'صندوق'),
(42, 1, 'طقم', 'طقم'),
(43, 1, 'دستة', 'دستة'),
(44, 1, 'جرام', 'جم'),
(45, 1, 'طن', 'طن'),
(46, 1, 'متر مربع', 'م²');

-- Insert unit descriptions (English)
INSERT IGNORE INTO `cod_unit_description` (`unit_id`, `language_id`, `name`, `abbreviation`) VALUES
(37, 2, 'Piece', 'pcs'),
(38, 2, 'Kilogram', 'kg'),
(39, 2, 'Liter', 'L'),
(40, 2, 'Meter', 'm'),
(41, 2, 'Box', 'box'),
(42, 2, 'Set', 'set'),
(43, 2, 'Dozen', 'doz'),
(44, 2, 'Gram', 'g'),
(45, 2, 'Ton', 'ton'),
(46, 2, 'Square Meter', 'm²');

-- Modification templates for common scenarios
CREATE TABLE IF NOT EXISTS `cod_order_modification_template` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `template_data` longtext NOT NULL COMMENT 'JSON template structure',
  `category` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `user_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`template_id`),
  KEY `idx_category` (`category`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert common modification templates
INSERT IGNORE INTO `cod_order_modification_template` (`name`, `description`, `template_data`, `category`, `user_id`, `date_added`) VALUES
('زيادة الكمية', 'قالب لزيادة كمية المنتجات', '{"type":"quantity","action":"increase","fields":["product_id","new_quantity","reason"]}', 'quantity', 1, NOW()),
('تقليل الكمية', 'قالب لتقليل كمية المنتجات', '{"type":"quantity","action":"decrease","fields":["product_id","new_quantity","reason"]}', 'quantity', 1, NOW()),
('تغيير السعر', 'قالب لتغيير أسعار المنتجات', '{"type":"price","action":"change","fields":["product_id","new_price","reason"]}', 'price', 1, NOW()),
('إضافة منتج', 'قالب لإضافة منتج جديد للطلب', '{"type":"product","action":"add","fields":["product_id","quantity","price","options"]}', 'product', 1, NOW()),
('حذف منتج', 'قالب لحذف منتج من الطلب', '{"type":"product","action":"remove","fields":["order_product_id","reason"]}', 'product', 1, NOW());

-- Modification rules and validations
CREATE TABLE IF NOT EXISTS `cod_order_modification_rule` (
  `rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `rule_type` varchar(50) NOT NULL COMMENT 'validation, approval, notification, eta',
  `conditions` longtext NOT NULL COMMENT 'JSON conditions',
  `actions` longtext NOT NULL COMMENT 'JSON actions to take',
  `priority` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`rule_id`),
  KEY `idx_rule_type` (`rule_type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default modification rules
INSERT IGNORE INTO `cod_order_modification_rule` (`name`, `description`, `rule_type`, `conditions`, `actions`, `priority`, `date_added`) VALUES
('تتطلب موافقة للمبالغ الكبيرة', 'التعديلات التي تزيد عن 1000 جنيه تتطلب موافقة', 'approval', '{"amount_change":{"operator":">","value":1000}}', '{"require_approval":true,"approval_level":2}', 1, NOW()),
('إشعار ETA للتغييرات الضريبية', 'إرسال إشعار ETA للتغييرات التي تؤثر على الضرائب', 'eta', '{"tax_change":{"operator":"!=","value":0}}', '{"send_eta_note":true,"note_type":"auto"}', 2, NOW()),
('منع التعديل للطلبات المكتملة', 'منع تعديل الطلبات المكتملة والمرسلة', 'validation', '{"order_status":{"operator":"in","value":[7,8,9]}}', '{"block_modification":true,"message":"لا يمكن تعديل الطلبات المكتملة"}', 3, NOW());

-- Create foreign key constraints
ALTER TABLE `cod_order_modification_item`
ADD CONSTRAINT `fk_modification_item_modification`
FOREIGN KEY (`modification_id`) REFERENCES `cod_order_modification` (`modification_id`) ON DELETE CASCADE;

ALTER TABLE `cod_order_modification_approval`
ADD CONSTRAINT `fk_modification_approval_modification`
FOREIGN KEY (`modification_id`) REFERENCES `cod_order_modification` (`modification_id`) ON DELETE CASCADE;

ALTER TABLE `cod_order_modification_log`
ADD CONSTRAINT `fk_modification_log_modification`
FOREIGN KEY (`modification_id`) REFERENCES `cod_order_modification` (`modification_id`) ON DELETE SET NULL;

ALTER TABLE `cod_product_unit`
ADD CONSTRAINT `fk_product_unit_unit`
FOREIGN KEY (`unit_id`) REFERENCES `cod_unit` (`unit_id`) ON DELETE CASCADE;

-- Create triggers for automatic ETA processing
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS `order_modification_eta_check`
AFTER INSERT ON `cod_order_modification`
FOR EACH ROW
BEGIN
    DECLARE eta_required BOOLEAN DEFAULT FALSE;
    DECLARE eta_threshold DECIMAL(15,4) DEFAULT 0;

    -- Get ETA threshold from settings
    SELECT CAST(value AS DECIMAL(15,4)) INTO eta_threshold
    FROM `cod_eta_settings`
    WHERE `key` = 'eta_modification_threshold'
    LIMIT 1;

    -- Check if ETA notification is required
    IF ABS(NEW.amount_change) > eta_threshold AND NEW.modification_type != 'cancel' THEN
        SET eta_required = TRUE;
    END IF;

    -- Update ETA status
    IF eta_required THEN
        UPDATE `cod_order_modification`
        SET `eta_status` = 'pending'
        WHERE `modification_id` = NEW.modification_id;

        -- Add to ETA queue
        INSERT INTO `cod_eta_queue` (`order_id`, `type`, `data`, `status`, `created_date`, `next_attempt`)
        VALUES (NEW.order_id, 'modification_note',
                JSON_OBJECT('modification_id', NEW.modification_id, 'modification_type', NEW.modification_type),
                'pending', NOW(), NOW());
    ELSE
        UPDATE `cod_order_modification`
        SET `eta_status` = 'not_required'
        WHERE `modification_id` = NEW.modification_id;
    END IF;
END$$

CREATE TRIGGER IF NOT EXISTS `order_modification_stats_update`
AFTER UPDATE ON `cod_order_modification`
FOR EACH ROW
BEGIN
    -- Update daily statistics
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        INSERT INTO `cod_eta_statistics` (`date`, `date_updated`)
        VALUES (DATE(NEW.date_modified), NOW())
        ON DUPLICATE KEY UPDATE `date_updated` = NOW();
    END IF;
END$$

DELIMITER ;

-- Create views for reporting
CREATE OR REPLACE VIEW `view_order_modification_summary` AS
SELECT
    om.modification_id,
    om.order_id,
    o.order_id as order_number,
    CONCAT(o.firstname, ' ', o.lastname) as customer_name,
    om.modification_type,
    om.amount_change,
    om.tax_change,
    om.status,
    om.eta_status,
    om.date_added,
    u.username as modified_by,
    COUNT(omi.item_id) as item_count
FROM `cod_order_modification` om
LEFT JOIN `cod_order` o ON (om.order_id = o.order_id)
LEFT JOIN `cod_user` u ON (om.user_id = u.user_id)
LEFT JOIN `cod_order_modification_item` omi ON (om.modification_id = omi.modification_id)
GROUP BY om.modification_id;

-- Create stored procedures for common operations
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `sp_get_modification_impact`(IN p_order_id INT)
BEGIN
    SELECT
        modification_type,
        COUNT(*) as count,
        SUM(amount_change) as total_amount_change,
        SUM(tax_change) as total_tax_change,
        AVG(amount_change) as avg_amount_change
    FROM `cod_order_modification`
    WHERE order_id = p_order_id
    AND status = 'completed'
    GROUP BY modification_type;
END$$

CREATE PROCEDURE IF NOT EXISTS `sp_process_pending_eta_modifications`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE mod_id INT;
    DECLARE ord_id INT;
    DECLARE mod_type VARCHAR(20);

    DECLARE cur CURSOR FOR
        SELECT modification_id, order_id, modification_type
        FROM `cod_order_modification`
        WHERE eta_status = 'pending'
        AND status = 'completed'
        LIMIT 10;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO mod_id, ord_id, mod_type;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Add to ETA queue if not already there
        INSERT IGNORE INTO `cod_eta_queue` (`order_id`, `type`, `data`, `status`, `created_date`, `next_attempt`)
        VALUES (ord_id, 'modification_note',
                JSON_OBJECT('modification_id', mod_id, 'modification_type', mod_type),
                'pending', NOW(), NOW());

    END LOOP;

    CLOSE cur;
END$$

DELIMITER ;

-- Create comprehensive indexes for performance
CREATE INDEX IF NOT EXISTS `idx_order_modification_search` ON `cod_order_modification` (`order_id`, `status`, `modification_type`, `date_added`);
CREATE INDEX IF NOT EXISTS `idx_order_modification_eta` ON `cod_order_modification` (`eta_status`, `status`, `date_added`);
CREATE INDEX IF NOT EXISTS `idx_modification_item_search` ON `cod_order_modification_item` (`modification_id`, `modification_type`, `product_id`);
CREATE INDEX IF NOT EXISTS `idx_modification_log_audit` ON `cod_order_modification_log` (`order_id`, `action`, `date_added`);
CREATE INDEX IF NOT EXISTS `idx_product_unit_lookup` ON `cod_product_unit` (`product_id`, `is_default`, `status`);

-- Add configuration settings for order modifications
INSERT IGNORE INTO `cod_eta_settings` (`key`, `value`, `group`, `description`, `type`, `date_added`, `date_modified`) VALUES
('order_modification_enabled', '1', 'order_modification', 'Enable order modification system', 'boolean', NOW(), NOW()),
('order_modification_max_days', '30', 'order_modification', 'Maximum days to allow modifications', 'number', NOW(), NOW()),
('order_modification_approval_required', '0', 'order_modification', 'Require approval for modifications', 'boolean', NOW(), NOW()),
('order_modification_approval_threshold', '1000', 'order_modification', 'Amount threshold requiring approval', 'number', NOW(), NOW()),
('eta_modification_threshold', '100', 'order_modification', 'Amount threshold for ETA notification', 'number', NOW(), NOW()),
('order_modification_auto_eta', '1', 'order_modification', 'Automatically send ETA notes for modifications', 'boolean', NOW(), NOW());

-- Create event scheduler for automatic processing
DELIMITER $$

CREATE EVENT IF NOT EXISTS `evt_process_eta_modifications`
ON SCHEDULE EVERY 5 MINUTE
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    CALL sp_process_pending_eta_modifications();
END$$

DELIMITER ;

-- Enable event scheduler (if not already enabled)
-- SET GLOBAL event_scheduler = ON;

-- Tax Filing System Tables
-- Professional tax filing and compliance management

-- Tax filing records
CREATE TABLE IF NOT EXISTS `cod_tax_filing` (
  `filing_id` int(11) NOT NULL AUTO_INCREMENT,
  `filing_period` varchar(20) NOT NULL COMMENT 'YYYY-MM for monthly, YYYY-QQ for quarterly',
  `filing_type` varchar(20) NOT NULL DEFAULT 'monthly' COMMENT 'monthly, quarterly, yearly',
  `filing_data` longtext NOT NULL COMMENT 'JSON filing data',
  `status` varchar(20) NOT NULL DEFAULT 'generated' COMMENT 'generated, submitted, accepted, rejected',
  `submission_reference` varchar(100) DEFAULT NULL,
  `submission_date` datetime DEFAULT NULL,
  `response_data` longtext COMMENT 'Response from tax authority',
  `total_tax_amount` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `total_taxable_amount` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `user_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`filing_id`),
  UNIQUE KEY `filing_period_type` (`filing_period`, `filing_type`),
  KEY `idx_filing_period` (`filing_period`),
  KEY `idx_filing_type` (`filing_type`),
  KEY `idx_status` (`status`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tax compliance tracking
CREATE TABLE IF NOT EXISTS `cod_tax_compliance` (
  `compliance_id` int(11) NOT NULL AUTO_INCREMENT,
  `compliance_type` varchar(50) NOT NULL COMMENT 'eta_submission, tax_filing, audit_trail',
  `reference_id` int(11) NOT NULL COMMENT 'Order ID, Filing ID, etc.',
  `compliance_status` varchar(20) NOT NULL COMMENT 'compliant, non_compliant, pending, warning',
  `compliance_score` decimal(5,2) DEFAULT NULL COMMENT 'Compliance score 0-100',
  `issues` longtext COMMENT 'JSON array of compliance issues',
  `recommendations` longtext COMMENT 'JSON array of recommendations',
  `checked_date` datetime NOT NULL,
  `next_check_date` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`compliance_id`),
  KEY `idx_compliance_type` (`compliance_type`),
  KEY `idx_reference_id` (`reference_id`),
  KEY `idx_compliance_status` (`compliance_status`),
  KEY `idx_checked_date` (`checked_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tax audit trail
CREATE TABLE IF NOT EXISTS `cod_tax_audit_trail` (
  `audit_id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) NOT NULL COMMENT 'order, invoice, filing, modification',
  `entity_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'create, update, delete, submit, approve, reject',
  `old_values` longtext COMMENT 'JSON of old values',
  `new_values` longtext COMMENT 'JSON of new values',
  `tax_impact` decimal(15,4) DEFAULT NULL COMMENT 'Tax amount impact of the change',
  `reason` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`audit_id`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_action` (`action`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_date_added` (`date_added`),
  KEY `idx_tax_impact` (`tax_impact`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tax rate history for compliance
CREATE TABLE IF NOT EXISTS `cod_tax_rate_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `tax_class_id` int(11) NOT NULL,
  `tax_rate_id` int(11) NOT NULL,
  `old_rate` decimal(15,4) NOT NULL,
  `new_rate` decimal(15,4) NOT NULL,
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `government_reference` varchar(100) DEFAULT NULL COMMENT 'Government decree/law reference',
  `user_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_tax_class_id` (`tax_class_id`),
  KEY `idx_tax_rate_id` (`tax_rate_id`),
  KEY `idx_effective_date` (`effective_date`),
  KEY `idx_end_date` (`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tax exemption tracking
CREATE TABLE IF NOT EXISTS `cod_tax_exemption` (
  `exemption_id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(20) NOT NULL COMMENT 'customer, product, order',
  `entity_id` int(11) NOT NULL,
  `exemption_type` varchar(50) NOT NULL COMMENT 'full_exempt, partial_exempt, temporary_exempt',
  `exemption_reason` varchar(255) NOT NULL,
  `exemption_certificate` varchar(255) DEFAULT NULL COMMENT 'Certificate file path',
  `exemption_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Percentage of exemption',
  `valid_from` date NOT NULL,
  `valid_to` date DEFAULT NULL,
  `government_reference` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, expired, revoked',
  `user_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`exemption_id`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_exemption_type` (`exemption_type`),
  KEY `idx_valid_period` (`valid_from`, `valid_to`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tax reporting templates
CREATE TABLE IF NOT EXISTS `cod_tax_report_template` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `report_type` varchar(50) NOT NULL COMMENT 'summary, detailed, compliance, filing',
  `template_config` longtext NOT NULL COMMENT 'JSON configuration',
  `filters` longtext COMMENT 'JSON default filters',
  `schedule` varchar(50) DEFAULT NULL COMMENT 'Cron expression for scheduled reports',
  `recipients` longtext COMMENT 'JSON array of email recipients',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `user_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`template_id`),
  KEY `idx_report_type` (`report_type`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tax alert rules
CREATE TABLE IF NOT EXISTS `cod_tax_alert_rule` (
  `rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `rule_type` varchar(50) NOT NULL COMMENT 'threshold, compliance, deadline, anomaly',
  `conditions` longtext NOT NULL COMMENT 'JSON conditions',
  `alert_level` varchar(20) NOT NULL DEFAULT 'warning' COMMENT 'info, warning, error, critical',
  `notification_methods` longtext COMMENT 'JSON array of notification methods',
  `recipients` longtext COMMENT 'JSON array of recipients',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_triggered` datetime DEFAULT NULL,
  `trigger_count` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`rule_id`),
  KEY `idx_rule_type` (`rule_type`),
  KEY `idx_alert_level` (`alert_level`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_last_triggered` (`last_triggered`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tax alert log
CREATE TABLE IF NOT EXISTS `cod_tax_alert_log` (
  `alert_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_id` int(11) NOT NULL,
  `alert_level` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `alert_data` longtext COMMENT 'JSON data that triggered the alert',
  `notification_sent` tinyint(1) NOT NULL DEFAULT 0,
  `notification_methods` longtext COMMENT 'JSON methods used for notification',
  `acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_date` datetime DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`alert_log_id`),
  KEY `idx_rule_id` (`rule_id`),
  KEY `idx_alert_level` (`alert_level`),
  KEY `idx_notification_sent` (`notification_sent`),
  KEY `idx_acknowledged` (`acknowledged`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default tax report templates
INSERT IGNORE INTO `cod_tax_report_template` (`name`, `description`, `report_type`, `template_config`, `filters`, `user_id`, `date_added`, `date_modified`) VALUES
('تقرير الضرائب الشهري', 'تقرير شامل للضرائب المحصلة شهرياً', 'summary', '{"charts":["monthly_trends","tax_breakdown"],"tables":["tax_summary","top_customers"],"export_formats":["pdf","excel"]}', '{"period":"monthly","include_eta":true}', 1, NOW(), NOW()),
('تقرير امتثال ETA', 'تقرير حالة الامتثال لمصلحة الضرائب المصرية', 'compliance', '{"charts":["eta_compliance","success_rate"],"tables":["pending_submissions","failed_submissions"],"alerts":true}', '{"eta_only":true,"include_errors":true}', 1, NOW(), NOW()),
('تقرير الإقرار الضريبي', 'تقرير تحضير الإقرار الضريبي الربع سنوي', 'filing', '{"sections":["summary","breakdown","exemptions","adjustments"],"calculations":true,"validation":true}', '{"period":"quarterly","include_all_taxes":true}', 1, NOW(), NOW());

-- Insert default tax alert rules
INSERT IGNORE INTO `cod_tax_alert_rule` (`name`, `description`, `rule_type`, `conditions`, `alert_level`, `notification_methods`, `recipients`, `user_id`, `date_added`, `date_modified`) VALUES
('فشل إرسال ETA', 'تنبيه عند فشل إرسال فاتورة لمصلحة الضرائب', 'compliance', '{"eta_status":"failed","attempts":">3"}', 'error', '["email","dashboard"]', '["admin@company.com"]', 1, NOW(), NOW()),
('تجاوز حد الضرائب', 'تنبيه عند تجاوز مبلغ الضرائب اليومي حد معين', 'threshold', '{"daily_tax_amount":">10000"}', 'warning', '["email"]', '["finance@company.com"]', 1, NOW(), NOW()),
('اقتراب موعد الإقرار', 'تنبيه قبل موعد تقديم الإقرار الضريبي', 'deadline', '{"filing_deadline":"7_days_before"}', 'warning', '["email","sms"]', '["admin@company.com","finance@company.com"]', 1, NOW(), NOW());

-- Create foreign key constraints for tax system
ALTER TABLE `cod_tax_compliance`
ADD CONSTRAINT `fk_tax_compliance_user`
FOREIGN KEY (`user_id`) REFERENCES `cod_user` (`user_id`) ON DELETE SET NULL;

ALTER TABLE `cod_tax_audit_trail`
ADD CONSTRAINT `fk_tax_audit_user`
FOREIGN KEY (`user_id`) REFERENCES `cod_user` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `cod_tax_alert_log`
ADD CONSTRAINT `fk_tax_alert_log_rule`
FOREIGN KEY (`rule_id`) REFERENCES `cod_tax_alert_rule` (`rule_id`) ON DELETE CASCADE;

-- Create triggers for automatic tax audit trail
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS `tax_audit_order_update`
AFTER UPDATE ON `cod_order`
FOR EACH ROW
BEGIN
    DECLARE tax_change DECIMAL(15,4) DEFAULT 0;

    -- Calculate tax impact
    SELECT (NEW.total - OLD.total) INTO tax_change;

    -- Log if there's a significant change
    IF ABS(tax_change) > 0.01 THEN
        INSERT INTO `cod_tax_audit_trail` (`entity_type`, `entity_id`, `action`, `old_values`, `new_values`, `tax_impact`, `user_id`, `date_added`)
        VALUES ('order', NEW.order_id, 'update',
                JSON_OBJECT('total', OLD.total, 'status', OLD.order_status_id),
                JSON_OBJECT('total', NEW.total, 'status', NEW.order_status_id),
                tax_change, 1, NOW());
    END IF;
END$$

CREATE TRIGGER IF NOT EXISTS `tax_audit_eta_invoice_insert`
AFTER INSERT ON `cod_eta_invoices`
FOR EACH ROW
BEGIN
    INSERT INTO `cod_tax_audit_trail` (`entity_type`, `entity_id`, `action`, `new_values`, `tax_impact`, `user_id`, `date_added`)
    VALUES ('invoice', NEW.invoice_id, 'create',
            JSON_OBJECT('order_id', NEW.order_id, 'total_amount', NEW.total_amount, 'tax_amount', NEW.tax_amount, 'status', NEW.status),
            NEW.tax_amount, 1, NOW());
END$$

DELIMITER ;

-- Create comprehensive indexes for tax reporting performance
CREATE INDEX IF NOT EXISTS `idx_tax_filing_period_status` ON `cod_tax_filing` (`filing_period`, `status`, `filing_type`);
CREATE INDEX IF NOT EXISTS `idx_tax_compliance_check` ON `cod_tax_compliance` (`compliance_type`, `compliance_status`, `checked_date`);
CREATE INDEX IF NOT EXISTS `idx_tax_audit_entity_date` ON `cod_tax_audit_trail` (`entity_type`, `entity_id`, `date_added`);
CREATE INDEX IF NOT EXISTS `idx_tax_exemption_entity_valid` ON `cod_tax_exemption` (`entity_type`, `entity_id`, `valid_from`, `valid_to`, `status`);
CREATE INDEX IF NOT EXISTS `idx_tax_alert_active_level` ON `cod_tax_alert_rule` (`is_active`, `alert_level`, `rule_type`);

-- Create views for tax reporting
CREATE OR REPLACE VIEW `view_tax_summary_daily` AS
SELECT
    DATE(o.date_added) as report_date,
    COUNT(DISTINCT o.order_id) as order_count,
    SUM(ot.value) as total_tax_collected,
    SUM(o.total) as total_sales,
    AVG(ot.value) as avg_tax_per_order,
    COUNT(CASE WHEN ei.status = 'sent' THEN 1 END) as eta_sent_count,
    COUNT(CASE WHEN ei.status = 'accepted' THEN 1 END) as eta_accepted_count,
    (COUNT(CASE WHEN ei.status = 'accepted' THEN 1 END) / COUNT(DISTINCT o.order_id) * 100) as eta_success_rate
FROM `cod_order` o
LEFT JOIN `cod_order_total` ot ON (o.order_id = ot.order_id AND ot.code = 'tax')
LEFT JOIN `cod_eta_invoices` ei ON (o.order_id = ei.order_id)
WHERE o.order_status_id > 0
AND DATE(o.date_added) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(o.date_added)
ORDER BY report_date DESC;

CREATE OR REPLACE VIEW `view_tax_compliance_status` AS
SELECT
    'ETA Compliance' as compliance_area,
    COUNT(CASE WHEN ei.status = 'sent' THEN 1 END) as compliant_count,
    COUNT(CASE WHEN ei.status IS NULL OR ei.status = 'failed' THEN 1 END) as non_compliant_count,
    COUNT(*) as total_count,
    (COUNT(CASE WHEN ei.status = 'sent' THEN 1 END) / COUNT(*) * 100) as compliance_percentage
FROM `cod_order` o
LEFT JOIN `cod_eta_invoices` ei ON (o.order_id = ei.order_id)
WHERE o.order_status_id > 0
AND DATE(o.date_added) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)

UNION ALL

SELECT
    'Tax Filing' as compliance_area,
    COUNT(CASE WHEN tf.status = 'submitted' THEN 1 END) as compliant_count,
    COUNT(CASE WHEN tf.status = 'generated' THEN 1 END) as non_compliant_count,
    COUNT(*) as total_count,
    (COUNT(CASE WHEN tf.status = 'submitted' THEN 1 END) / COUNT(*) * 100) as compliance_percentage
FROM `cod_tax_filing` tf
WHERE DATE(tf.date_added) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY);

-- Create stored procedures for tax operations
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `sp_generate_tax_compliance_report`(IN p_date_start DATE, IN p_date_end DATE)
BEGIN
    SELECT
        'Tax Collection' as metric,
        SUM(ot.value) as total_amount,
        COUNT(DISTINCT o.order_id) as transaction_count,
        AVG(ot.value) as average_amount
    FROM `cod_order` o
    LEFT JOIN `cod_order_total` ot ON (o.order_id = ot.order_id AND ot.code = 'tax')
    WHERE DATE(o.date_added) BETWEEN p_date_start AND p_date_end
    AND o.order_status_id > 0

    UNION ALL

    SELECT
        'ETA Submissions' as metric,
        COUNT(CASE WHEN ei.status = 'sent' THEN 1 END) as total_amount,
        COUNT(*) as transaction_count,
        (COUNT(CASE WHEN ei.status = 'sent' THEN 1 END) / COUNT(*) * 100) as average_amount
    FROM `cod_eta_invoices` ei
    LEFT JOIN `cod_order` o ON (ei.order_id = o.order_id)
    WHERE DATE(o.date_added) BETWEEN p_date_start AND p_date_end;
END$$

CREATE PROCEDURE IF NOT EXISTS `sp_check_tax_compliance`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE order_id INT;
    DECLARE order_total DECIMAL(15,4);
    DECLARE eta_status VARCHAR(20);
    DECLARE compliance_score DECIMAL(5,2);

    DECLARE cur CURSOR FOR
        SELECT o.order_id, o.total, COALESCE(ei.status, 'not_sent') as eta_status
        FROM `cod_order` o
        LEFT JOIN `cod_eta_invoices` ei ON (o.order_id = ei.order_id)
        WHERE o.order_status_id > 0
        AND DATE(o.date_added) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND o.total > 100; -- Only check orders above threshold

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    check_loop: LOOP
        FETCH cur INTO order_id, order_total, eta_status;
        IF done THEN
            LEAVE check_loop;
        END IF;

        -- Calculate compliance score
        SET compliance_score = 0;

        IF eta_status = 'sent' OR eta_status = 'accepted' THEN
            SET compliance_score = 100;
        ELSEIF eta_status = 'pending' THEN
            SET compliance_score = 50;
        ELSE
            SET compliance_score = 0;
        END IF;

        -- Insert or update compliance record
        INSERT INTO `cod_tax_compliance` (`compliance_type`, `reference_id`, `compliance_status`, `compliance_score`, `checked_date`, `date_added`)
        VALUES ('eta_submission', order_id,
                CASE
                    WHEN compliance_score >= 80 THEN 'compliant'
                    WHEN compliance_score >= 50 THEN 'warning'
                    ELSE 'non_compliant'
                END,
                compliance_score, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            `compliance_status` = CASE
                WHEN compliance_score >= 80 THEN 'compliant'
                WHEN compliance_score >= 50 THEN 'warning'
                ELSE 'non_compliant'
            END,
            `compliance_score` = compliance_score,
            `checked_date` = NOW();

    END LOOP;

    CLOSE cur;
END$$

DELIMITER ;

-- Advanced Multi-Warehouse Inventory Management System
-- Professional inventory tracking with comprehensive features

-- Enhanced warehouse table
CREATE TABLE IF NOT EXISTS `cod_warehouse` (
  `warehouse_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `address` text,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `manager` varchar(100) DEFAULT NULL,
  `capacity` decimal(15,4) DEFAULT NULL COMMENT 'Storage capacity in cubic meters',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `timezone` varchar(50) DEFAULT NULL,
  `operating_hours` text COMMENT 'JSON operating hours',
  `warehouse_type` varchar(20) DEFAULT 'standard' COMMENT 'standard, cold_storage, hazmat, bonded',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`warehouse_id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_warehouse_type` (`warehouse_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Warehouse locations (zones, aisles, shelves, bins)
CREATE TABLE IF NOT EXISTS `cod_warehouse_location` (
  `location_id` int(11) NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `type` varchar(20) NOT NULL COMMENT 'zone, aisle, shelf, bin, receiving, shipping, quality',
  `level` int(11) NOT NULL DEFAULT 1 COMMENT '1=zone, 2=aisle, 3=shelf, 4=bin',
  `capacity` decimal(15,4) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL COMMENT 'Length x Width x Height',
  `temperature_min` decimal(5,2) DEFAULT NULL,
  `temperature_max` decimal(5,2) DEFAULT NULL,
  `humidity_min` decimal(5,2) DEFAULT NULL,
  `humidity_max` decimal(5,2) DEFAULT NULL,
  `restrictions` text COMMENT 'JSON restrictions (hazmat, weight, etc)',
  `barcode` varchar(100) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`location_id`),
  UNIQUE KEY `warehouse_code` (`warehouse_id`, `code`),
  KEY `idx_warehouse_id` (`warehouse_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_type` (`type`),
  KEY `idx_level` (`level`),
  KEY `idx_barcode` (`barcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced product stock tracking
CREATE TABLE IF NOT EXISTS `cod_product_stock` (
  `stock_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `reserved_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `available_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `allocated_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `damaged_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `reorder_level` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `reorder_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `max_stock_level` decimal(15,4) DEFAULT NULL,
  `min_stock_level` decimal(15,4) DEFAULT NULL,
  `average_cost` decimal(15,4) DEFAULT NULL,
  `last_cost` decimal(15,4) DEFAULT NULL,
  `last_movement_date` datetime DEFAULT NULL,
  `last_count_date` datetime DEFAULT NULL,
  `cycle_count_due` date DEFAULT NULL,
  `abc_classification` varchar(1) DEFAULT NULL COMMENT 'A, B, C classification',
  `velocity_classification` varchar(10) DEFAULT NULL COMMENT 'fast, medium, slow',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`stock_id`),
  UNIQUE KEY `product_warehouse` (`product_id`, `warehouse_id`),
  KEY `idx_warehouse_id` (`warehouse_id`),
  KEY `idx_location_id` (`location_id`),
  KEY `idx_reorder_level` (`reorder_level`),
  KEY `idx_abc_classification` (`abc_classification`),
  KEY `idx_cycle_count_due` (`cycle_count_due`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock movements tracking
CREATE TABLE IF NOT EXISTS `cod_stock_movement` (
  `movement_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `movement_type` varchar(50) NOT NULL COMMENT 'in, out, transfer_in, transfer_out, adjustment_in, adjustment_out, sale, purchase, return, damage, etc',
  `quantity` decimal(15,4) NOT NULL,
  `unit_cost` decimal(15,4) DEFAULT NULL,
  `total_cost` decimal(15,4) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'order, transfer, adjustment, purchase, etc',
  `reference_id` int(11) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL COMMENT 'External reference number',
  `batch_number` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `notes` text,
  `user_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`movement_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_warehouse_id` (`warehouse_id`),
  KEY `idx_location_id` (`location_id`),
  KEY `idx_movement_type` (`movement_type`),
  KEY `idx_reference` (`reference_type`, `reference_id`),
  KEY `idx_batch_number` (`batch_number`),
  KEY `idx_serial_number` (`serial_number`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock transfers between warehouses
CREATE TABLE IF NOT EXISTS `cod_stock_transfer` (
  `transfer_id` int(11) NOT NULL AUTO_INCREMENT,
  `transfer_number` varchar(50) NOT NULL,
  `from_warehouse_id` int(11) NOT NULL,
  `to_warehouse_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, in_transit, received, cancelled',
  `priority` varchar(10) DEFAULT 'normal' COMMENT 'low, normal, high, urgent',
  `transfer_type` varchar(20) DEFAULT 'standard' COMMENT 'standard, emergency, replenishment',
  `reason` varchar(255) DEFAULT NULL,
  `notes` text,
  `expected_date` date DEFAULT NULL,
  `shipped_date` datetime DEFAULT NULL,
  `received_date` datetime DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `carrier` varchar(100) DEFAULT NULL,
  `shipping_cost` decimal(15,4) DEFAULT NULL,
  `total_items` int(11) DEFAULT 0,
  `total_quantity` decimal(15,4) DEFAULT 0.0000,
  `total_value` decimal(15,4) DEFAULT 0.0000,
  `created_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `shipped_by` int(11) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`transfer_id`),
  UNIQUE KEY `transfer_number` (`transfer_number`),
  KEY `idx_from_warehouse` (`from_warehouse_id`),
  KEY `idx_to_warehouse` (`to_warehouse_id`),
  KEY `idx_status` (`status`),
  KEY `idx_expected_date` (`expected_date`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock transfer items
CREATE TABLE IF NOT EXISTS `cod_stock_transfer_item` (
  `transfer_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `transfer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `from_location_id` int(11) DEFAULT NULL,
  `to_location_id` int(11) DEFAULT NULL,
  `quantity_requested` decimal(15,4) NOT NULL,
  `quantity_shipped` decimal(15,4) DEFAULT 0.0000,
  `quantity_received` decimal(15,4) DEFAULT 0.0000,
  `quantity_damaged` decimal(15,4) DEFAULT 0.0000,
  `unit_cost` decimal(15,4) DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `notes` text,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`transfer_item_id`),
  KEY `idx_transfer_id` (`transfer_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_batch_number` (`batch_number`),
  KEY `idx_serial_number` (`serial_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock adjustments
CREATE TABLE IF NOT EXISTS `cod_stock_adjustment` (
  `adjustment_id` int(11) NOT NULL AUTO_INCREMENT,
  `adjustment_number` varchar(50) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `reason_id` int(11) NOT NULL,
  `adjustment_type` varchar(20) DEFAULT 'manual' COMMENT 'manual, cycle_count, physical_count, system',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, completed, cancelled',
  `total_items` int(11) DEFAULT 0,
  `total_adjustment_value` decimal(15,4) DEFAULT 0.0000,
  `notes` text,
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`adjustment_id`),
  UNIQUE KEY `adjustment_number` (`adjustment_number`),
  KEY `idx_warehouse_id` (`warehouse_id`),
  KEY `idx_reason_id` (`reason_id`),
  KEY `idx_status` (`status`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock adjustment items
CREATE TABLE IF NOT EXISTS `cod_stock_adjustment_item` (
  `adjustment_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `adjustment_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `current_quantity` decimal(15,4) NOT NULL,
  `adjusted_quantity` decimal(15,4) NOT NULL,
  `difference` decimal(15,4) NOT NULL,
  `unit_cost` decimal(15,4) DEFAULT NULL,
  `adjustment_value` decimal(15,4) DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `notes` text,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`adjustment_item_id`),
  KEY `idx_adjustment_id` (`adjustment_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_location_id` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock adjustment reasons
CREATE TABLE IF NOT EXISTS `cod_stock_adjustment_reason` (
  `reason_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `type` varchar(20) NOT NULL COMMENT 'increase, decrease, both',
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`reason_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product batch tracking
CREATE TABLE IF NOT EXISTS `cod_product_batch` (
  `batch_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `batch_number` varchar(100) NOT NULL,
  `quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `reserved_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `available_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(15,4) DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `supplier_batch` varchar(100) DEFAULT NULL,
  `notes` text,
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, expired, recalled, damaged',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`batch_id`),
  UNIQUE KEY `product_warehouse_batch` (`product_id`, `warehouse_id`, `batch_number`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_warehouse_id` (`warehouse_id`),
  KEY `idx_location_id` (`location_id`),
  KEY `idx_batch_number` (`batch_number`),
  KEY `idx_expiry_date` (`expiry_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product serial number tracking
CREATE TABLE IF NOT EXISTS `cod_product_serial` (
  `serial_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `serial_number` varchar(100) NOT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `unit_cost` decimal(15,4) DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `warranty_start` date DEFAULT NULL,
  `warranty_end` date DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL COMMENT 'If sold',
  `order_id` int(11) DEFAULT NULL COMMENT 'Sale order',
  `notes` text,
  `status` varchar(20) NOT NULL DEFAULT 'available' COMMENT 'available, reserved, sold, returned, damaged',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`serial_id`),
  UNIQUE KEY `serial_number` (`serial_number`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_warehouse_id` (`warehouse_id`),
  KEY `idx_location_id` (`location_id`),
  KEY `idx_batch_number` (`batch_number`),
  KEY `idx_status` (`status`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cycle counting
CREATE TABLE IF NOT EXISTS `cod_cycle_count` (
  `count_id` int(11) NOT NULL AUTO_INCREMENT,
  `count_number` varchar(50) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `count_type` varchar(20) NOT NULL DEFAULT 'cycle' COMMENT 'cycle, physical, spot, abc',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, in_progress, completed, cancelled',
  `scheduled_date` date NOT NULL,
  `started_date` datetime DEFAULT NULL,
  `completed_date` datetime DEFAULT NULL,
  `total_items` int(11) DEFAULT 0,
  `counted_items` int(11) DEFAULT 0,
  `variance_items` int(11) DEFAULT 0,
  `total_variance_value` decimal(15,4) DEFAULT 0.0000,
  `notes` text,
  `created_by` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`count_id`),
  UNIQUE KEY `count_number` (`count_number`),
  KEY `idx_warehouse_id` (`warehouse_id`),
  KEY `idx_location_id` (`location_id`),
  KEY `idx_count_type` (`count_type`),
  KEY `idx_status` (`status`),
  KEY `idx_scheduled_date` (`scheduled_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cycle count items
CREATE TABLE IF NOT EXISTS `cod_cycle_count_item` (
  `count_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `count_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `system_quantity` decimal(15,4) NOT NULL,
  `counted_quantity` decimal(15,4) DEFAULT NULL,
  `variance_quantity` decimal(15,4) DEFAULT NULL,
  `unit_cost` decimal(15,4) DEFAULT NULL,
  `variance_value` decimal(15,4) DEFAULT NULL,
  `notes` text,
  `counted_by` int(11) DEFAULT NULL,
  `counted_date` datetime DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, counted, variance, adjusted',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`count_item_id`),
  KEY `idx_count_id` (`count_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_location_id` (`location_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory reservations
CREATE TABLE IF NOT EXISTS `cod_inventory_reservation` (
  `reservation_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `reserved_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `allocated_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `reference_type` varchar(50) NOT NULL COMMENT 'order, transfer, production, etc',
  `reference_id` int(11) NOT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 0,
  `expiry_date` datetime DEFAULT NULL,
  `notes` text,
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, allocated, fulfilled, cancelled, expired',
  `user_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`reservation_id`),
  KEY `idx_product_warehouse` (`product_id`, `warehouse_id`),
  KEY `idx_location_id` (`location_id`),
  KEY `idx_reference` (`reference_type`, `reference_id`),
  KEY `idx_status` (`status`),
  KEY `idx_expiry_date` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory alerts
CREATE TABLE IF NOT EXISTS `cod_inventory_alert` (
  `alert_id` int(11) NOT NULL AUTO_INCREMENT,
  `alert_type` varchar(50) NOT NULL COMMENT 'low_stock, out_of_stock, expiry, overstock, negative_stock',
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `current_quantity` decimal(15,4) NOT NULL,
  `threshold_quantity` decimal(15,4) DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `alert_level` varchar(20) NOT NULL DEFAULT 'warning' COMMENT 'info, warning, error, critical',
  `message` text NOT NULL,
  `is_acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_date` datetime DEFAULT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_date` datetime DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`alert_id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_product_warehouse` (`product_id`, `warehouse_id`),
  KEY `idx_alert_level` (`alert_level`),
  KEY `idx_acknowledged` (`is_acknowledged`),
  KEY `idx_resolved` (`is_resolved`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default stock adjustment reasons
INSERT IGNORE INTO `cod_stock_adjustment_reason` (`name`, `description`, `type`, `requires_approval`, `sort_order`, `date_added`) VALUES
('Physical Count Variance', 'Adjustment based on physical count variance', 'both', 1, 1, NOW()),
('Damaged Goods', 'Stock damaged and needs to be written off', 'decrease', 1, 2, NOW()),
('Expired Products', 'Products that have expired', 'decrease', 0, 3, NOW()),
('Found Stock', 'Stock found during physical count', 'increase', 0, 4, NOW()),
('Theft/Loss', 'Stock lost due to theft or other reasons', 'decrease', 1, 5, NOW()),
('System Error Correction', 'Correction of system errors', 'both', 1, 6, NOW()),
('Supplier Return', 'Return to supplier', 'decrease', 0, 7, NOW()),
('Customer Return', 'Return from customer', 'increase', 0, 8, NOW()),
('Production Consumption', 'Stock consumed in production', 'decrease', 0, 9, NOW()),
('Production Output', 'Stock produced from manufacturing', 'increase', 0, 10, NOW());

-- Insert default warehouses
INSERT IGNORE INTO `cod_warehouse` (`warehouse_id`, `name`, `code`, `address`, `warehouse_type`, `status`, `sort_order`, `date_added`, `date_modified`) VALUES
(1, 'Main Warehouse', 'MAIN', 'Main warehouse location', 'standard', 1, 1, NOW(), NOW()),
(2, 'Secondary Warehouse', 'SEC', 'Secondary warehouse location', 'standard', 1, 2, NOW(), NOW()),
(3, 'Cold Storage', 'COLD', 'Cold storage facility', 'cold_storage', 1, 3, NOW(), NOW());

-- Advanced CRM & Sales Pipeline Management System
-- Professional CRM with comprehensive lead management and sales pipeline

-- CRM leads table
CREATE TABLE IF NOT EXISTS `cod_crm_lead` (
  `lead_id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `source` varchar(50) NOT NULL COMMENT 'website, referral, social_media, email, phone, event, advertisement',
  `source_details` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'new' COMMENT 'new, contacted, qualified, proposal, negotiation, converted, lost',
  `stage_id` int(11) NOT NULL DEFAULT 1,
  `assigned_to_user_id` int(11) NOT NULL,
  `lead_value` decimal(15,4) DEFAULT 0.0000,
  `probability` int(11) DEFAULT 0 COMMENT 'Percentage 0-100',
  `expected_close_date` date DEFAULT NULL,
  `actual_close_date` date DEFAULT NULL,
  `lead_score` int(11) DEFAULT 0,
  `temperature` varchar(10) DEFAULT 'cold' COMMENT 'cold, warm, hot',
  `industry` varchar(100) DEFAULT NULL,
  `company_size` varchar(20) DEFAULT NULL COMMENT 'startup, small, medium, large, enterprise',
  `annual_revenue` decimal(15,4) DEFAULT NULL,
  `employees_count` int(11) DEFAULT NULL,
  `decision_maker` tinyint(1) DEFAULT 0,
  `budget_confirmed` tinyint(1) DEFAULT 0,
  `timeline_confirmed` tinyint(1) DEFAULT 0,
  `authority_confirmed` tinyint(1) DEFAULT 0,
  `need_confirmed` tinyint(1) DEFAULT 0,
  `notes` text,
  `last_contact_date` datetime DEFAULT NULL,
  `next_contact_date` datetime DEFAULT NULL,
  `conversion_date` datetime DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL COMMENT 'If converted to customer',
  `lost_reason` varchar(255) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`lead_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_stage_id` (`stage_id`),
  KEY `idx_assigned_to` (`assigned_to_user_id`),
  KEY `idx_source` (`source`),
  KEY `idx_lead_score` (`lead_score`),
  KEY `idx_temperature` (`temperature`),
  KEY `idx_expected_close_date` (`expected_close_date`),
  KEY `idx_date_added` (`date_added`),
  KEY `idx_customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CRM pipeline stages
CREATE TABLE IF NOT EXISTS `cod_crm_pipeline_stage` (
  `stage_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `color` varchar(7) DEFAULT '#007bff' COMMENT 'Hex color code',
  `probability` int(11) DEFAULT 0 COMMENT 'Default probability for this stage',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_closed_won` tinyint(1) DEFAULT 0,
  `is_closed_lost` tinyint(1) DEFAULT 0,
  `auto_actions` text COMMENT 'JSON auto actions for this stage',
  `required_fields` text COMMENT 'JSON required fields for this stage',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`stage_id`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CRM lead activities
CREATE TABLE IF NOT EXISTS `cod_crm_lead_activity` (
  `activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'call, email, meeting, task, note, stage_change, lead_created, etc',
  `subject` varchar(255) NOT NULL,
  `description` text,
  `date_due` datetime DEFAULT NULL,
  `date_completed` datetime DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'Duration in minutes',
  `priority` varchar(10) DEFAULT 'normal' COMMENT 'low, normal, high, urgent',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, completed, cancelled, overdue',
  `outcome` varchar(50) DEFAULT NULL COMMENT 'successful, unsuccessful, no_answer, busy, etc',
  `follow_up_required` tinyint(1) DEFAULT 0,
  `follow_up_date` datetime DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `attendees` text COMMENT 'JSON list of attendees',
  `attachments` text COMMENT 'JSON list of attachments',
  `user_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`activity_id`),
  KEY `idx_lead_id` (`lead_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_date_due` (`date_due`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CRM lead communications
CREATE TABLE IF NOT EXISTS `cod_crm_lead_communication` (
  `communication_id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL COMMENT 'email, sms, call, whatsapp, social',
  `direction` varchar(10) NOT NULL COMMENT 'inbound, outbound',
  `subject` varchar(255) DEFAULT NULL,
  `content` text,
  `from_address` varchar(255) DEFAULT NULL,
  `to_address` varchar(255) DEFAULT NULL,
  `cc_address` text DEFAULT NULL,
  `bcc_address` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'sent' COMMENT 'sent, delivered, read, replied, failed',
  `external_id` varchar(100) DEFAULT NULL COMMENT 'External system ID',
  `attachments` text COMMENT 'JSON list of attachments',
  `metadata` text COMMENT 'JSON additional metadata',
  `user_id` int(11) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`communication_id`),
  KEY `idx_lead_id` (`lead_id`),
  KEY `idx_type` (`type`),
  KEY `idx_direction` (`direction`),
  KEY `idx_status` (`status`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CRM lead tags
CREATE TABLE IF NOT EXISTS `cod_crm_lead_tag` (
  `lead_tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`lead_tag_id`),
  UNIQUE KEY `lead_tag` (`lead_id`, `tag_id`),
  KEY `idx_lead_id` (`lead_id`),
  KEY `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CRM tags
CREATE TABLE IF NOT EXISTS `cod_crm_tag` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `color` varchar(7) DEFAULT '#6c757d',
  `description` text,
  `category` varchar(50) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CRM lead custom fields
CREATE TABLE IF NOT EXISTS `cod_crm_lead_custom_field` (
  `lead_custom_field_id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `custom_field_id` int(11) NOT NULL,
  `value` text,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`lead_custom_field_id`),
  UNIQUE KEY `lead_field` (`lead_id`, `custom_field_id`),
  KEY `idx_lead_id` (`lead_id`),
  KEY `idx_custom_field_id` (`custom_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CRM custom fields definition
CREATE TABLE IF NOT EXISTS `cod_crm_custom_field` (
  `custom_field_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `label` varchar(100) NOT NULL,
  `type` varchar(20) NOT NULL COMMENT 'text, textarea, select, multiselect, checkbox, radio, date, datetime, number, email, url',
  `options` text COMMENT 'JSON options for select/radio fields',
  `default_value` text,
  `is_required` tinyint(1) DEFAULT 0,
  `is_searchable` tinyint(1) DEFAULT 1,
  `validation_rules` text COMMENT 'JSON validation rules',
  `help_text` text,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`custom_field_id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CRM lead scoring rules
CREATE TABLE IF NOT EXISTS `cod_crm_lead_scoring_rule` (
  `rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `field_name` varchar(100) NOT NULL,
  `operator` varchar(20) NOT NULL COMMENT 'equals, not_equals, contains, not_contains, greater_than, less_than, between',
  `value` text NOT NULL,
  `score_points` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`rule_id`),
  KEY `idx_field_name` (`field_name`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CRM lead scoring history
CREATE TABLE IF NOT EXISTS `cod_crm_lead_scoring_history` (
  `scoring_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `rule_id` int(11) NOT NULL,
  `points_awarded` int(11) NOT NULL,
  `previous_score` int(11) NOT NULL,
  `new_score` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`scoring_history_id`),
  KEY `idx_lead_id` (`lead_id`),
  KEY `idx_rule_id` (`rule_id`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CRM email templates
CREATE TABLE IF NOT EXISTS `cod_crm_email_template` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `template_type` varchar(50) DEFAULT 'general' COMMENT 'general, welcome, follow_up, proposal, etc',
  `variables` text COMMENT 'JSON available variables',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`template_id`),
  KEY `idx_template_type` (`template_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CRM automation workflows
CREATE TABLE IF NOT EXISTS `cod_crm_workflow` (
  `workflow_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `trigger_type` varchar(50) NOT NULL COMMENT 'lead_created, stage_changed, field_updated, time_based, etc',
  `trigger_conditions` text COMMENT 'JSON trigger conditions',
  `actions` text NOT NULL COMMENT 'JSON workflow actions',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `execution_count` int(11) DEFAULT 0,
  `last_execution` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`workflow_id`),
  KEY `idx_trigger_type` (`trigger_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CRM workflow executions
CREATE TABLE IF NOT EXISTS `cod_crm_workflow_execution` (
  `execution_id` int(11) NOT NULL AUTO_INCREMENT,
  `workflow_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, running, completed, failed',
  `actions_executed` text COMMENT 'JSON executed actions',
  `error_message` text,
  `execution_time` decimal(10,4) DEFAULT NULL COMMENT 'Execution time in seconds',
  `date_started` datetime NOT NULL,
  `date_completed` datetime DEFAULT NULL,
  PRIMARY KEY (`execution_id`),
  KEY `idx_workflow_id` (`workflow_id`),
  KEY `idx_lead_id` (`lead_id`),
  KEY `idx_status` (`status`),
  KEY `idx_date_started` (`date_started`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default CRM pipeline stages
INSERT IGNORE INTO `cod_crm_pipeline_stage` (`stage_id`, `name`, `description`, `color`, `probability`, `sort_order`, `is_closed_won`, `is_closed_lost`, `status`, `date_added`, `date_modified`) VALUES
(1, 'New Lead', 'Newly generated leads', '#6c757d', 10, 1, 0, 0, 1, NOW(), NOW()),
(2, 'Contacted', 'Initial contact made', '#17a2b8', 20, 2, 0, 0, 1, NOW(), NOW()),
(3, 'Qualified', 'Lead has been qualified', '#ffc107', 40, 3, 0, 0, 1, NOW(), NOW()),
(4, 'Proposal', 'Proposal sent to lead', '#fd7e14', 60, 4, 0, 0, 1, NOW(), NOW()),
(5, 'Negotiation', 'In negotiation phase', '#e83e8c', 80, 5, 0, 0, 1, NOW(), NOW()),
(6, 'Closed Won', 'Successfully converted', '#28a745', 100, 6, 1, 0, 1, NOW(), NOW()),
(7, 'Closed Lost', 'Lost opportunity', '#dc3545', 0, 7, 0, 1, 1, NOW(), NOW());

-- Insert default CRM tags
INSERT IGNORE INTO `cod_crm_tag` (`name`, `color`, `description`, `category`, `status`, `sort_order`, `date_added`) VALUES
('Hot Lead', '#dc3545', 'High priority lead', 'priority', 1, 1, NOW()),
('Warm Lead', '#ffc107', 'Medium priority lead', 'priority', 1, 2, NOW()),
('Cold Lead', '#6c757d', 'Low priority lead', 'priority', 1, 3, NOW()),
('Enterprise', '#007bff', 'Enterprise customer', 'size', 1, 4, NOW()),
('SMB', '#28a745', 'Small/Medium business', 'size', 1, 5, NOW()),
('Startup', '#17a2b8', 'Startup company', 'size', 1, 6, NOW()),
('Decision Maker', '#6f42c1', 'Has decision making authority', 'role', 1, 7, NOW()),
('Influencer', '#fd7e14', 'Influences decision making', 'role', 1, 8, NOW()),
('Budget Confirmed', '#20c997', 'Budget has been confirmed', 'qualification', 1, 9, NOW()),
('Timeline Confirmed', '#6610f2', 'Timeline has been confirmed', 'qualification', 1, 10, NOW());

-- Insert default lead scoring rules
INSERT IGNORE INTO `cod_crm_lead_scoring_rule` (`name`, `description`, `field_name`, `operator`, `value`, `score_points`, `is_active`, `sort_order`, `date_added`, `date_modified`) VALUES
('Company Size - Enterprise', 'Enterprise companies get higher score', 'company_size', 'equals', 'enterprise', 25, 1, 1, NOW(), NOW()),
('Company Size - Large', 'Large companies get good score', 'company_size', 'equals', 'large', 20, 1, 2, NOW(), NOW()),
('Company Size - Medium', 'Medium companies get moderate score', 'company_size', 'equals', 'medium', 15, 1, 3, NOW(), NOW()),
('Decision Maker', 'Decision makers get higher score', 'decision_maker', 'equals', '1', 20, 1, 4, NOW(), NOW()),
('Budget Confirmed', 'Confirmed budget increases score', 'budget_confirmed', 'equals', '1', 15, 1, 5, NOW(), NOW()),
('Timeline Confirmed', 'Confirmed timeline increases score', 'timeline_confirmed', 'equals', '1', 10, 1, 6, NOW(), NOW()),
('Authority Confirmed', 'Confirmed authority increases score', 'authority_confirmed', 'equals', '1', 15, 1, 7, NOW(), NOW()),
('Need Confirmed', 'Confirmed need increases score', 'need_confirmed', 'equals', '1', 10, 1, 8, NOW(), NOW()),
('High Lead Value', 'High value leads get bonus points', 'lead_value', 'greater_than', '50000', 30, 1, 9, NOW(), NOW()),
('Medium Lead Value', 'Medium value leads get moderate points', 'lead_value', 'between', '10000,50000', 15, 1, 10, NOW(), NOW()),
('Website Source', 'Website leads get points', 'source', 'equals', 'website', 5, 1, 11, NOW(), NOW()),
('Referral Source', 'Referral leads get higher points', 'source', 'equals', 'referral', 15, 1, 12, NOW(), NOW()),
('Event Source', 'Event leads get good points', 'source', 'equals', 'event', 10, 1, 13, NOW(), NOW());

-- Insert default email templates
INSERT IGNORE INTO `cod_crm_email_template` (`name`, `subject`, `content`, `template_type`, `variables`, `is_active`, `created_by`, `date_added`, `date_modified`) VALUES
('Welcome Email', 'Welcome to AYM ERP - {{lead_name}}',
'<p>Dear {{lead_name}},</p>
<p>Thank you for your interest in AYM ERP. We are excited to help you streamline your business operations.</p>
<p>Our team will be in touch with you shortly to discuss your requirements.</p>
<p>Best regards,<br>AYM ERP Team</p>',
'welcome',
'["lead_name", "company", "email", "phone"]',
1, 1, NOW(), NOW()),

('Follow Up Email', 'Following up on your AYM ERP inquiry - {{lead_name}}',
'<p>Dear {{lead_name}},</p>
<p>I wanted to follow up on your recent inquiry about AYM ERP for {{company}}.</p>
<p>Do you have time for a brief call this week to discuss your requirements?</p>
<p>Best regards,<br>{{user_name}}</p>',
'follow_up',
'["lead_name", "company", "user_name", "phone"]',
1, 1, NOW(), NOW()),

('Proposal Email', 'AYM ERP Proposal for {{company}}',
'<p>Dear {{lead_name}},</p>
<p>Thank you for taking the time to discuss your requirements with us.</p>
<p>Please find attached our proposal for implementing AYM ERP at {{company}}.</p>
<p>I am available to discuss any questions you may have.</p>
<p>Best regards,<br>{{user_name}}</p>',
'proposal',
'["lead_name", "company", "user_name", "lead_value"]',
1, 1, NOW(), NOW());

-- Advanced Human Resources Management (HRM) System
-- Professional HRM with comprehensive employee lifecycle management

-- HR departments
CREATE TABLE IF NOT EXISTS `cod_hr_department` (
  `department_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `manager_id` int(11) DEFAULT NULL,
  `parent_department_id` int(11) DEFAULT NULL,
  `budget` decimal(15,4) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `cost_center` varchar(50) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`department_id`),
  KEY `idx_manager_id` (`manager_id`),
  KEY `idx_parent_department_id` (`parent_department_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- HR positions/job titles
CREATE TABLE IF NOT EXISTS `cod_hr_position` (
  `position_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text,
  `department_id` int(11) NOT NULL,
  `level` varchar(20) DEFAULT NULL COMMENT 'entry, junior, senior, lead, manager, director, executive',
  `min_salary` decimal(15,4) DEFAULT NULL,
  `max_salary` decimal(15,4) DEFAULT NULL,
  `required_skills` text COMMENT 'JSON required skills',
  `responsibilities` text,
  `qualifications` text,
  `reports_to_position_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`position_id`),
  KEY `idx_department_id` (`department_id`),
  KEY `idx_reports_to` (`reports_to_position_id`),
  KEY `idx_level` (`level`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced employee profiles
CREATE TABLE IF NOT EXISTS `cod_employee_profile` (
  `employee_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `employment_type` varchar(20) NOT NULL DEFAULT 'full_time' COMMENT 'full_time, part_time, contract, intern, consultant',
  `employment_status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, inactive, terminated, resigned, retired',
  `hire_date` date NOT NULL,
  `probation_end_date` date DEFAULT NULL,
  `termination_date` date DEFAULT NULL,
  `termination_reason` varchar(255) DEFAULT NULL,
  `work_location` varchar(100) DEFAULT NULL,
  `work_schedule` varchar(50) DEFAULT 'standard' COMMENT 'standard, flexible, remote, hybrid',
  `salary` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `salary_currency` varchar(3) DEFAULT 'EGP',
  `pay_frequency` varchar(20) DEFAULT 'monthly' COMMENT 'weekly, bi_weekly, monthly, quarterly, annually',
  `overtime_eligible` tinyint(1) DEFAULT 1,
  `benefits_eligible` tinyint(1) DEFAULT 1,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `social_security_number` varchar(50) DEFAULT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `passport_expiry` date DEFAULT NULL,
  `visa_status` varchar(50) DEFAULT NULL,
  `visa_expiry` date DEFAULT NULL,
  `skills` text COMMENT 'JSON skills and proficiency levels',
  `certifications` text COMMENT 'JSON certifications',
  `education` text COMMENT 'JSON education history',
  `languages` text COMMENT 'JSON languages and proficiency',
  `notes` text,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`employee_id`),
  UNIQUE KEY `employee_number` (`employee_number`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_department_id` (`department_id`),
  KEY `idx_position_id` (`position_id`),
  KEY `idx_manager_id` (`manager_id`),
  KEY `idx_employment_status` (`employment_status`),
  KEY `idx_hire_date` (`hire_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employee attendance tracking
CREATE TABLE IF NOT EXISTS `cod_employee_attendance` (
  `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `break_time` int(11) DEFAULT 0 COMMENT 'Break time in minutes',
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'present' COMMENT 'present, absent, late, half_day, sick, vacation',
  `location` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `notes` text,
  `approved_by` int(11) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `employee_date` (`employee_id`, `attendance_date`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_attendance_date` (`attendance_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Leave types
CREATE TABLE IF NOT EXISTS `cod_hr_leave_type` (
  `leave_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `days_per_year` int(11) DEFAULT NULL,
  `max_consecutive_days` int(11) DEFAULT NULL,
  `min_notice_days` int(11) DEFAULT 0,
  `requires_approval` tinyint(1) DEFAULT 1,
  `is_paid` tinyint(1) DEFAULT 1,
  `carry_forward` tinyint(1) DEFAULT 0,
  `max_carry_forward_days` int(11) DEFAULT 0,
  `gender_restriction` varchar(10) DEFAULT NULL COMMENT 'male, female, null for both',
  `color` varchar(7) DEFAULT '#007bff',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`leave_type_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employee leave requests
CREATE TABLE IF NOT EXISTS `cod_employee_leave_request` (
  `leave_request_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` int(11) NOT NULL,
  `reason` text,
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, rejected, cancelled',
  `approved_by` int(11) DEFAULT NULL,
  `approval_comments` text,
  `date_requested` datetime NOT NULL,
  `date_processed` datetime DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`leave_request_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_leave_type_id` (`leave_type_id`),
  KEY `idx_status` (`status`),
  KEY `idx_start_date` (`start_date`),
  KEY `idx_date_requested` (`date_requested`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employee performance reviews
CREATE TABLE IF NOT EXISTS `cod_employee_performance_review` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `review_period_start` date NOT NULL,
  `review_period_end` date NOT NULL,
  `review_type` varchar(20) DEFAULT 'annual' COMMENT 'annual, quarterly, probation, project',
  `overall_rating` decimal(3,2) NOT NULL COMMENT 'Rating out of 5.00',
  `goals_achievement` decimal(3,2) DEFAULT NULL,
  `communication_skills` decimal(3,2) DEFAULT NULL,
  `teamwork` decimal(3,2) DEFAULT NULL,
  `leadership` decimal(3,2) DEFAULT NULL,
  `technical_skills` decimal(3,2) DEFAULT NULL,
  `punctuality` decimal(3,2) DEFAULT NULL,
  `initiative` decimal(3,2) DEFAULT NULL,
  `problem_solving` decimal(3,2) DEFAULT NULL,
  `strengths` text,
  `areas_for_improvement` text,
  `goals_next_period` text,
  `training_recommendations` text,
  `salary_recommendation` varchar(50) DEFAULT NULL COMMENT 'increase, maintain, decrease',
  `promotion_recommendation` tinyint(1) DEFAULT 0,
  `comments` text,
  `employee_comments` text,
  `status` varchar(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, completed, acknowledged',
  `review_date` datetime NOT NULL,
  `employee_acknowledged_date` datetime DEFAULT NULL,
  PRIMARY KEY (`review_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_reviewer_id` (`reviewer_id`),
  KEY `idx_review_period` (`review_period_start`, `review_period_end`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employee salary history
CREATE TABLE IF NOT EXISTS `cod_employee_salary_history` (
  `salary_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `previous_salary` decimal(15,4) NOT NULL,
  `new_salary` decimal(15,4) NOT NULL,
  `change_type` varchar(20) NOT NULL COMMENT 'increase, decrease, promotion, adjustment, bonus',
  `change_percentage` decimal(5,2) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `effective_date` date NOT NULL,
  `approved_by` int(11) NOT NULL,
  `notes` text,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`salary_history_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_effective_date` (`effective_date`),
  KEY `idx_change_type` (`change_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- HR training programs
CREATE TABLE IF NOT EXISTS `cod_hr_training` (
  `training_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `category` varchar(100) DEFAULT NULL,
  `trainer` varchar(255) DEFAULT NULL,
  `duration_hours` int(11) DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `cost_per_participant` decimal(15,4) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `training_type` varchar(20) DEFAULT 'classroom' COMMENT 'classroom, online, workshop, seminar, certification',
  `prerequisites` text,
  `learning_objectives` text,
  `materials` text COMMENT 'JSON training materials',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, inactive, completed, cancelled',
  `created_by` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`training_id`),
  KEY `idx_category` (`category`),
  KEY `idx_training_type` (`training_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employee training records
CREATE TABLE IF NOT EXISTS `cod_employee_training_record` (
  `training_record_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `enrollment_date` date NOT NULL,
  `start_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'enrolled' COMMENT 'enrolled, in_progress, completed, cancelled, failed',
  `score` decimal(5,2) DEFAULT NULL COMMENT 'Score out of 100',
  `certificate_issued` tinyint(1) DEFAULT 0,
  `certificate_number` varchar(100) DEFAULT NULL,
  `feedback` text,
  `cost` decimal(15,4) DEFAULT NULL,
  `notes` text,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`training_record_id`),
  UNIQUE KEY `employee_training` (`employee_id`, `training_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_training_id` (`training_id`),
  KEY `idx_status` (`status`),
  KEY `idx_completion_date` (`completion_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employee documents
CREATE TABLE IF NOT EXISTS `cod_employee_documents` (
  `document_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL COMMENT 'contract, id_copy, passport, visa, certificate, resume, etc',
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `description` text,
  `expiry_date` date DEFAULT NULL,
  `is_confidential` tinyint(1) DEFAULT 0,
  `uploaded_by` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`document_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_document_type` (`document_type`),
  KEY `idx_expiry_date` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employee benefits
CREATE TABLE IF NOT EXISTS `cod_hr_benefit` (
  `benefit_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `benefit_type` varchar(50) NOT NULL COMMENT 'health, dental, vision, retirement, life_insurance, disability, vacation, etc',
  `provider` varchar(255) DEFAULT NULL,
  `cost_employee` decimal(15,4) DEFAULT NULL,
  `cost_employer` decimal(15,4) DEFAULT NULL,
  `eligibility_criteria` text,
  `enrollment_period` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`benefit_id`),
  KEY `idx_benefit_type` (`benefit_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employee benefit enrollments
CREATE TABLE IF NOT EXISTS `cod_employee_benefit_enrollment` (
  `enrollment_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `benefit_id` int(11) NOT NULL,
  `enrollment_date` date NOT NULL,
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `employee_contribution` decimal(15,4) DEFAULT NULL,
  `employer_contribution` decimal(15,4) DEFAULT NULL,
  `coverage_level` varchar(50) DEFAULT NULL COMMENT 'employee, employee_spouse, family',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, inactive, terminated',
  `notes` text,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`enrollment_id`),
  UNIQUE KEY `employee_benefit` (`employee_id`, `benefit_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_benefit_id` (`benefit_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payroll records
CREATE TABLE IF NOT EXISTS `cod_employee_payroll` (
  `payroll_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `pay_date` date NOT NULL,
  `gross_salary` decimal(15,4) NOT NULL,
  `basic_salary` decimal(15,4) NOT NULL,
  `allowances` decimal(15,4) DEFAULT 0.0000,
  `overtime_pay` decimal(15,4) DEFAULT 0.0000,
  `bonus` decimal(15,4) DEFAULT 0.0000,
  `commission` decimal(15,4) DEFAULT 0.0000,
  `gross_total` decimal(15,4) NOT NULL,
  `tax_deduction` decimal(15,4) DEFAULT 0.0000,
  `social_security_deduction` decimal(15,4) DEFAULT 0.0000,
  `insurance_deduction` decimal(15,4) DEFAULT 0.0000,
  `other_deductions` decimal(15,4) DEFAULT 0.0000,
  `total_deductions` decimal(15,4) NOT NULL,
  `net_pay` decimal(15,4) NOT NULL,
  `currency` varchar(3) DEFAULT 'EGP',
  `status` varchar(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, approved, paid, cancelled',
  `approved_by` int(11) DEFAULT NULL,
  `paid_date` datetime DEFAULT NULL,
  `payment_method` varchar(20) DEFAULT 'bank_transfer' COMMENT 'bank_transfer, cash, check',
  `notes` text,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`payroll_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_pay_period` (`pay_period_start`, `pay_period_end`),
  KEY `idx_pay_date` (`pay_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default HR departments
INSERT IGNORE INTO `cod_hr_department` (`name`, `description`, `status`, `sort_order`, `date_added`, `date_modified`) VALUES
('Human Resources', 'Human Resources Department', 1, 1, NOW(), NOW()),
('Information Technology', 'IT Department', 1, 2, NOW(), NOW()),
('Finance & Accounting', 'Finance and Accounting Department', 1, 3, NOW(), NOW()),
('Sales & Marketing', 'Sales and Marketing Department', 1, 4, NOW(), NOW()),
('Operations', 'Operations Department', 1, 5, NOW(), NOW()),
('Customer Service', 'Customer Service Department', 1, 6, NOW(), NOW()),
('Research & Development', 'R&D Department', 1, 7, NOW(), NOW()),
('Legal & Compliance', 'Legal and Compliance Department', 1, 8, NOW(), NOW());

-- Insert default HR positions
INSERT IGNORE INTO `cod_hr_position` (`title`, `description`, `department_id`, `level`, `min_salary`, `max_salary`, `is_active`, `date_added`, `date_modified`) VALUES
('HR Manager', 'Human Resources Manager', 1, 'manager', 15000.00, 25000.00, 1, NOW(), NOW()),
('HR Specialist', 'Human Resources Specialist', 1, 'senior', 8000.00, 15000.00, 1, NOW(), NOW()),
('IT Manager', 'Information Technology Manager', 2, 'manager', 20000.00, 35000.00, 1, NOW(), NOW()),
('Software Developer', 'Software Developer', 2, 'senior', 10000.00, 20000.00, 1, NOW(), NOW()),
('System Administrator', 'System Administrator', 2, 'senior', 8000.00, 15000.00, 1, NOW(), NOW()),
('Finance Manager', 'Finance Manager', 3, 'manager', 18000.00, 30000.00, 1, NOW(), NOW()),
('Accountant', 'Accountant', 3, 'senior', 6000.00, 12000.00, 1, NOW(), NOW()),
('Sales Manager', 'Sales Manager', 4, 'manager', 15000.00, 25000.00, 1, NOW(), NOW()),
('Sales Representative', 'Sales Representative', 4, 'junior', 4000.00, 8000.00, 1, NOW(), NOW()),
('Marketing Specialist', 'Marketing Specialist', 4, 'senior', 7000.00, 14000.00, 1, NOW(), NOW());

-- Insert default leave types
INSERT IGNORE INTO `cod_hr_leave_type` (`name`, `description`, `days_per_year`, `max_consecutive_days`, `min_notice_days`, `requires_approval`, `is_paid`, `carry_forward`, `color`, `status`, `sort_order`, `date_added`) VALUES
('Annual Leave', 'Annual vacation leave', 21, 14, 7, 1, 1, 1, '#28a745', 1, 1, NOW()),
('Sick Leave', 'Medical sick leave', 15, 7, 0, 1, 1, 0, '#dc3545', 1, 2, NOW()),
('Emergency Leave', 'Emergency personal leave', 5, 3, 0, 1, 1, 0, '#ffc107', 1, 3, NOW()),
('Maternity Leave', 'Maternity leave for mothers', 90, 90, 30, 1, 1, 0, '#e83e8c', 1, 4, NOW()),
('Paternity Leave', 'Paternity leave for fathers', 7, 7, 7, 1, 1, 0, '#17a2b8', 1, 5, NOW()),
('Bereavement Leave', 'Leave for family bereavement', 3, 3, 0, 1, 1, 0, '#6c757d', 1, 6, NOW()),
('Study Leave', 'Educational study leave', 10, 5, 14, 1, 0, 0, '#6f42c1', 1, 7, NOW()),
('Unpaid Leave', 'Unpaid personal leave', NULL, 30, 14, 1, 0, 0, '#fd7e14', 1, 8, NOW());

-- Insert default benefits
INSERT IGNORE INTO `cod_hr_benefit` (`name`, `description`, `benefit_type`, `cost_employee`, `cost_employer`, `status`, `date_added`) VALUES
('Health Insurance', 'Comprehensive health insurance coverage', 'health', 200.00, 800.00, 1, NOW()),
('Dental Insurance', 'Dental care insurance coverage', 'dental', 50.00, 150.00, 1, NOW()),
('Life Insurance', 'Life insurance coverage', 'life_insurance', 0.00, 100.00, 1, NOW()),
('Retirement Plan', '401k retirement savings plan', 'retirement', 500.00, 500.00, 1, NOW()),
('Transportation Allowance', 'Monthly transportation allowance', 'allowance', 0.00, 300.00, 1, NOW()),
('Meal Allowance', 'Daily meal allowance', 'allowance', 0.00, 200.00, 1, NOW()),
('Mobile Allowance', 'Mobile phone allowance', 'allowance', 0.00, 150.00, 1, NOW()),
('Training Budget', 'Annual training and development budget', 'training', 0.00, 2000.00, 1, NOW());

-- Insert default training programs
INSERT IGNORE INTO `cod_hr_training` (`title`, `description`, `category`, `duration_hours`, `training_type`, `status`, `created_by`, `date_added`, `date_modified`) VALUES
('New Employee Orientation', 'Comprehensive orientation program for new hires', 'Onboarding', 8, 'classroom', 'active', 1, NOW(), NOW()),
('Leadership Development', 'Leadership skills development program', 'Leadership', 24, 'workshop', 'active', 1, NOW(), NOW()),
('Communication Skills', 'Effective communication skills training', 'Soft Skills', 16, 'workshop', 'active', 1, NOW(), NOW()),
('Time Management', 'Time management and productivity training', 'Productivity', 8, 'online', 'active', 1, NOW(), NOW()),
('Customer Service Excellence', 'Customer service skills and best practices', 'Customer Service', 12, 'classroom', 'active', 1, NOW(), NOW()),
('Safety Training', 'Workplace safety and emergency procedures', 'Safety', 4, 'classroom', 'active', 1, NOW(), NOW()),
('Technical Skills Update', 'Latest technical skills and tools training', 'Technical', 40, 'online', 'active', 1, NOW(), NOW()),
('Performance Management', 'Performance management and evaluation training', 'Management', 16, 'workshop', 'active', 1, NOW(), NOW());

-- Advanced Project Management System
-- Professional project management with comprehensive project lifecycle management

-- Projects table
CREATE TABLE IF NOT EXISTS `cod_project` (
  `project_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `client_id` int(11) DEFAULT NULL,
  `project_manager_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `original_end_date` date DEFAULT NULL COMMENT 'Original planned end date',
  `budget` decimal(15,4) DEFAULT 0.0000,
  `currency` varchar(3) DEFAULT 'EGP',
  `actual_cost` decimal(15,4) DEFAULT 0.0000,
  `priority` varchar(10) DEFAULT 'medium' COMMENT 'low, medium, high, critical',
  `status` varchar(20) NOT NULL DEFAULT 'planning' COMMENT 'planning, in_progress, on_hold, completed, cancelled',
  `progress` decimal(5,2) DEFAULT 0.00 COMMENT 'Percentage 0-100',
  `billing_type` varchar(20) DEFAULT 'fixed' COMMENT 'fixed, hourly, milestone',
  `hourly_rate` decimal(10,2) DEFAULT 0.00,
  `is_billable` tinyint(1) DEFAULT 1,
  `is_public` tinyint(1) DEFAULT 0,
  `estimated_hours` int(11) DEFAULT NULL,
  `actual_hours` decimal(10,2) DEFAULT 0.00,
  `completion_date` datetime DEFAULT NULL,
  `notes` text,
  `created_by` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`project_id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_project_manager_id` (`project_manager_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_start_date` (`start_date`),
  KEY `idx_end_date` (`end_date`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project tasks
CREATE TABLE IF NOT EXISTS `cod_project_task` (
  `task_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `parent_task_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `assigned_to` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `estimated_hours` decimal(10,2) DEFAULT NULL,
  `actual_hours` decimal(10,2) DEFAULT 0.00,
  `priority` varchar(10) DEFAULT 'medium' COMMENT 'low, medium, high, critical',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, in_progress, completed, cancelled, on_hold',
  `progress` decimal(5,2) DEFAULT 0.00,
  `sort_order` int(11) DEFAULT 0,
  `is_milestone` tinyint(1) DEFAULT 0,
  `billable_hours` decimal(10,2) DEFAULT 0.00,
  `completion_date` datetime DEFAULT NULL,
  `notes` text,
  `created_by` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`task_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_parent_task_id` (`parent_task_id`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project task dependencies
CREATE TABLE IF NOT EXISTS `cod_project_task_dependency` (
  `dependency_id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `depends_on_task_id` int(11) NOT NULL,
  `dependency_type` varchar(20) DEFAULT 'finish_to_start' COMMENT 'finish_to_start, start_to_start, finish_to_finish, start_to_finish',
  `lag_days` int(11) DEFAULT 0,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`dependency_id`),
  UNIQUE KEY `task_dependency` (`task_id`, `depends_on_task_id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_depends_on_task_id` (`depends_on_task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project milestones
CREATE TABLE IF NOT EXISTS `cod_project_milestone` (
  `milestone_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `due_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, completed, overdue',
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `color` varchar(7) DEFAULT '#007bff',
  `is_critical` tinyint(1) DEFAULT 0,
  `completed_by` int(11) DEFAULT NULL,
  `date_completed` datetime DEFAULT NULL,
  `notes` text,
  `created_by` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`milestone_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project team members
CREATE TABLE IF NOT EXISTS `cod_project_team_member` (
  `team_member_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(50) DEFAULT 'team_member' COMMENT 'project_manager, team_lead, developer, designer, tester, analyst, etc',
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `can_view_budget` tinyint(1) DEFAULT 0,
  `can_edit_tasks` tinyint(1) DEFAULT 1,
  `can_log_time` tinyint(1) DEFAULT 1,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`team_member_id`),
  UNIQUE KEY `project_user` (`project_id`, `user_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project time logs
CREATE TABLE IF NOT EXISTS `cod_project_time_log` (
  `time_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `hours` decimal(10,2) NOT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(15,4) DEFAULT NULL,
  `description` text,
  `is_billable` tinyint(1) DEFAULT 1,
  `is_approved` tinyint(1) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`time_log_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_date` (`date`),
  KEY `idx_is_billable` (`is_billable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project documents
CREATE TABLE IF NOT EXISTS `cod_project_document` (
  `document_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `document_type` varchar(50) DEFAULT 'general' COMMENT 'general, requirement, design, specification, contract, etc',
  `version` varchar(20) DEFAULT '1.0',
  `description` text,
  `is_public` tinyint(1) DEFAULT 0,
  `uploaded_by` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`document_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_document_type` (`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project risks
CREATE TABLE IF NOT EXISTS `cod_project_risk` (
  `risk_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(50) DEFAULT 'general' COMMENT 'technical, financial, schedule, resource, external, etc',
  `probability` int(11) NOT NULL COMMENT '1-5 scale',
  `impact` int(11) NOT NULL COMMENT '1-5 scale',
  `risk_score` int(11) GENERATED ALWAYS AS (probability * impact) STORED,
  `priority` varchar(10) DEFAULT 'medium' COMMENT 'low, medium, high, critical',
  `status` varchar(20) DEFAULT 'identified' COMMENT 'identified, assessed, mitigated, closed',
  `mitigation_plan` text,
  `contingency_plan` text,
  `owner_id` int(11) DEFAULT NULL,
  `identified_by` int(11) NOT NULL,
  `date_identified` date NOT NULL,
  `target_resolution_date` date DEFAULT NULL,
  `actual_resolution_date` date DEFAULT NULL,
  `notes` text,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`risk_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_category` (`category`),
  KEY `idx_risk_score` (`risk_score`),
  KEY `idx_priority` (`priority`),
  KEY `idx_status` (`status`),
  KEY `idx_owner_id` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project issues
CREATE TABLE IF NOT EXISTS `cod_project_issue` (
  `issue_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `issue_type` varchar(50) DEFAULT 'bug' COMMENT 'bug, feature, improvement, task, story, epic',
  `priority` varchar(10) DEFAULT 'medium' COMMENT 'low, medium, high, critical',
  `severity` varchar(10) DEFAULT 'minor' COMMENT 'trivial, minor, major, critical, blocker',
  `status` varchar(20) NOT NULL DEFAULT 'open' COMMENT 'open, in_progress, resolved, closed, reopened',
  `resolution` varchar(50) DEFAULT NULL COMMENT 'fixed, wont_fix, duplicate, invalid, works_as_designed',
  `assigned_to` int(11) DEFAULT NULL,
  `reporter_id` int(11) NOT NULL,
  `due_date` date DEFAULT NULL,
  `resolution_date` datetime DEFAULT NULL,
  `estimated_hours` decimal(10,2) DEFAULT NULL,
  `actual_hours` decimal(10,2) DEFAULT 0.00,
  `environment` varchar(100) DEFAULT NULL,
  `steps_to_reproduce` text,
  `expected_result` text,
  `actual_result` text,
  `workaround` text,
  `notes` text,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`issue_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_issue_type` (`issue_type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_status` (`status`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_reporter_id` (`reporter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project templates
CREATE TABLE IF NOT EXISTS `cod_project_template` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `category` varchar(100) DEFAULT NULL,
  `estimated_duration_days` int(11) DEFAULT NULL,
  `estimated_budget` decimal(15,4) DEFAULT NULL,
  `template_data` longtext COMMENT 'JSON template structure',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`template_id`),
  KEY `idx_category` (`category`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project custom fields
CREATE TABLE IF NOT EXISTS `cod_project_custom_field` (
  `project_custom_field_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `custom_field_id` int(11) NOT NULL,
  `value` text,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`project_custom_field_id`),
  UNIQUE KEY `project_field` (`project_id`, `custom_field_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_custom_field_id` (`custom_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project tags
CREATE TABLE IF NOT EXISTS `cod_project_tag` (
  `project_tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`project_tag_id`),
  UNIQUE KEY `project_tag` (`project_id`, `tag_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project comments/notes
CREATE TABLE IF NOT EXISTS `cod_project_comment` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `is_private` tinyint(1) DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_parent_comment_id` (`parent_comment_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project activity log
CREATE TABLE IF NOT EXISTS `cod_project_activity` (
  `activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL COMMENT 'created, updated, deleted, status_changed, assigned, etc',
  `description` text NOT NULL,
  `old_value` text,
  `new_value` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`activity_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_activity_type` (`activity_type`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;