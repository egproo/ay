-- =====================================================
-- جداول تحليلات المخزون (Inventory Analytics Tables)
-- =====================================================

-- جدول تحليلات المخزون اليومية
CREATE TABLE IF NOT EXISTS `cod_inventory_analytics_daily` (
  `analytics_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `analysis_date` date NOT NULL,
  `opening_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `closing_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `inbound_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `outbound_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `adjustment_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `average_cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `total_value` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `movement_count` int(11) NOT NULL DEFAULT '0',
  `turnover_ratio` decimal(10,4) DEFAULT NULL,
  `days_of_supply` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`analytics_id`),
  UNIQUE KEY `product_branch_unit_date` (`product_id`,`branch_id`,`unit_id`,`analysis_date`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_unit_id` (`unit_id`),
  KEY `idx_analysis_date` (`analysis_date`),
  KEY `idx_turnover_ratio` (`turnover_ratio`),
  KEY `idx_days_of_supply` (`days_of_supply`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='تحليلات المخزون اليومية';

-- جدول تصنيف ABC للمنتجات
CREATE TABLE IF NOT EXISTS `cod_product_abc_classification` (
  `classification_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `classification_period` varchar(20) NOT NULL COMMENT 'monthly, quarterly, yearly',
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `classification` enum('A','B','C') NOT NULL,
  `total_value_moved` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `movement_count` int(11) NOT NULL DEFAULT '0',
  `value_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `cumulative_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `rank_position` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`classification_id`),
  UNIQUE KEY `product_branch_period` (`product_id`,`branch_id`,`classification_period`,`period_start`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_classification` (`classification`),
  KEY `idx_period` (`classification_period`,`period_start`,`period_end`),
  KEY `idx_value_percentage` (`value_percentage`),
  KEY `idx_rank_position` (`rank_position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='تصنيف ABC للمنتجات';

-- جدول تحليل المنتجات بطيئة الحركة
CREATE TABLE IF NOT EXISTS `cod_slow_moving_analysis` (
  `analysis_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `analysis_date` date NOT NULL,
  `last_movement_date` date DEFAULT NULL,
  `days_since_movement` int(11) NOT NULL DEFAULT '0',
  `current_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `current_value` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `movement_count_30_days` int(11) NOT NULL DEFAULT '0',
  `movement_count_90_days` int(11) NOT NULL DEFAULT '0',
  `movement_count_180_days` int(11) NOT NULL DEFAULT '0',
  `movement_count_365_days` int(11) NOT NULL DEFAULT '0',
  `velocity_score` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'نقاط سرعة الحركة',
  `risk_level` enum('low','medium','high','critical') NOT NULL DEFAULT 'low',
  `recommended_action` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`analysis_id`),
  UNIQUE KEY `product_branch_unit_date` (`product_id`,`branch_id`,`unit_id`,`analysis_date`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_unit_id` (`unit_id`),
  KEY `idx_analysis_date` (`analysis_date`),
  KEY `idx_days_since_movement` (`days_since_movement`),
  KEY `idx_velocity_score` (`velocity_score`),
  KEY `idx_risk_level` (`risk_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='تحليل المنتجات بطيئة الحركة';

-- جدول تنبيهات المخزون
CREATE TABLE IF NOT EXISTS `cod_inventory_alerts` (
  `alert_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `alert_type` enum('out_of_stock','low_stock','overstock','slow_moving','expiry_warning') NOT NULL,
  `alert_level` enum('info','warning','critical') NOT NULL DEFAULT 'info',
  `current_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `threshold_quantity` decimal(15,4) DEFAULT NULL,
  `alert_message` text NOT NULL,
  `is_acknowledged` tinyint(1) NOT NULL DEFAULT '0',
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT '0',
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`alert_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_unit_id` (`unit_id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_alert_level` (`alert_level`),
  KEY `idx_is_acknowledged` (`is_acknowledged`),
  KEY `idx_is_resolved` (`is_resolved`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='تنبيهات المخزون';

-- جدول مؤشرات أداء المخزون
CREATE TABLE IF NOT EXISTS `cod_inventory_kpi` (
  `kpi_id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `kpi_date` date NOT NULL,
  `period_type` enum('daily','weekly','monthly','quarterly','yearly') NOT NULL,
  `total_products` int(11) NOT NULL DEFAULT '0',
  `total_value` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `turnover_ratio` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `stock_accuracy` decimal(5,2) NOT NULL DEFAULT '0.00',
  `fill_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `stockout_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `overstock_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `carrying_cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `obsolete_inventory_value` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `inventory_shrinkage` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `days_sales_outstanding` decimal(10,2) NOT NULL DEFAULT '0.00',
  `gross_margin_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`kpi_id`),
  UNIQUE KEY `branch_category_date_period` (`branch_id`,`category_id`,`kpi_date`,`period_type`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_kpi_date` (`kpi_date`),
  KEY `idx_period_type` (`period_type`),
  KEY `idx_turnover_ratio` (`turnover_ratio`),
  KEY `idx_stock_accuracy` (`stock_accuracy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='مؤشرات أداء المخزون';

-- جدول تحليل التقييم حسب الفئات
CREATE TABLE IF NOT EXISTS `cod_inventory_valuation_by_category` (
  `valuation_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `valuation_date` date NOT NULL,
  `product_count` int(11) NOT NULL DEFAULT '0',
  `total_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `total_value` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `average_cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `min_cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `max_cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `percentage_of_total_value` decimal(5,2) NOT NULL DEFAULT '0.00',
  `movement_value_30_days` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `movement_value_90_days` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `movement_value_365_days` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`valuation_id`),
  UNIQUE KEY `category_branch_date` (`category_id`,`branch_id`,`valuation_date`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_valuation_date` (`valuation_date`),
  KEY `idx_total_value` (`total_value`),
  KEY `idx_percentage_of_total_value` (`percentage_of_total_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='تحليل التقييم حسب الفئات';

-- جدول تتبع دقة المخزون
CREATE TABLE IF NOT EXISTS `cod_inventory_accuracy_tracking` (
  `tracking_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `check_date` date NOT NULL,
  `system_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `physical_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `variance_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `variance_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `variance_value` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `check_type` enum('cycle_count','full_inventory','spot_check','system_adjustment') NOT NULL,
  `checked_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `adjustment_made` tinyint(1) NOT NULL DEFAULT '0',
  `adjustment_reference` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tracking_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_unit_id` (`unit_id`),
  KEY `idx_check_date` (`check_date`),
  KEY `idx_check_type` (`check_type`),
  KEY `idx_variance_percentage` (`variance_percentage`),
  KEY `idx_checked_by` (`checked_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='تتبع دقة المخزون';

-- =====================================================
-- إجراءات مخزنة لتحليلات المخزون
-- =====================================================

DELIMITER //

-- إجراء لحساب تحليلات المخزون اليومية
CREATE PROCEDURE IF NOT EXISTS `sp_calculate_daily_inventory_analytics`(
    IN p_analysis_date DATE,
    IN p_branch_id INT
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_product_id INT;
    DECLARE v_unit_id INT;
    DECLARE v_opening_qty DECIMAL(15,4);
    DECLARE v_closing_qty DECIMAL(15,4);
    DECLARE v_inbound_qty DECIMAL(15,4);
    DECLARE v_outbound_qty DECIMAL(15,4);
    DECLARE v_adjustment_qty DECIMAL(15,4);
    DECLARE v_avg_cost DECIMAL(15,4);
    DECLARE v_movement_count INT;
    
    DECLARE product_cursor CURSOR FOR
        SELECT DISTINCT pi.product_id, pi.unit_id
        FROM cod_product_inventory pi
        WHERE pi.branch_id = p_branch_id OR p_branch_id = 0;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN product_cursor;
    
    product_loop: LOOP
        FETCH product_cursor INTO v_product_id, v_unit_id;
        IF done THEN
            LEAVE product_loop;
        END IF;
        
        -- حساب الكميات والحركات
        SELECT 
            COALESCE(pi.quantity, 0),
            COALESCE(pi.average_cost, 0)
        INTO v_closing_qty, v_avg_cost
        FROM cod_product_inventory pi
        WHERE pi.product_id = v_product_id 
        AND pi.branch_id = p_branch_id 
        AND pi.unit_id = v_unit_id;
        
        -- حساب الحركات اليومية
        SELECT 
            COALESCE(SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE 0 END), 0),
            COALESCE(SUM(CASE WHEN movement_type = 'out' THEN quantity ELSE 0 END), 0),
            COALESCE(SUM(CASE WHEN movement_type = 'adjustment' THEN quantity ELSE 0 END), 0),
            COUNT(*)
        INTO v_inbound_qty, v_outbound_qty, v_adjustment_qty, v_movement_count
        FROM cod_inventory_movement
        WHERE product_id = v_product_id 
        AND branch_id = p_branch_id 
        AND unit_id = v_unit_id
        AND DATE(created_at) = p_analysis_date;
        
        -- حساب الكمية الافتتاحية
        SET v_opening_qty = v_closing_qty - v_inbound_qty + v_outbound_qty - v_adjustment_qty;
        
        -- إدراج أو تحديث السجل
        INSERT INTO cod_inventory_analytics_daily (
            product_id, branch_id, unit_id, analysis_date,
            opening_quantity, closing_quantity, inbound_quantity, 
            outbound_quantity, adjustment_quantity, average_cost,
            total_value, movement_count
        ) VALUES (
            v_product_id, p_branch_id, v_unit_id, p_analysis_date,
            v_opening_qty, v_closing_qty, v_inbound_qty,
            v_outbound_qty, v_adjustment_qty, v_avg_cost,
            v_closing_qty * v_avg_cost, v_movement_count
        ) ON DUPLICATE KEY UPDATE
            opening_quantity = v_opening_qty,
            closing_quantity = v_closing_qty,
            inbound_quantity = v_inbound_qty,
            outbound_quantity = v_outbound_qty,
            adjustment_quantity = v_adjustment_qty,
            average_cost = v_avg_cost,
            total_value = v_closing_qty * v_avg_cost,
            movement_count = v_movement_count,
            updated_at = NOW();
            
    END LOOP;
    
    CLOSE product_cursor;
END //

-- إجراء لحساب تصنيف ABC
CREATE PROCEDURE IF NOT EXISTS `sp_calculate_abc_classification`(
    IN p_period_start DATE,
    IN p_period_end DATE,
    IN p_branch_id INT
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_product_id INT;
    DECLARE v_total_value DECIMAL(15,4);
    DECLARE v_movement_count INT;
    DECLARE v_cumulative_value DECIMAL(15,4) DEFAULT 0;
    DECLARE v_grand_total DECIMAL(15,4);
    DECLARE v_cumulative_percentage DECIMAL(5,2);
    DECLARE v_classification CHAR(1);
    DECLARE v_rank INT DEFAULT 0;
    
    -- حساب إجمالي القيمة
    SELECT SUM(quantity * cost) INTO v_grand_total
    FROM cod_inventory_movement
    WHERE DATE(created_at) BETWEEN p_period_start AND p_period_end
    AND (branch_id = p_branch_id OR p_branch_id = 0);
    
    DECLARE product_cursor CURSOR FOR
        SELECT 
            product_id,
            SUM(quantity * cost) as total_value,
            COUNT(*) as movement_count
        FROM cod_inventory_movement
        WHERE DATE(created_at) BETWEEN p_period_start AND p_period_end
        AND (branch_id = p_branch_id OR p_branch_id = 0)
        GROUP BY product_id
        ORDER BY total_value DESC;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN product_cursor;
    
    classification_loop: LOOP
        FETCH product_cursor INTO v_product_id, v_total_value, v_movement_count;
        IF done THEN
            LEAVE classification_loop;
        END IF;
        
        SET v_rank = v_rank + 1;
        SET v_cumulative_value = v_cumulative_value + v_total_value;
        SET v_cumulative_percentage = (v_cumulative_value / v_grand_total) * 100;
        
        -- تحديد التصنيف
        IF v_cumulative_percentage <= 80 THEN
            SET v_classification = 'A';
        ELSEIF v_cumulative_percentage <= 95 THEN
            SET v_classification = 'B';
        ELSE
            SET v_classification = 'C';
        END IF;
        
        -- إدراج أو تحديث التصنيف
        INSERT INTO cod_product_abc_classification (
            product_id, branch_id, classification_period, period_start, period_end,
            classification, total_value_moved, movement_count, 
            value_percentage, cumulative_percentage, rank_position
        ) VALUES (
            v_product_id, p_branch_id, 'custom', p_period_start, p_period_end,
            v_classification, v_total_value, v_movement_count,
            (v_total_value / v_grand_total) * 100, v_cumulative_percentage, v_rank
        ) ON DUPLICATE KEY UPDATE
            classification = v_classification,
            total_value_moved = v_total_value,
            movement_count = v_movement_count,
            value_percentage = (v_total_value / v_grand_total) * 100,
            cumulative_percentage = v_cumulative_percentage,
            rank_position = v_rank,
            updated_at = NOW();
            
    END LOOP;
    
    CLOSE product_cursor;
END //

DELIMITER ;

-- =====================================================
-- فهارس إضافية لتحسين الأداء
-- =====================================================

-- فهارس مركبة لتحسين استعلامات التحليلات
CREATE INDEX idx_inventory_analytics_product_date ON cod_inventory_analytics_daily(product_id, analysis_date);
CREATE INDEX idx_inventory_analytics_branch_date ON cod_inventory_analytics_daily(branch_id, analysis_date);
CREATE INDEX idx_abc_classification_period ON cod_product_abc_classification(classification_period, period_start, period_end);
CREATE INDEX idx_slow_moving_risk_level ON cod_slow_moving_analysis(risk_level, days_since_movement);
CREATE INDEX idx_inventory_alerts_type_level ON cod_inventory_alerts(alert_type, alert_level, is_resolved);
CREATE INDEX idx_inventory_kpi_period_date ON cod_inventory_kpi(period_type, kpi_date);

-- =====================================================
-- تعليقات وتوثيق
-- =====================================================

/*
هذا الملف يحتوي على جداول تحليلات المخزون المطلوبة لشاشة dashboard/inventory_analytics

الجداول الرئيسية:
1. cod_inventory_analytics_daily - تحليلات يومية للمخزون
2. cod_product_abc_classification - تصنيف ABC للمنتجات
3. cod_slow_moving_analysis - تحليل المنتجات بطيئة الحركة
4. cod_inventory_alerts - تنبيهات المخزون
5. cod_inventory_kpi - مؤشرات أداء المخزون
6. cod_inventory_valuation_by_category - تحليل التقييم حسب الفئات
7. cod_inventory_accuracy_tracking - تتبع دقة المخزون

الإجراءات المخزنة:
1. sp_calculate_daily_inventory_analytics - حساب التحليلات اليومية
2. sp_calculate_abc_classification - حساب تصنيف ABC

هذه الجداول تدعم:
- تحليل اتجاهات الحركة
- مستويات المخزون والتنبيهات
- تحليل التقييم حسب الفئات
- تصنيف ABC (تحليل باريتو)
- تحليل المنتجات بطيئة الحركة
- مؤشرات أداء المخزون
- تتبع دقة المخزون
*/
