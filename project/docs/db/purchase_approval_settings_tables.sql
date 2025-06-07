-- AYM ERP - Purchase Approval Settings Database Tables
-- Created: 2024
-- Purpose: Comprehensive purchase approval workflow management system with multi-level approval chains

-- Purchase approval amount thresholds
CREATE TABLE IF NOT EXISTS `oc_purchase_approval_threshold` (
  `threshold_id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `currency_id` int(11) NOT NULL DEFAULT '1',
  `approver_type` enum('user','group','role','department') NOT NULL DEFAULT 'user',
  `approver_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL DEFAULT '0',
  `category_id` int(11) NOT NULL DEFAULT '0',
  `conditions` text,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`threshold_id`),
  KEY `idx_amount` (`amount`),
  KEY `idx_approver` (`approver_type`, `approver_id`),
  KEY `idx_department` (`department_id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Department-specific approval rules
CREATE TABLE IF NOT EXISTS `oc_purchase_approval_department_rule` (
  `rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) NOT NULL,
  `approver_type` enum('user','group','role') NOT NULL DEFAULT 'user',
  `approver_id` int(11) NOT NULL,
  `min_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `max_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `priority` int(11) NOT NULL DEFAULT '0',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`rule_id`),
  KEY `idx_department` (`department_id`),
  KEY `idx_approver` (`approver_type`, `approver_id`),
  KEY `idx_amount_range` (`min_amount`, `max_amount`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Category-specific approval rules
CREATE TABLE IF NOT EXISTS `oc_purchase_approval_category_rule` (
  `rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `approver_type` enum('user','group','role') NOT NULL DEFAULT 'user',
  `approver_id` int(11) NOT NULL,
  `min_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `max_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `priority` int(11) NOT NULL DEFAULT '0',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`rule_id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_approver` (`approver_type`, `approver_id`),
  KEY `idx_amount_range` (`min_amount`, `max_amount`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Workflow steps definition
CREATE TABLE IF NOT EXISTS `oc_purchase_approval_workflow_step` (
  `step_id` int(11) NOT NULL AUTO_INCREMENT,
  `step_name` varchar(128) NOT NULL,
  `description` text,
  `approver_type` enum('user','group','role','department') NOT NULL DEFAULT 'user',
  `approver_id` int(11) NOT NULL,
  `conditions` text,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `timeout_hours` int(11) NOT NULL DEFAULT '24',
  `escalation_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `escalation_approver_type` enum('user','group','role') DEFAULT NULL,
  `escalation_approver_id` int(11) DEFAULT NULL,
  `escalation_timeout_hours` int(11) NOT NULL DEFAULT '48',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`step_id`),
  KEY `idx_approver` (`approver_type`, `approver_id`),
  KEY `idx_escalation_approver` (`escalation_approver_type`, `escalation_approver_id`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Purchase order approval instances
CREATE TABLE IF NOT EXISTS `oc_purchase_approval_instance` (
  `approval_id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(11) NOT NULL,
  `workflow_type` enum('sequential','parallel','hybrid') NOT NULL DEFAULT 'sequential',
  `current_step` int(11) NOT NULL DEFAULT '1',
  `total_steps` int(11) NOT NULL DEFAULT '1',
  `status` enum('pending','in_progress','approved','rejected','cancelled','expired') NOT NULL DEFAULT 'pending',
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `requested_by` int(11) NOT NULL,
  `requested_date` datetime NOT NULL,
  `approved_date` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `rejected_date` datetime DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejection_reason` text,
  `expiry_date` datetime DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`approval_id`),
  KEY `idx_purchase_order` (`purchase_order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_requested_by` (`requested_by`),
  KEY `idx_requested_date` (`requested_date`),
  KEY `idx_expiry_date` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Individual approval steps for each purchase order
CREATE TABLE IF NOT EXISTS `oc_purchase_approval_step` (
  `approval_step_id` int(11) NOT NULL AUTO_INCREMENT,
  `approval_id` int(11) NOT NULL,
  `step_number` int(11) NOT NULL,
  `step_name` varchar(128) NOT NULL,
  `approver_type` enum('user','group','role','department') NOT NULL,
  `approver_id` int(11) NOT NULL,
  `approver_name` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected','skipped','expired') NOT NULL DEFAULT 'pending',
  `assigned_date` datetime NOT NULL,
  `response_date` datetime DEFAULT NULL,
  `timeout_date` datetime DEFAULT NULL,
  `comments` text,
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `escalation_level` int(11) NOT NULL DEFAULT '0',
  `escalated_to` int(11) DEFAULT NULL,
  `escalated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`approval_step_id`),
  KEY `idx_approval_id` (`approval_id`),
  KEY `idx_step_number` (`step_number`),
  KEY `idx_approver` (`approver_type`, `approver_id`),
  KEY `idx_status` (`status`),
  KEY `idx_assigned_date` (`assigned_date`),
  KEY `idx_timeout_date` (`timeout_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Approval history and audit trail
CREATE TABLE IF NOT EXISTS `oc_purchase_approval_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `approval_id` int(11) NOT NULL,
  `approval_step_id` int(11) DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  `description` text,
  `old_status` varchar(32) DEFAULT NULL,
  `new_status` varchar(32) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_approval_id` (`approval_id`),
  KEY `idx_approval_step_id` (`approval_step_id`),
  KEY `idx_action` (`action`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Approval notifications
CREATE TABLE IF NOT EXISTS `oc_purchase_approval_notification` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `approval_id` int(11) NOT NULL,
  `approval_step_id` int(11) DEFAULT NULL,
  `notification_type` varchar(64) NOT NULL,
  `recipient_type` enum('user','group','role') NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `recipient_email` varchar(255) DEFAULT NULL,
  `recipient_phone` varchar(32) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `delivery_method` enum('email','sms','push','internal') NOT NULL DEFAULT 'email',
  `status` enum('pending','sent','delivered','failed','cancelled') NOT NULL DEFAULT 'pending',
  `sent_date` datetime DEFAULT NULL,
  `delivered_date` datetime DEFAULT NULL,
  `error_message` text,
  `retry_count` int(11) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `idx_approval_id` (`approval_id`),
  KEY `idx_approval_step_id` (`approval_step_id`),
  KEY `idx_notification_type` (`notification_type`),
  KEY `idx_recipient` (`recipient_type`, `recipient_id`),
  KEY `idx_status` (`status`),
  KEY `idx_delivery_method` (`delivery_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Approval delegation
CREATE TABLE IF NOT EXISTS `oc_purchase_approval_delegation` (
  `delegation_id` int(11) NOT NULL AUTO_INCREMENT,
  `delegator_id` int(11) NOT NULL,
  `delegate_id` int(11) NOT NULL,
  `delegation_type` enum('temporary','permanent','conditional') NOT NULL DEFAULT 'temporary',
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `conditions` text,
  `max_amount` decimal(15,4) DEFAULT NULL,
  `department_ids` text,
  `category_ids` text,
  `reason` text,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`delegation_id`),
  KEY `idx_delegator` (`delegator_id`),
  KEY `idx_delegate` (`delegate_id`),
  KEY `idx_delegation_type` (`delegation_type`),
  KEY `idx_date_range` (`start_date`, `end_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Emergency approval overrides
CREATE TABLE IF NOT EXISTS `oc_purchase_approval_emergency` (
  `emergency_id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(11) NOT NULL,
  `original_approval_id` int(11) DEFAULT NULL,
  `emergency_approver_id` int(11) NOT NULL,
  `emergency_reason` text NOT NULL,
  `justification` text,
  `risk_assessment` text,
  `conditions` text,
  `approval_date` datetime NOT NULL,
  `review_required` tinyint(1) NOT NULL DEFAULT '1',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_date` datetime DEFAULT NULL,
  `review_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `review_comments` text,
  PRIMARY KEY (`emergency_id`),
  KEY `idx_purchase_order` (`purchase_order_id`),
  KEY `idx_original_approval` (`original_approval_id`),
  KEY `idx_emergency_approver` (`emergency_approver_id`),
  KEY `idx_approval_date` (`approval_date`),
  KEY `idx_review_status` (`review_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Foreign key constraints
ALTER TABLE `oc_purchase_approval_threshold`
  ADD CONSTRAINT `fk_pat_currency` FOREIGN KEY (`currency_id`) REFERENCES `oc_currency` (`currency_id`) ON DELETE RESTRICT;

ALTER TABLE `oc_purchase_approval_instance`
  ADD CONSTRAINT `fk_pai_purchase_order` FOREIGN KEY (`purchase_order_id`) REFERENCES `oc_purchase_order` (`purchase_order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pai_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_pai_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `oc_user` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pai_rejected_by` FOREIGN KEY (`rejected_by`) REFERENCES `oc_user` (`user_id`) ON DELETE SET NULL;

ALTER TABLE `oc_purchase_approval_step`
  ADD CONSTRAINT `fk_pas_approval` FOREIGN KEY (`approval_id`) REFERENCES `oc_purchase_approval_instance` (`approval_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pas_escalated_to` FOREIGN KEY (`escalated_to`) REFERENCES `oc_user` (`user_id`) ON DELETE SET NULL;

ALTER TABLE `oc_purchase_approval_history`
  ADD CONSTRAINT `fk_pah_approval` FOREIGN KEY (`approval_id`) REFERENCES `oc_purchase_approval_instance` (`approval_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pah_approval_step` FOREIGN KEY (`approval_step_id`) REFERENCES `oc_purchase_approval_step` (`approval_step_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pah_user` FOREIGN KEY (`user_id`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT;

ALTER TABLE `oc_purchase_approval_notification`
  ADD CONSTRAINT `fk_pan_approval` FOREIGN KEY (`approval_id`) REFERENCES `oc_purchase_approval_instance` (`approval_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pan_approval_step` FOREIGN KEY (`approval_step_id`) REFERENCES `oc_purchase_approval_step` (`approval_step_id`) ON DELETE SET NULL;

ALTER TABLE `oc_purchase_approval_delegation`
  ADD CONSTRAINT `fk_pad_delegator` FOREIGN KEY (`delegator_id`) REFERENCES `oc_user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pad_delegate` FOREIGN KEY (`delegate_id`) REFERENCES `oc_user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pad_created_by` FOREIGN KEY (`created_by`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT;

ALTER TABLE `oc_purchase_approval_emergency`
  ADD CONSTRAINT `fk_pae_purchase_order` FOREIGN KEY (`purchase_order_id`) REFERENCES `oc_purchase_order` (`purchase_order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pae_original_approval` FOREIGN KEY (`original_approval_id`) REFERENCES `oc_purchase_approval_instance` (`approval_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pae_emergency_approver` FOREIGN KEY (`emergency_approver_id`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_pae_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `oc_user` (`user_id`) ON DELETE SET NULL;

-- Sample data for testing
INSERT INTO `oc_purchase_approval_threshold` (`amount`, `currency_id`, `approver_type`, `approver_id`, `sort_order`, `status`, `date_added`, `date_modified`) VALUES
(1000.00, 1, 'user', 1, 1, 1, NOW(), NOW()),
(5000.00, 1, 'user', 1, 2, 1, NOW(), NOW()),
(10000.00, 1, 'group', 1, 3, 1, NOW(), NOW()),
(50000.00, 1, 'group', 1, 4, 1, NOW(), NOW());

INSERT INTO `oc_purchase_approval_workflow_step` (`step_name`, `description`, `approver_type`, `approver_id`, `sort_order`, `is_required`, `timeout_hours`, `status`, `date_added`, `date_modified`) VALUES
('Supervisor Approval', 'Direct supervisor approval for purchase requests', 'user', 1, 1, 1, 24, 1, NOW(), NOW()),
('Department Manager Approval', 'Department manager approval for significant purchases', 'user', 1, 2, 1, 48, 1, NOW(), NOW()),
('Finance Manager Approval', 'Finance manager approval for budget compliance', 'user', 1, 3, 1, 72, 1, NOW(), NOW()),
('General Manager Approval', 'General manager approval for high-value purchases', 'user', 1, 4, 1, 96, 1, NOW(), NOW());

-- Views for reporting and analytics
CREATE OR REPLACE VIEW v_purchase_approval_summary AS
SELECT 
    pai.approval_id,
    pai.purchase_order_id,
    po.order_number,
    po.total as order_total,
    pai.status as approval_status,
    pai.priority,
    pai.requested_date,
    pai.approved_date,
    pai.rejected_date,
    CONCAT(ru.firstname, ' ', ru.lastname) as requested_by_name,
    CONCAT(au.firstname, ' ', au.lastname) as approved_by_name,
    CONCAT(reu.firstname, ' ', reu.lastname) as rejected_by_name,
    pai.current_step,
    pai.total_steps,
    CASE 
        WHEN pai.status = 'approved' THEN TIMESTAMPDIFF(HOUR, pai.requested_date, pai.approved_date)
        WHEN pai.status = 'rejected' THEN TIMESTAMPDIFF(HOUR, pai.requested_date, pai.rejected_date)
        ELSE TIMESTAMPDIFF(HOUR, pai.requested_date, NOW())
    END as processing_hours
FROM oc_purchase_approval_instance pai
LEFT JOIN oc_purchase_order po ON pai.purchase_order_id = po.purchase_order_id
LEFT JOIN oc_user ru ON pai.requested_by = ru.user_id
LEFT JOIN oc_user au ON pai.approved_by = au.user_id
LEFT JOIN oc_user reu ON pai.rejected_by = reu.user_id;

CREATE OR REPLACE VIEW v_pending_approvals AS
SELECT 
    pas.approval_step_id,
    pas.approval_id,
    pai.purchase_order_id,
    po.order_number,
    po.total as order_total,
    pas.step_number,
    pas.step_name,
    pas.approver_type,
    pas.approver_id,
    pas.approver_name,
    pas.assigned_date,
    pas.timeout_date,
    TIMESTAMPDIFF(HOUR, pas.assigned_date, NOW()) as pending_hours,
    CASE 
        WHEN pas.timeout_date < NOW() THEN 'overdue'
        WHEN TIMESTAMPDIFF(HOUR, NOW(), pas.timeout_date) <= 4 THEN 'urgent'
        WHEN TIMESTAMPDIFF(HOUR, NOW(), pas.timeout_date) <= 24 THEN 'due_soon'
        ELSE 'normal'
    END as urgency_level
FROM oc_purchase_approval_step pas
JOIN oc_purchase_approval_instance pai ON pas.approval_id = pai.approval_id
LEFT JOIN oc_purchase_order po ON pai.purchase_order_id = po.purchase_order_id
WHERE pas.status = 'pending'
AND pai.status IN ('pending', 'in_progress')
ORDER BY pas.timeout_date ASC;

-- Indexes for performance optimization
CREATE INDEX idx_pai_status_date ON oc_purchase_approval_instance(status, requested_date);
CREATE INDEX idx_pas_approver_status ON oc_purchase_approval_step(approver_type, approver_id, status);
CREATE INDEX idx_pah_approval_action ON oc_purchase_approval_history(approval_id, action, date_added);
CREATE INDEX idx_pan_recipient_status ON oc_purchase_approval_notification(recipient_type, recipient_id, status);

-- Triggers for automatic workflow management
DELIMITER $$

CREATE TRIGGER tr_approval_status_change
AFTER UPDATE ON oc_purchase_approval_step
FOR EACH ROW
BEGIN
    IF NEW.status != OLD.status THEN
        INSERT INTO oc_purchase_approval_history 
        (approval_id, approval_step_id, action, description, old_status, new_status, user_id, user_name, date_added)
        VALUES 
        (NEW.approval_id, NEW.approval_step_id, 'status_change', 
         CONCAT('Step status changed from ', OLD.status, ' to ', NEW.status), 
         OLD.status, NEW.status, @user_id, @user_name, NOW());
    END IF;
END$$

CREATE TRIGGER tr_approval_timeout_check
AFTER INSERT ON oc_purchase_approval_step
FOR EACH ROW
BEGIN
    IF NEW.timeout_date IS NOT NULL THEN
        INSERT INTO oc_purchase_approval_notification 
        (approval_id, approval_step_id, notification_type, recipient_type, recipient_id, 
         subject, message, delivery_method, date_added)
        VALUES 
        (NEW.approval_id, NEW.approval_step_id, 'timeout_reminder', NEW.approver_type, NEW.approver_id,
         'Purchase Approval Timeout Reminder', 
         CONCAT('Purchase order approval step "', NEW.step_name, '" will timeout on ', NEW.timeout_date),
         'email', NOW());
    END IF;
END$$

DELIMITER ;
