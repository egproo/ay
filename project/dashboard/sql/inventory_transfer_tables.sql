-- جدول تحويلات المخزون
CREATE TABLE IF NOT EXISTS `oc_inventory_transfer` (
  `transfer_id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_number` varchar(50) NOT NULL,
  `from_branch_id` int(11) NOT NULL,
  `to_branch_id` int(11) NOT NULL,
  `transfer_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, confirmed, in_transit, completed, cancelled, rejected',
  `notes` text,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`transfer_id`),
  KEY `reference_number` (`reference_number`),
  KEY `from_branch_id` (`from_branch_id`),
  KEY `to_branch_id` (`to_branch_id`),
  KEY `transfer_date` (`transfer_date`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- جدول تفاصيل تحويلات المخزون
CREATE TABLE IF NOT EXISTS `oc_inventory_transfer_product` (
  `transfer_product_id` int(11) NOT NULL AUTO_INCREMENT,
  `transfer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `received_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `notes` text,
  PRIMARY KEY (`transfer_product_id`),
  KEY `transfer_id` (`transfer_id`),
  KEY `product_id` (`product_id`),
  KEY `unit_id` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- جدول تاريخ تحويلات المخزون
CREATE TABLE IF NOT EXISTS `oc_inventory_transfer_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `transfer_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `notes` text,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`history_id`),
  KEY `transfer_id` (`transfer_id`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- إجراء مخزن لتأكيد تحويل المخزون
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `sp_confirm_inventory_transfer`(
    IN p_transfer_id INT,
    IN p_user_id INT,
    IN p_notes TEXT
)
BEGIN
    DECLARE v_from_branch_id INT;
    DECLARE v_to_branch_id INT;
    DECLARE v_status VARCHAR(20);
    
    -- الحصول على معلومات التحويل
    SELECT from_branch_id, to_branch_id, status
    INTO v_from_branch_id, v_to_branch_id, v_status
    FROM `oc_inventory_transfer`
    WHERE transfer_id = p_transfer_id;
    
    -- التحقق من أن التحويل في حالة معلق
    IF v_status = 'pending' THEN
        -- تحديث حالة التحويل إلى مؤكد
        UPDATE `oc_inventory_transfer`
        SET status = 'confirmed',
            modified_by = p_user_id,
            modified_at = NOW()
        WHERE transfer_id = p_transfer_id;
        
        -- إضافة سجل في تاريخ التحويل
        INSERT INTO `oc_inventory_transfer_history` (
            transfer_id,
            status,
            notes,
            created_by,
            created_at
        ) VALUES (
            p_transfer_id,
            'confirmed',
            p_notes,
            p_user_id,
            NOW()
        );
        
        -- خصم الكمية من المخزون في الفرع المصدر
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
            tp.product_id,
            v_from_branch_id,
            tp.unit_id,
            'out',
            'transfer',
            p_transfer_id,
            tp.quantity,
            tp.cost,
            CONCAT('Transfer to branch ID: ', v_to_branch_id),
            p_user_id,
            NOW()
        FROM `oc_inventory_transfer_product` tp
        WHERE tp.transfer_id = p_transfer_id;
        
        -- تحديث المخزون في الفرع المصدر
        UPDATE `oc_product_inventory` pi
        JOIN `oc_inventory_transfer_product` tp ON (pi.product_id = tp.product_id AND pi.unit_id = tp.unit_id)
        SET pi.quantity = pi.quantity - tp.quantity
        WHERE tp.transfer_id = p_transfer_id
        AND pi.branch_id = v_from_branch_id;
    END IF;
END //
DELIMITER ;

-- إجراء مخزن لإكمال تحويل المخزون
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `sp_complete_inventory_transfer`(
    IN p_transfer_id INT,
    IN p_user_id INT,
    IN p_notes TEXT
)
BEGIN
    DECLARE v_from_branch_id INT;
    DECLARE v_to_branch_id INT;
    DECLARE v_status VARCHAR(20);
    
    -- الحصول على معلومات التحويل
    SELECT from_branch_id, to_branch_id, status
    INTO v_from_branch_id, v_to_branch_id, v_status
    FROM `oc_inventory_transfer`
    WHERE transfer_id = p_transfer_id;
    
    -- التحقق من أن التحويل في حالة قيد النقل
    IF v_status = 'in_transit' THEN
        -- تحديث حالة التحويل إلى مكتمل
        UPDATE `oc_inventory_transfer`
        SET status = 'completed',
            modified_by = p_user_id,
            modified_at = NOW()
        WHERE transfer_id = p_transfer_id;
        
        -- إضافة سجل في تاريخ التحويل
        INSERT INTO `oc_inventory_transfer_history` (
            transfer_id,
            status,
            notes,
            created_by,
            created_at
        ) VALUES (
            p_transfer_id,
            'completed',
            p_notes,
            p_user_id,
            NOW()
        );
        
        -- إضافة الكمية إلى المخزون في الفرع المستلم
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
            tp.product_id,
            v_to_branch_id,
            tp.unit_id,
            'in',
            'transfer',
            p_transfer_id,
            tp.quantity,
            tp.cost,
            CONCAT('Transfer from branch ID: ', v_from_branch_id),
            p_user_id,
            NOW()
        FROM `oc_inventory_transfer_product` tp
        WHERE tp.transfer_id = p_transfer_id;
        
        -- تحديث المخزون في الفرع المستلم
        INSERT INTO `oc_product_inventory` (
            product_id,
            branch_id,
            unit_id,
            quantity,
            average_cost
        )
        SELECT 
            tp.product_id,
            v_to_branch_id,
            tp.unit_id,
            tp.quantity,
            tp.cost
        FROM `oc_inventory_transfer_product` tp
        WHERE tp.transfer_id = p_transfer_id
        ON DUPLICATE KEY UPDATE
            quantity = quantity + VALUES(quantity),
            average_cost = (quantity * average_cost + VALUES(quantity) * VALUES(average_cost)) / (quantity + VALUES(quantity));
        
        -- تحديث الكمية المستلمة
        UPDATE `oc_inventory_transfer_product`
        SET received_quantity = quantity
        WHERE transfer_id = p_transfer_id;
    END IF;
END //
DELIMITER ;

-- إجراء مخزن لإلغاء تحويل المخزون
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `sp_cancel_inventory_transfer`(
    IN p_transfer_id INT,
    IN p_user_id INT,
    IN p_notes TEXT
)
BEGIN
    DECLARE v_from_branch_id INT;
    DECLARE v_status VARCHAR(20);
    
    -- الحصول على معلومات التحويل
    SELECT from_branch_id, status
    INTO v_from_branch_id, v_status
    FROM `oc_inventory_transfer`
    WHERE transfer_id = p_transfer_id;
    
    -- التحقق من أن التحويل في حالة معلق أو مؤكد
    IF v_status IN ('pending', 'confirmed') THEN
        -- تحديث حالة التحويل إلى ملغي
        UPDATE `oc_inventory_transfer`
        SET status = 'cancelled',
            modified_by = p_user_id,
            modified_at = NOW()
        WHERE transfer_id = p_transfer_id;
        
        -- إضافة سجل في تاريخ التحويل
        INSERT INTO `oc_inventory_transfer_history` (
            transfer_id,
            status,
            notes,
            created_by,
            created_at
        ) VALUES (
            p_transfer_id,
            'cancelled',
            p_notes,
            p_user_id,
            NOW()
        );
        
        -- إذا كان التحويل مؤكدًا، قم بإعادة الكمية إلى المخزون في الفرع المصدر
        IF v_status = 'confirmed' THEN
            -- إضافة الكمية إلى المخزون في الفرع المصدر
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
                tp.product_id,
                v_from_branch_id,
                tp.unit_id,
                'in',
                'transfer_cancel',
                p_transfer_id,
                tp.quantity,
                tp.cost,
                'Transfer cancelled',
                p_user_id,
                NOW()
            FROM `oc_inventory_transfer_product` tp
            WHERE tp.transfer_id = p_transfer_id;
            
            -- تحديث المخزون في الفرع المصدر
            UPDATE `oc_product_inventory` pi
            JOIN `oc_inventory_transfer_product` tp ON (pi.product_id = tp.product_id AND pi.unit_id = tp.unit_id)
            SET pi.quantity = pi.quantity + tp.quantity
            WHERE tp.transfer_id = p_transfer_id
            AND pi.branch_id = v_from_branch_id;
        END IF;
    END IF;
END //
DELIMITER ;
