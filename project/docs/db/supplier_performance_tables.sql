-- AYM ERP - Supplier Performance Database Tables
-- Created: 2024
-- Purpose: Comprehensive supplier performance evaluation and tracking system

-- Main supplier performance evaluation table
CREATE TABLE IF NOT EXISTS `oc_supplier_performance_evaluation` (
  `evaluation_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `evaluation_period` varchar(32) NOT NULL,
  `overall_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `delivery_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `quality_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `cost_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `service_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `innovation_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `sustainability_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `compliance_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `comments` text,
  `evaluator_id` int(11) NOT NULL,
  `evaluation_date` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`evaluation_id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_evaluation_date` (`evaluation_date`),
  KEY `idx_overall_score` (`overall_score`),
  KEY `idx_evaluator_id` (`evaluator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Detailed evaluation criteria scores
CREATE TABLE IF NOT EXISTS `oc_supplier_evaluation_criteria` (
  `criteria_id` int(11) NOT NULL AUTO_INCREMENT,
  `evaluation_id` int(11) NOT NULL,
  `criteria_name` varchar(64) NOT NULL,
  `score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `weight` decimal(5,2) NOT NULL DEFAULT '1.00',
  `comments` text,
  PRIMARY KEY (`criteria_id`),
  KEY `idx_evaluation_id` (`evaluation_id`),
  KEY `idx_criteria_name` (`criteria_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Delivery performance tracking
CREATE TABLE IF NOT EXISTS `oc_supplier_delivery_performance` (
  `delivery_performance_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `promised_delivery_date` date NOT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `delivery_status` enum('on_time','early','late','pending') NOT NULL DEFAULT 'pending',
  `delay_days` int(11) NOT NULL DEFAULT '0',
  `delivery_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `delivery_date` datetime NOT NULL,
  `notes` text,
  PRIMARY KEY (`delivery_performance_id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_purchase_order_id` (`purchase_order_id`),
  KEY `idx_delivery_status` (`delivery_status`),
  KEY `idx_delivery_date` (`delivery_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Quality inspection results
CREATE TABLE IF NOT EXISTS `oc_supplier_quality_inspection` (
  `inspection_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `received_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `inspected_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `passed_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `defect_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `quality_status` enum('passed','failed','partial','pending') NOT NULL DEFAULT 'pending',
  `quality_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `defect_types` text,
  `inspector_id` int(11) NOT NULL,
  `inspection_date` datetime NOT NULL,
  `notes` text,
  PRIMARY KEY (`inspection_id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_purchase_order_id` (`purchase_order_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_quality_status` (`quality_status`),
  KEY `idx_inspection_date` (`inspection_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Cost performance tracking
CREATE TABLE IF NOT EXISTS `oc_supplier_cost_performance` (
  `cost_performance_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `budget_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `actual_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `variance_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `variance_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `cost_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `currency_id` int(11) NOT NULL,
  `evaluation_date` datetime NOT NULL,
  `notes` text,
  PRIMARY KEY (`cost_performance_id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_purchase_order_id` (`purchase_order_id`),
  KEY `idx_evaluation_date` (`evaluation_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Service performance incidents
CREATE TABLE IF NOT EXISTS `oc_supplier_service_incident` (
  `incident_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `incident_type` varchar(64) NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `description` text NOT NULL,
  `reported_by` int(11) NOT NULL,
  `reported_date` datetime NOT NULL,
  `resolved_date` datetime DEFAULT NULL,
  `resolution_time_hours` int(11) DEFAULT NULL,
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `impact_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `resolution_notes` text,
  PRIMARY KEY (`incident_id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_incident_type` (`incident_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_status` (`status`),
  KEY `idx_reported_date` (`reported_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Performance improvement actions
CREATE TABLE IF NOT EXISTS `oc_supplier_improvement_action` (
  `action_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `evaluation_id` int(11) DEFAULT NULL,
  `action_type` varchar(64) NOT NULL,
  `description` text NOT NULL,
  `target_improvement` decimal(5,2) NOT NULL DEFAULT '0.00',
  `assigned_to` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('planned','in_progress','completed','cancelled') NOT NULL DEFAULT 'planned',
  `actual_improvement` decimal(5,2) DEFAULT NULL,
  `notes` text,
  `created_date` datetime NOT NULL,
  PRIMARY KEY (`action_id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_evaluation_id` (`evaluation_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Performance benchmarks and targets
CREATE TABLE IF NOT EXISTS `oc_supplier_performance_target` (
  `target_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `metric_name` varchar(64) NOT NULL,
  `target_value` decimal(10,4) NOT NULL,
  `current_value` decimal(10,4) DEFAULT NULL,
  `measurement_unit` varchar(32) DEFAULT NULL,
  `target_period` varchar(32) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_date` datetime NOT NULL,
  PRIMARY KEY (`target_id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_metric_name` (`metric_name`),
  KEY `idx_target_period` (`target_period`),
  KEY `idx_date_range` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Foreign key constraints
ALTER TABLE `oc_supplier_performance_evaluation`
  ADD CONSTRAINT `fk_spe_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `oc_supplier` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_spe_evaluator` FOREIGN KEY (`evaluator_id`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT;

ALTER TABLE `oc_supplier_evaluation_criteria`
  ADD CONSTRAINT `fk_sec_evaluation` FOREIGN KEY (`evaluation_id`) REFERENCES `oc_supplier_performance_evaluation` (`evaluation_id`) ON DELETE CASCADE;

ALTER TABLE `oc_supplier_delivery_performance`
  ADD CONSTRAINT `fk_sdp_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `oc_supplier` (`supplier_id`) ON DELETE CASCADE;

ALTER TABLE `oc_supplier_quality_inspection`
  ADD CONSTRAINT `fk_sqi_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `oc_supplier` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sqi_product` FOREIGN KEY (`product_id`) REFERENCES `oc_product` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sqi_inspector` FOREIGN KEY (`inspector_id`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT;

ALTER TABLE `oc_supplier_cost_performance`
  ADD CONSTRAINT `fk_scp_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `oc_supplier` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_scp_currency` FOREIGN KEY (`currency_id`) REFERENCES `oc_currency` (`currency_id`) ON DELETE RESTRICT;

ALTER TABLE `oc_supplier_service_incident`
  ADD CONSTRAINT `fk_ssi_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `oc_supplier` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ssi_reporter` FOREIGN KEY (`reported_by`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT;

ALTER TABLE `oc_supplier_improvement_action`
  ADD CONSTRAINT `fk_sia_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `oc_supplier` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sia_evaluation` FOREIGN KEY (`evaluation_id`) REFERENCES `oc_supplier_performance_evaluation` (`evaluation_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sia_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `oc_user` (`user_id`) ON DELETE RESTRICT;

ALTER TABLE `oc_supplier_performance_target`
  ADD CONSTRAINT `fk_spt_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `oc_supplier` (`supplier_id`) ON DELETE CASCADE;

-- Sample data for testing
INSERT INTO `oc_supplier_performance_evaluation` (`supplier_id`, `evaluation_period`, `overall_score`, `delivery_score`, `quality_score`, `cost_score`, `service_score`, `evaluator_id`, `evaluation_date`) VALUES
(1, 'Q1-2024', 85.5, 90.0, 88.0, 82.0, 81.0, 1, '2024-03-31 10:00:00'),
(2, 'Q1-2024', 72.3, 75.0, 78.0, 68.0, 68.0, 1, '2024-03-31 10:30:00'),
(1, 'Q2-2024', 87.2, 92.0, 85.0, 85.0, 87.0, 1, '2024-06-30 10:00:00');

-- Performance calculation views
CREATE OR REPLACE VIEW v_supplier_performance_summary AS
SELECT 
    s.supplier_id,
    s.name as supplier_name,
    COUNT(spe.evaluation_id) as total_evaluations,
    AVG(spe.overall_score) as avg_overall_score,
    AVG(spe.delivery_score) as avg_delivery_score,
    AVG(spe.quality_score) as avg_quality_score,
    AVG(spe.cost_score) as avg_cost_score,
    AVG(spe.service_score) as avg_service_score,
    MAX(spe.evaluation_date) as last_evaluation_date,
    CASE 
        WHEN AVG(spe.overall_score) >= 80 THEN 'Excellent'
        WHEN AVG(spe.overall_score) >= 60 THEN 'Good'
        WHEN AVG(spe.overall_score) >= 40 THEN 'Average'
        ELSE 'Poor'
    END as performance_level
FROM oc_supplier s
LEFT JOIN oc_supplier_performance_evaluation spe ON s.supplier_id = spe.supplier_id
WHERE s.status = 1
GROUP BY s.supplier_id;

-- Indexes for performance optimization
CREATE INDEX idx_spe_supplier_date ON oc_supplier_performance_evaluation(supplier_id, evaluation_date);
CREATE INDEX idx_spe_score_range ON oc_supplier_performance_evaluation(overall_score, evaluation_date);
CREATE INDEX idx_sdp_supplier_status ON oc_supplier_delivery_performance(supplier_id, delivery_status);
CREATE INDEX idx_sqi_supplier_quality ON oc_supplier_quality_inspection(supplier_id, quality_status);

-- Triggers for automatic score calculation
DELIMITER $$

CREATE TRIGGER tr_delivery_score_update
AFTER INSERT ON oc_supplier_delivery_performance
FOR EACH ROW
BEGIN
    DECLARE delivery_score DECIMAL(5,2) DEFAULT 0;
    
    -- Calculate delivery score based on status
    CASE NEW.delivery_status
        WHEN 'on_time' THEN SET delivery_score = 100;
        WHEN 'early' THEN SET delivery_score = 95;
        WHEN 'late' THEN 
            SET delivery_score = GREATEST(0, 100 - (NEW.delay_days * 5));
        ELSE SET delivery_score = 0;
    END CASE;
    
    UPDATE oc_supplier_delivery_performance 
    SET delivery_score = delivery_score 
    WHERE delivery_performance_id = NEW.delivery_performance_id;
END$$

CREATE TRIGGER tr_quality_score_update
AFTER INSERT ON oc_supplier_quality_inspection
FOR EACH ROW
BEGIN
    DECLARE quality_score DECIMAL(5,2) DEFAULT 0;
    
    IF NEW.inspected_quantity > 0 THEN
        SET quality_score = (NEW.passed_quantity / NEW.inspected_quantity) * 100;
    END IF;
    
    UPDATE oc_supplier_quality_inspection 
    SET quality_score = quality_score 
    WHERE inspection_id = NEW.inspection_id;
END$$

DELIMITER ;
