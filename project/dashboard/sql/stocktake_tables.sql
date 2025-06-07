-- جدول الجرد
CREATE TABLE IF NOT EXISTS `oc_stocktake` (
  `stocktake_id` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(50) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `stocktake_date` date NOT NULL,
  `type` varchar(20) NOT NULL COMMENT 'full, partial, spot, cycle',
  `status` varchar(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, in_progress, completed, cancelled',
  `notes` text,
  `created_by` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `date_completed` datetime DEFAULT NULL,
  PRIMARY KEY (`stocktake_id`),
  KEY `reference` (`reference`),
  KEY `branch_id` (`branch_id`),
  KEY `stocktake_date` (`stocktake_date`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`),
  KEY `completed_by` (`completed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- جدول منتجات الجرد
CREATE TABLE IF NOT EXISTS `oc_stocktake_product` (
  `stocktake_product_id` int(11) NOT NULL AUTO_INCREMENT,
  `stocktake_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `expected_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `counted_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `variance_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `notes` text,
  PRIMARY KEY (`stocktake_product_id`),
  KEY `stocktake_id` (`stocktake_id`),
  KEY `product_id` (`product_id`),
  KEY `unit_id` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- إجراء مخزن لإكمال عملية الجرد
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `sp_complete_stocktake`(
    IN p_stocktake_id INT,
    IN p_user_id INT
)
BEGIN
    DECLARE v_branch_id INT;
    DECLARE v_status VARCHAR(20);
    
    -- الحصول على معلومات الجرد
    SELECT branch_id, status
    INTO v_branch_id, v_status
    FROM `oc_stocktake`
    WHERE stocktake_id = p_stocktake_id;
    
    -- التحقق من أن الجرد في حالة قيد التنفيذ
    IF v_status = 'in_progress' THEN
        -- تحديث حالة الجرد إلى مكتمل
        UPDATE `oc_stocktake`
        SET status = 'completed',
            completed_by = p_user_id,
            date_completed = NOW()
        WHERE stocktake_id = p_stocktake_id;
        
        -- تعديل المخزون بناءً على نتائج الجرد
        INSERT INTO `oc_inventory_movement` (
            product_id,
            branch_id,
            unit_id,
            movement_type,
            reference_type,
            reference_id,
            quantity,
            cost,
            notes,
            created_by,
            created_at
        )
        SELECT 
            sp.product_id,
            v_branch_id,
            sp.unit_id,
            CASE WHEN sp.variance_quantity > 0 THEN 'in' ELSE 'out' END,
            'stocktake',
            p_stocktake_id,
            ABS(sp.variance_quantity),
            0.0000,
            'Stocktake adjustment',
            p_user_id,
            NOW()
        FROM `oc_stocktake_product` sp
        WHERE sp.stocktake_id = p_stocktake_id
        AND sp.variance_quantity <> 0;
        
        -- تحديث المخزون
        UPDATE `oc_product_inventory` pi
        JOIN `oc_stocktake_product` sp ON (pi.product_id = sp.product_id AND pi.unit_id = sp.unit_id)
        SET pi.quantity = sp.counted_quantity
        WHERE sp.stocktake_id = p_stocktake_id
        AND pi.branch_id = v_branch_id;
        
        -- إدخال المنتجات التي لم تكن موجودة في المخزون
        INSERT INTO `oc_product_inventory` (
            product_id,
            branch_id,
            unit_id,
            quantity,
            average_cost
        )
        SELECT 
            sp.product_id,
            v_branch_id,
            sp.unit_id,
            sp.counted_quantity,
            0.0000
        FROM `oc_stocktake_product` sp
        LEFT JOIN `oc_product_inventory` pi ON (sp.product_id = pi.product_id AND pi.branch_id = v_branch_id AND sp.unit_id = pi.unit_id)
        WHERE sp.stocktake_id = p_stocktake_id
        AND pi.product_id IS NULL
        AND sp.counted_quantity > 0;
    END IF;
END //
DELIMITER ;

-- إجراء مخزن لإلغاء عملية الجرد
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `sp_cancel_stocktake`(
    IN p_stocktake_id INT,
    IN p_user_id INT
)
BEGIN
    DECLARE v_status VARCHAR(20);
    
    -- الحصول على حالة الجرد
    SELECT status
    INTO v_status
    FROM `oc_stocktake`
    WHERE stocktake_id = p_stocktake_id;
    
    -- التحقق من أن الجرد في حالة مسودة أو قيد التنفيذ
    IF v_status IN ('draft', 'in_progress') THEN
        -- تحديث حالة الجرد إلى ملغي
        UPDATE `oc_stocktake`
        SET status = 'cancelled'
        WHERE stocktake_id = p_stocktake_id;
    END IF;
END //
DELIMITER ;
