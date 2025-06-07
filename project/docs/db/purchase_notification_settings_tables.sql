-- AYM ERP - Purchase Notification Settings Database Tables
-- Created: 2024
-- Purpose: Comprehensive notification management system for purchase workflows with multi-channel delivery

-- Notification events configuration
CREATE TABLE IF NOT EXISTS `oc_purchase_notification_event` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_name` varchar(128) NOT NULL,
  `event_type` varchar(64) NOT NULL,
  `description` text,
  `trigger_conditions` text,
  `delivery_methods` text,
  `recipients` text,
  `template_id` int(11) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent','critical') NOT NULL DEFAULT 'normal',
  `delay_minutes` int(11) NOT NULL DEFAULT '0',
  `retry_attempts` int(11) NOT NULL DEFAULT '3',
  `retry_delay_minutes` int(11) NOT NULL DEFAULT '5',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`event_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_status` (`status`),
  KEY `idx_template_id` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Notification templates
CREATE TABLE IF NOT EXISTS `oc_purchase_notification_template` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `description` text,
  `event_type` varchar(64) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `content_html` text,
  `variables` text,
  `language_code` varchar(5) NOT NULL DEFAULT 'en',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`template_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_language_code` (`language_code`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Notification rules for conditional logic
CREATE TABLE IF NOT EXISTS `oc_purchase_notification_rule` (
  `rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(128) NOT NULL,
  `description` text,
  `conditions` text NOT NULL,
  `actions` text NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`rule_id`),
  KEY `idx_priority` (`priority`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Escalation levels configuration
CREATE TABLE IF NOT EXISTS `oc_purchase_notification_escalation` (
  `escalation_id` int(11) NOT NULL AUTO_INCREMENT,
  `level_name` varchar(128) NOT NULL,
  `description` text,
  `escalation_to` text NOT NULL,
  `trigger_after_hours` int(11) NOT NULL DEFAULT '24',
  `max_escalations` int(11) NOT NULL DEFAULT '3',
  `escalation_message` text,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`escalation_id`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Notification queue for batch processing
CREATE TABLE IF NOT EXISTS `oc_purchase_notification_queue` (
  `queue_id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_type` varchar(64) NOT NULL,
  `delivery_method` enum('email','sms','push','internal','webhook') NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `recipient_type` enum('user','group','role','email','phone') NOT NULL DEFAULT 'user',
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `message_html` text,
  `priority` enum('low','normal','high','urgent','critical') NOT NULL DEFAULT 'normal',
  `scheduled_date` datetime DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT '0',
  `max_attempts` int(11) NOT NULL DEFAULT '3',
  `status` enum('pending','processing','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  `error_message` text,
  `metadata` text,
  `date_added` datetime NOT NULL,
  `date_processed` datetime DEFAULT NULL,
  PRIMARY KEY (`queue_id`),
  KEY `idx_notification_type` (`notification_type`),
  KEY `idx_delivery_method` (`delivery_method`),
  KEY `idx_recipient` (`recipient`),
  KEY `idx_priority` (`priority`),
  KEY `idx_status` (`status`),
  KEY `idx_scheduled_date` (`scheduled_date`),
  KEY `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Notification delivery log
CREATE TABLE IF NOT EXISTS `oc_purchase_notification_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_type` varchar(64) NOT NULL,
  `delivery_method` enum('email','sms','push','internal','webhook') NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','sent','delivered','failed','bounced','cancelled') NOT NULL DEFAULT 'pending',
  `error_message` text,
  `provider_response` text,
  `delivery_time_seconds` decimal(10,3) DEFAULT NULL,
  `cost` decimal(10,4) DEFAULT NULL,
  `metadata` text,
  `date_added` datetime NOT NULL,
  `date_sent` datetime DEFAULT NULL,
  `delivered_date` datetime DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_notification_type` (`notification_type`),
  KEY `idx_delivery_method` (`delivery_method`),
  KEY `idx_recipient` (`recipient`),
  KEY `idx_status` (`status`),
  KEY `idx_date_added` (`date_added`),
  KEY `idx_date_sent` (`date_sent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Notification preferences per user
CREATE TABLE IF NOT EXISTS `oc_purchase_notification_preference` (
  `preference_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notification_type` varchar(64) NOT NULL,
  `delivery_method` enum('email','sms','push','internal','none') NOT NULL DEFAULT 'email',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `frequency` enum('immediate','hourly','daily','weekly','never') NOT NULL DEFAULT 'immediate',
  `quiet_hours_start` time DEFAULT NULL,
  `quiet_hours_end` time DEFAULT NULL,
  `timezone` varchar(64) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `idx_user_notification_method` (`user_id`, `notification_type`, `delivery_method`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_notification_type` (`notification_type`),
  KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Notification digest settings
CREATE TABLE IF NOT EXISTS `oc_purchase_notification_digest` (
  `digest_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `digest_type` enum('daily','weekly','monthly') NOT NULL DEFAULT 'daily',
  `delivery_method` enum('email','internal') NOT NULL DEFAULT 'email',
  `delivery_time` time NOT NULL DEFAULT '09:00:00',
  `delivery_day` tinyint(1) DEFAULT NULL COMMENT 'For weekly digest: 1=Monday, 7=Sunday',
  `delivery_date` tinyint(2) DEFAULT NULL COMMENT 'For monthly digest: 1-31',
  `include_types` text,
  `last_sent` datetime DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`digest_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_digest_type` (`digest_type`),
  KEY `idx_enabled` (`enabled`),
  KEY `idx_delivery_time` (`delivery_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Notification blacklist/whitelist
CREATE TABLE IF NOT EXISTS `oc_purchase_notification_filter` (
  `filter_id` int(11) NOT NULL AUTO_INCREMENT,
  `filter_type` enum('blacklist','whitelist') NOT NULL,
  `delivery_method` enum('email','sms','push','all') NOT NULL DEFAULT 'all',
  `filter_value` varchar(255) NOT NULL,
  `filter_pattern` varchar(255) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`filter_id`),
  KEY `idx_filter_type` (`filter_type`),
  KEY `idx_delivery_method` (`delivery_method`),
  KEY `idx_filter_value` (`filter_value`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Notification webhooks
CREATE TABLE IF NOT EXISTS `oc_purchase_notification_webhook` (
  `webhook_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `url` varchar(500) NOT NULL,
  `method` enum('GET','POST','PUT','PATCH') NOT NULL DEFAULT 'POST',
  `headers` text,
  `payload_template` text,
  `event_types` text,
  `secret_key` varchar(255) DEFAULT NULL,
  `timeout_seconds` int(11) NOT NULL DEFAULT '30',
  `retry_attempts` int(11) NOT NULL DEFAULT '3',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`webhook_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Notification analytics and metrics
CREATE TABLE IF NOT EXISTS `oc_purchase_notification_metric` (
  `metric_id` int(11) NOT NULL AUTO_INCREMENT,
  `metric_date` date NOT NULL,
  `notification_type` varchar(64) NOT NULL,
  `delivery_method` enum('email','sms','push','internal','webhook') NOT NULL,
  `total_sent` int(11) NOT NULL DEFAULT '0',
  `total_delivered` int(11) NOT NULL DEFAULT '0',
  `total_failed` int(11) NOT NULL DEFAULT '0',
  `total_bounced` int(11) NOT NULL DEFAULT '0',
  `avg_delivery_time` decimal(10,3) DEFAULT NULL,
  `total_cost` decimal(10,4) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`metric_id`),
  UNIQUE KEY `idx_date_type_method` (`metric_date`, `notification_type`, `delivery_method`),
  KEY `idx_metric_date` (`metric_date`),
  KEY `idx_notification_type` (`notification_type`),
  KEY `idx_delivery_method` (`delivery_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Foreign key constraints
ALTER TABLE `oc_purchase_notification_event`
  ADD CONSTRAINT `fk_pne_template` FOREIGN KEY (`template_id`) REFERENCES `oc_purchase_notification_template` (`template_id`) ON DELETE SET NULL;

ALTER TABLE `oc_purchase_notification_template`
  ADD CONSTRAINT `fk_pnt_created_by` FOREIGN KEY (`created_by`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT;

ALTER TABLE `oc_purchase_notification_preference`
  ADD CONSTRAINT `fk_pnp_user` FOREIGN KEY (`user_id`) REFERENCES `oc_user` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `oc_purchase_notification_digest`
  ADD CONSTRAINT `fk_pnd_user` FOREIGN KEY (`user_id`) REFERENCES `oc_user` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `oc_purchase_notification_filter`
  ADD CONSTRAINT `fk_pnf_created_by` FOREIGN KEY (`created_by`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT;

ALTER TABLE `oc_purchase_notification_webhook`
  ADD CONSTRAINT `fk_pnw_created_by` FOREIGN KEY (`created_by`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT;

-- Sample data for testing
INSERT INTO `oc_purchase_notification_template` (`name`, `description`, `event_type`, `subject`, `content`, `language_code`, `status`, `created_by`, `date_added`, `date_modified`) VALUES
('Purchase Order Created', 'Template for new purchase order notifications', 'purchase_order_created', 'New Purchase Order #{order_number}', 'A new purchase order #{order_number} has been created for {supplier_name} with total amount {order_total}.', 'en', 1, 1, NOW(), NOW()),
('Purchase Order Approved', 'Template for purchase order approval notifications', 'purchase_order_approved', 'Purchase Order #{order_number} Approved', 'Purchase order #{order_number} has been approved by {approver_name} on {approval_date}.', 'en', 1, 1, NOW(), NOW()),
('Budget Exceeded Alert', 'Template for budget exceeded notifications', 'budget_exceeded', 'Budget Exceeded Alert', 'Purchase order #{order_number} exceeds the allocated budget. Order total: {order_total}, Budget limit: {budget_limit}.', 'en', 1, 1, NOW(), NOW());

INSERT INTO `oc_purchase_notification_event` (`event_name`, `event_type`, `description`, `delivery_methods`, `recipients`, `template_id`, `priority`, `status`, `date_added`, `date_modified`) VALUES
('New Purchase Order', 'purchase_order_created', 'Notify when a new purchase order is created', '["email","internal"]', '["manager","finance"]', 1, 'normal', 1, NOW(), NOW()),
('Purchase Order Approval', 'purchase_order_approved', 'Notify when a purchase order is approved', '["email","sms"]', '["requester","supplier"]', 2, 'high', 1, NOW(), NOW()),
('Budget Alert', 'budget_exceeded', 'Alert when purchase order exceeds budget', '["email","push"]', '["manager","finance"]', 3, 'urgent', 1, NOW(), NOW());

INSERT INTO `oc_purchase_notification_escalation` (`level_name`, `description`, `escalation_to`, `trigger_after_hours`, `max_escalations`, `sort_order`, `status`, `date_added`, `date_modified`) VALUES
('Level 1 - Supervisor', 'Escalate to direct supervisor', '["supervisor"]', 4, 2, 1, 1, NOW(), NOW()),
('Level 2 - Manager', 'Escalate to department manager', '["manager"]', 8, 2, 2, 1, NOW(), NOW()),
('Level 3 - Director', 'Escalate to director level', '["director"]', 24, 1, 3, 1, NOW(), NOW());

-- Views for reporting and analytics
CREATE OR REPLACE VIEW v_notification_summary AS
SELECT 
    pnl.notification_type,
    pnl.delivery_method,
    COUNT(*) as total_notifications,
    COUNT(CASE WHEN pnl.status = 'sent' THEN 1 END) as sent_count,
    COUNT(CASE WHEN pnl.status = 'delivered' THEN 1 END) as delivered_count,
    COUNT(CASE WHEN pnl.status = 'failed' THEN 1 END) as failed_count,
    ROUND(AVG(pnl.delivery_time_seconds), 2) as avg_delivery_time,
    SUM(pnl.cost) as total_cost,
    DATE(pnl.date_added) as log_date
FROM oc_purchase_notification_log pnl
WHERE pnl.date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY pnl.notification_type, pnl.delivery_method, DATE(pnl.date_added)
ORDER BY log_date DESC;

CREATE OR REPLACE VIEW v_notification_performance AS
SELECT 
    delivery_method,
    COUNT(*) as total_sent,
    COUNT(CASE WHEN status IN ('sent', 'delivered') THEN 1 END) as successful,
    ROUND((COUNT(CASE WHEN status IN ('sent', 'delivered') THEN 1 END) / COUNT(*)) * 100, 2) as success_rate,
    ROUND(AVG(delivery_time_seconds), 2) as avg_delivery_time,
    MIN(delivery_time_seconds) as min_delivery_time,
    MAX(delivery_time_seconds) as max_delivery_time
FROM oc_purchase_notification_log
WHERE date_added >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY delivery_method;

-- Indexes for performance optimization
CREATE INDEX idx_pnq_priority_status ON oc_purchase_notification_queue(priority, status, scheduled_date);
CREATE INDEX idx_pnl_type_method_date ON oc_purchase_notification_log(notification_type, delivery_method, date_added);
CREATE INDEX idx_pnp_user_type ON oc_purchase_notification_preference(user_id, notification_type, enabled);
CREATE INDEX idx_pnd_delivery_time ON oc_purchase_notification_digest(delivery_time, enabled);

-- Triggers for automatic metrics collection
DELIMITER $$

CREATE TRIGGER tr_notification_log_metrics
AFTER INSERT ON oc_purchase_notification_log
FOR EACH ROW
BEGIN
    INSERT INTO oc_purchase_notification_metric 
    (metric_date, notification_type, delivery_method, total_sent, date_added)
    VALUES 
    (DATE(NEW.date_added), NEW.notification_type, NEW.delivery_method, 1, NOW())
    ON DUPLICATE KEY UPDATE 
    total_sent = total_sent + 1;
END$$

CREATE TRIGGER tr_notification_delivery_metrics
AFTER UPDATE ON oc_purchase_notification_log
FOR EACH ROW
BEGIN
    IF NEW.status != OLD.status THEN
        IF NEW.status = 'delivered' THEN
            UPDATE oc_purchase_notification_metric 
            SET total_delivered = total_delivered + 1,
                avg_delivery_time = (avg_delivery_time + NEW.delivery_time_seconds) / 2
            WHERE metric_date = DATE(NEW.date_added) 
            AND notification_type = NEW.notification_type 
            AND delivery_method = NEW.delivery_method;
        ELSEIF NEW.status = 'failed' THEN
            UPDATE oc_purchase_notification_metric 
            SET total_failed = total_failed + 1
            WHERE metric_date = DATE(NEW.date_added) 
            AND notification_type = NEW.notification_type 
            AND delivery_method = NEW.delivery_method;
        ELSEIF NEW.status = 'bounced' THEN
            UPDATE oc_purchase_notification_metric 
            SET total_bounced = total_bounced + 1
            WHERE metric_date = DATE(NEW.date_added) 
            AND notification_type = NEW.notification_type 
            AND delivery_method = NEW.delivery_method;
        END IF;
    END IF;
END$$

DELIMITER ;
