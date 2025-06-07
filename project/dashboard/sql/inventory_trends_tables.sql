-- جدول حركة المخزون
CREATE TABLE IF NOT EXISTS `oc_inventory_movement` (
  `movement_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `movement_type` varchar(20) NOT NULL COMMENT 'in, out, adjustment, transfer',
  `reference_type` varchar(50) NOT NULL COMMENT 'purchase, sale, adjustment, transfer, etc.',
  `reference_id` int(11) NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `notes` text,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`movement_id`),
  KEY `product_id` (`product_id`),
  KEY `branch_id` (`branch_id`),
  KEY `unit_id` (`unit_id`),
  KEY `movement_type` (`movement_type`),
  KEY `reference_type` (`reference_type`),
  KEY `reference_id` (`reference_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- جدول تاريخ المخزون (للاستخدام في حساب متوسط المخزون)
CREATE TABLE IF NOT EXISTS `oc_product_inventory_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `average_cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `date` date NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`history_id`),
  UNIQUE KEY `product_branch_unit_date` (`product_id`,`branch_id`,`unit_id`,`date`),
  KEY `product_id` (`product_id`),
  KEY `branch_id` (`branch_id`),
  KEY `unit_id` (`unit_id`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- إجراء مخزن لتسجيل حركة المخزون
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `sp_record_inventory_movement`(
    IN p_product_id INT,
    IN p_branch_id INT,
    IN p_unit_id INT,
    IN p_movement_type VARCHAR(20),
    IN p_reference_type VARCHAR(50),
    IN p_reference_id INT,
    IN p_quantity DECIMAL(15,4),
    IN p_cost DECIMAL(15,4),
    IN p_notes TEXT,
    IN p_user_id INT
)
BEGIN
    -- إدخال سجل في جدول حركة المخزون
    INSERT INTO `oc_inventory_movement` (
        `product_id`,
        `branch_id`,
        `unit_id`,
        `movement_type`,
        `reference_type`,
        `reference_id`,
        `quantity`,
        `cost`,
        `notes`,
        `created_by`,
        `created_at`
    ) VALUES (
        p_product_id,
        p_branch_id,
        p_unit_id,
        p_movement_type,
        p_reference_type,
        p_reference_id,
        p_quantity,
        p_cost,
        p_notes,
        p_user_id,
        NOW()
    );
    
    -- تحديث جدول المخزون
    IF p_movement_type = 'in' THEN
        -- إذا كان الحركة واردة
        INSERT INTO `oc_product_inventory` (
            `product_id`,
            `branch_id`,
            `unit_id`,
            `quantity`,
            `average_cost`
        ) VALUES (
            p_product_id,
            p_branch_id,
            p_unit_id,
            p_quantity,
            p_cost
        ) ON DUPLICATE KEY UPDATE
            `quantity` = `quantity` + p_quantity,
            `average_cost` = ((`quantity` * `average_cost`) + (p_quantity * p_cost)) / (`quantity` + p_quantity);
    ELSEIF p_movement_type = 'out' THEN
        -- إذا كان الحركة صادرة
        UPDATE `oc_product_inventory`
        SET `quantity` = `quantity` - p_quantity
        WHERE `product_id` = p_product_id
        AND `branch_id` = p_branch_id
        AND `unit_id` = p_unit_id;
    ELSEIF p_movement_type = 'adjustment' THEN
        -- إذا كان الحركة تعديل
        IF p_quantity >= 0 THEN
            -- زيادة المخزون
            INSERT INTO `oc_product_inventory` (
                `product_id`,
                `branch_id`,
                `unit_id`,
                `quantity`,
                `average_cost`
            ) VALUES (
                p_product_id,
                p_branch_id,
                p_unit_id,
                p_quantity,
                p_cost
            ) ON DUPLICATE KEY UPDATE
                `quantity` = `quantity` + p_quantity,
                `average_cost` = ((`quantity` * `average_cost`) + (p_quantity * p_cost)) / (`quantity` + p_quantity);
        ELSE
            -- نقص المخزون
            UPDATE `oc_product_inventory`
            SET `quantity` = `quantity` + p_quantity
            WHERE `product_id` = p_product_id
            AND `branch_id` = p_branch_id
            AND `unit_id` = p_unit_id;
        END IF;
    END IF;
    
    -- تسجيل تاريخ المخزون
    INSERT INTO `oc_product_inventory_history` (
        `product_id`,
        `branch_id`,
        `unit_id`,
        `quantity`,
        `average_cost`,
        `date`,
        `created_at`
    ) SELECT
        `product_id`,
        `branch_id`,
        `unit_id`,
        `quantity`,
        `average_cost`,
        CURDATE(),
        NOW()
    FROM `oc_product_inventory`
    WHERE `product_id` = p_product_id
    AND `branch_id` = p_branch_id
    AND `unit_id` = p_unit_id
    ON DUPLICATE KEY UPDATE
        `quantity` = VALUES(`quantity`),
        `average_cost` = VALUES(`average_cost`),
        `created_at` = VALUES(`created_at`);
END //
DELIMITER ;

-- إجراء مخزن لحساب معدل دوران المخزون
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `sp_calculate_turnover_rate`(
    IN p_product_id INT,
    IN p_branch_id INT,
    IN p_unit_id INT,
    IN p_date_start DATE,
    IN p_date_end DATE,
    OUT p_turnover_rate DECIMAL(15,4),
    OUT p_days_on_hand DECIMAL(15,4)
)
BEGIN
    DECLARE v_total_out DECIMAL(15,4);
    DECLARE v_average_inventory DECIMAL(15,4);
    DECLARE v_days_in_period INT;
    
    -- حساب إجمالي الصادر
    SELECT COALESCE(SUM(CASE WHEN movement_type = 'out' THEN quantity ELSE 0 END), 0)
    INTO v_total_out
    FROM `oc_inventory_movement`
    WHERE product_id = p_product_id
    AND branch_id = p_branch_id
    AND unit_id = p_unit_id
    AND created_at BETWEEN CONCAT(p_date_start, ' 00:00:00') AND CONCAT(p_date_end, ' 23:59:59');
    
    -- حساب متوسط المخزون
    SELECT COALESCE(AVG(quantity), 0)
    INTO v_average_inventory
    FROM `oc_product_inventory_history`
    WHERE product_id = p_product_id
    AND branch_id = p_branch_id
    AND unit_id = p_unit_id
    AND date BETWEEN p_date_start AND p_date_end;
    
    -- حساب عدد الأيام في الفترة
    SET v_days_in_period = DATEDIFF(p_date_end, p_date_start) + 1;
    
    -- حساب معدل الدوران
    IF v_average_inventory > 0 THEN
        SET p_turnover_rate = v_total_out / v_average_inventory;
    ELSE
        SET p_turnover_rate = 0;
    END IF;
    
    -- حساب أيام التخزين
    IF p_turnover_rate > 0 THEN
        SET p_days_on_hand = v_days_in_period / p_turnover_rate;
    ELSE
        SET p_days_on_hand = 0;
    END IF;
END //
DELIMITER ;
