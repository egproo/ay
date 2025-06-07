-- AYM ERP - Supplier Documents Database Tables
-- Created: 2024
-- Purpose: Comprehensive document management system for suppliers with version control and audit trail

-- Main supplier documents table
CREATE TABLE IF NOT EXISTS `oc_supplier_document` (
  `document_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `title` varchar(128) NOT NULL,
  `description` text,
  `document_type` varchar(64) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT '0',
  `mime_type` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `tags` text,
  `download_count` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int(11) NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `archived_date` datetime DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`document_id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_document_type` (`document_type`),
  KEY `idx_expiry_date` (`expiry_date`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_date_added` (`date_added`),
  FULLTEXT KEY `idx_search` (`title`, `description`, `tags`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Document versions for version control
CREATE TABLE IF NOT EXISTS `oc_supplier_document_version` (
  `version_id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `version_number` int(11) NOT NULL DEFAULT '1',
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL DEFAULT '0',
  `mime_type` varchar(100) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`version_id`),
  KEY `idx_document_id` (`document_id`),
  KEY `idx_version_number` (`version_number`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Document history and audit trail
CREATE TABLE IF NOT EXISTS `oc_supplier_document_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `action` varchar(32) NOT NULL,
  `description` text,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_document_id` (`document_id`),
  KEY `idx_action` (`action`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Document download tracking
CREATE TABLE IF NOT EXISTS `oc_supplier_document_download` (
  `download_id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`download_id`),
  KEY `idx_document_id` (`document_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Document categories for better organization
CREATE TABLE IF NOT EXISTS `oc_supplier_document_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `description` text,
  `parent_id` int(11) DEFAULT '0',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`category_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Document sharing and permissions
CREATE TABLE IF NOT EXISTS `oc_supplier_document_share` (
  `share_id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `shared_with_user_id` int(11) DEFAULT NULL,
  `shared_with_group_id` int(11) DEFAULT NULL,
  `permission_level` enum('view','download','edit','admin') NOT NULL DEFAULT 'view',
  `shared_by` int(11) NOT NULL,
  `expiry_date` datetime DEFAULT NULL,
  `access_count` int(11) NOT NULL DEFAULT '0',
  `last_accessed` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`share_id`),
  KEY `idx_document_id` (`document_id`),
  KEY `idx_shared_with_user` (`shared_with_user_id`),
  KEY `idx_shared_with_group` (`shared_with_group_id`),
  KEY `idx_shared_by` (`shared_by`),
  KEY `idx_permission_level` (`permission_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Document notifications and alerts
CREATE TABLE IF NOT EXISTS `oc_supplier_document_notification` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `notification_type` varchar(32) NOT NULL,
  `message` text NOT NULL,
  `recipient_user_id` int(11) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_date` datetime DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `idx_document_id` (`document_id`),
  KEY `idx_notification_type` (`notification_type`),
  KEY `idx_recipient_user_id` (`recipient_user_id`),
  KEY `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Document templates for standardization
CREATE TABLE IF NOT EXISTS `oc_supplier_document_template` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `description` text,
  `document_type` varchar(64) NOT NULL,
  `template_path` varchar(255) NOT NULL,
  `required_fields` text,
  `validation_rules` text,
  `created_by` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`template_id`),
  KEY `idx_document_type` (`document_type`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Foreign key constraints
ALTER TABLE `oc_supplier_document`
  ADD CONSTRAINT `fk_sd_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `oc_supplier` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sd_created_by` FOREIGN KEY (`created_by`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_sd_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `oc_user` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sd_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `oc_user` (`user_id`) ON DELETE SET NULL;

ALTER TABLE `oc_supplier_document_version`
  ADD CONSTRAINT `fk_sdv_document` FOREIGN KEY (`document_id`) REFERENCES `oc_supplier_document` (`document_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sdv_created_by` FOREIGN KEY (`created_by`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT;

ALTER TABLE `oc_supplier_document_history`
  ADD CONSTRAINT `fk_sdh_document` FOREIGN KEY (`document_id`) REFERENCES `oc_supplier_document` (`document_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sdh_user` FOREIGN KEY (`user_id`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT;

ALTER TABLE `oc_supplier_document_download`
  ADD CONSTRAINT `fk_sdd_document` FOREIGN KEY (`document_id`) REFERENCES `oc_supplier_document` (`document_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sdd_user` FOREIGN KEY (`user_id`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT;

ALTER TABLE `oc_supplier_document_share`
  ADD CONSTRAINT `fk_sds_document` FOREIGN KEY (`document_id`) REFERENCES `oc_supplier_document` (`document_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sds_shared_with_user` FOREIGN KEY (`shared_with_user_id`) REFERENCES `oc_user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sds_shared_by` FOREIGN KEY (`shared_by`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT;

ALTER TABLE `oc_supplier_document_notification`
  ADD CONSTRAINT `fk_sdn_document` FOREIGN KEY (`document_id`) REFERENCES `oc_supplier_document` (`document_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sdn_recipient` FOREIGN KEY (`recipient_user_id`) REFERENCES `oc_user` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `oc_supplier_document_template`
  ADD CONSTRAINT `fk_sdt_created_by` FOREIGN KEY (`created_by`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT;

-- Sample data for testing
INSERT INTO `oc_supplier_document_category` (`name`, `description`, `parent_id`, `sort_order`, `status`) VALUES
('Contracts', 'Supplier contracts and agreements', 0, 1, 1),
('Certificates', 'Quality and compliance certificates', 0, 2, 1),
('Financial', 'Financial documents and statements', 0, 3, 1),
('Technical', 'Technical specifications and manuals', 0, 4, 1),
('Legal', 'Legal documents and licenses', 0, 5, 1);

INSERT INTO `oc_supplier_document` (`supplier_id`, `title`, `description`, `document_type`, `expiry_date`, `tags`, `status`, `created_by`, `date_added`, `date_modified`) VALUES
(1, 'Supply Agreement 2024', 'Annual supply agreement with terms and conditions', 'contract', '2024-12-31', 'contract,agreement,2024', 1, 1, NOW(), NOW()),
(1, 'ISO 9001 Certificate', 'Quality management system certificate', 'certificate', '2025-06-30', 'iso,quality,certificate', 1, 1, NOW(), NOW()),
(2, 'Product Catalog 2024', 'Complete product catalog with specifications', 'product_catalog', NULL, 'catalog,products,specifications', 1, 1, NOW(), NOW());

-- Views for reporting and analytics
CREATE OR REPLACE VIEW v_supplier_document_summary AS
SELECT 
    s.supplier_id,
    s.name as supplier_name,
    COUNT(sd.document_id) as total_documents,
    COUNT(CASE WHEN sd.status = 1 THEN 1 END) as active_documents,
    COUNT(CASE WHEN sd.status = 0 THEN 1 END) as archived_documents,
    COUNT(CASE WHEN sd.expiry_date IS NOT NULL AND sd.expiry_date < NOW() THEN 1 END) as expired_documents,
    COUNT(CASE WHEN sd.expiry_date IS NOT NULL AND sd.expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 1 END) as expiring_soon,
    SUM(sd.file_size) as total_file_size,
    SUM(sd.download_count) as total_downloads,
    MAX(sd.date_added) as last_document_added
FROM oc_supplier s
LEFT JOIN oc_supplier_document sd ON s.supplier_id = sd.supplier_id
WHERE s.status = 1
GROUP BY s.supplier_id;

CREATE OR REPLACE VIEW v_document_expiry_alerts AS
SELECT 
    sd.document_id,
    sd.title,
    s.name as supplier_name,
    sd.document_type,
    sd.expiry_date,
    DATEDIFF(sd.expiry_date, CURDATE()) as days_to_expiry,
    CASE 
        WHEN sd.expiry_date < CURDATE() THEN 'expired'
        WHEN sd.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'critical'
        WHEN sd.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'warning'
        ELSE 'normal'
    END as alert_level
FROM oc_supplier_document sd
LEFT JOIN oc_supplier s ON sd.supplier_id = s.supplier_id
WHERE sd.status = 1 
AND sd.expiry_date IS NOT NULL
ORDER BY sd.expiry_date ASC;

-- Indexes for performance optimization
CREATE INDEX idx_sd_supplier_type ON oc_supplier_document(supplier_id, document_type);
CREATE INDEX idx_sd_expiry_status ON oc_supplier_document(expiry_date, status);
CREATE INDEX idx_sd_created_date ON oc_supplier_document(date_added, created_by);
CREATE INDEX idx_sdh_document_action ON oc_supplier_document_history(document_id, action, date_added);
CREATE INDEX idx_sdd_document_date ON oc_supplier_document_download(document_id, date_added);

-- Triggers for automatic notifications
DELIMITER $$

CREATE TRIGGER tr_document_expiry_notification
AFTER INSERT ON oc_supplier_document
FOR EACH ROW
BEGIN
    IF NEW.expiry_date IS NOT NULL AND NEW.expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) THEN
        INSERT INTO oc_supplier_document_notification 
        (document_id, notification_type, message, recipient_user_id, date_added)
        VALUES 
        (NEW.document_id, 'expiry_warning', 
         CONCAT('Document "', NEW.title, '" will expire on ', NEW.expiry_date), 
         NEW.created_by, NOW());
    END IF;
END$$

CREATE TRIGGER tr_document_download_log
AFTER UPDATE ON oc_supplier_document
FOR EACH ROW
BEGIN
    IF NEW.download_count > OLD.download_count THEN
        INSERT INTO oc_supplier_document_history 
        (document_id, action, description, user_id, date_added)
        VALUES 
        (NEW.document_id, 'downloaded', 
         CONCAT('Document downloaded (total: ', NEW.download_count, ')'), 
         @user_id, NOW());
    END IF;
END$$

DELIMITER ;
