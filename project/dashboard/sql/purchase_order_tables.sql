-- جدول طلبات الشراء
CREATE TABLE IF NOT EXISTS `oc_purchase_order` (
  `purchase_order_id` int(11) NOT NULL AUTO_INCREMENT,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `expected_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, pending, approved, ordered, partial, received, cancelled',
  `payment_terms` varchar(255) DEFAULT NULL,
  `delivery_terms` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_by` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `date_approved` datetime DEFAULT NULL,
  PRIMARY KEY (`purchase_order_id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `supplier_id` (`supplier_id`),
  KEY `branch_id` (`branch_id`),
  KEY `order_date` (`order_date`),
  KEY `expected_date` (`expected_date`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`),
  KEY `approved_by` (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- جدول منتجات طلبات الشراء
CREATE TABLE IF NOT EXISTS `oc_purchase_order_product` (
  `purchase_order_product_id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `received_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `unit_price` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `tax_rate` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `discount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `notes` text,
  PRIMARY KEY (`purchase_order_product_id`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `product_id` (`product_id`),
  KEY `unit_id` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- جدول تاريخ استلام طلبات الشراء
CREATE TABLE IF NOT EXISTS `oc_purchase_order_receipt` (
  `receipt_id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(11) NOT NULL,
  `receipt_date` date NOT NULL,
  `notes` text,
  `created_by` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`receipt_id`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `receipt_date` (`receipt_date`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- جدول تفاصيل استلام طلبات الشراء
CREATE TABLE IF NOT EXISTS `oc_purchase_order_receipt_product` (
  `receipt_product_id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_id` int(11) NOT NULL,
  `purchase_order_product_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `batch_number` varchar(50) DEFAULT NULL,
  `manufacturing_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`receipt_product_id`),
  KEY `receipt_id` (`receipt_id`),
  KEY `purchase_order_product_id` (`purchase_order_product_id`),
  KEY `product_id` (`product_id`),
  KEY `unit_id` (`unit_id`),
  KEY `batch_number` (`batch_number`),
  KEY `manufacturing_date` (`manufacturing_date`),
  KEY `expiry_date` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- إجراء مخزن لاعتماد طلب الشراء
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `sp_approve_purchase_order`(
    IN p_purchase_order_id INT,
    IN p_user_id INT
)
BEGIN
    DECLARE v_status VARCHAR(20);
    
    -- الحصول على حالة طلب الشراء
    SELECT status
    INTO v_status
    FROM `oc_purchase_order`
    WHERE purchase_order_id = p_purchase_order_id;
    
    -- التحقق من أن طلب الشراء في حالة معلق
    IF v_status = 'pending' THEN
        -- تحديث حالة طلب الشراء إلى معتمد
        UPDATE `oc_purchase_order`
        SET status = 'approved',
            approved_by = p_user_id,
            date_approved = NOW()
        WHERE purchase_order_id = p_purchase_order_id;
    END IF;
END //
DELIMITER ;

-- إجراء مخزن لاستلام طلب الشراء
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `sp_receive_purchase_order`(
    IN p_purchase_order_id INT,
    IN p_receipt_date DATE,
    IN p_notes TEXT,
    IN p_user_id INT,
    IN p_receipt_products JSON
)
BEGIN
    DECLARE v_receipt_id INT;
    DECLARE v_branch_id INT;
    DECLARE v_status VARCHAR(20);
    DECLARE v_total_quantity DECIMAL(15,4);
    DECLARE v_total_received DECIMAL(15,4);
    
    -- الحصول على معلومات طلب الشراء
    SELECT branch_id, status
    INTO v_branch_id, v_status
    FROM `oc_purchase_order`
    WHERE purchase_order_id = p_purchase_order_id;
    
    -- التحقق من أن طلب الشراء في حالة معتمد أو تم الطلب أو استلام جزئي
    IF v_status IN ('approved', 'ordered', 'partial') THEN
        -- إنشاء سجل استلام جديد
        INSERT INTO `oc_purchase_order_receipt` (
            purchase_order_id,
            receipt_date,
            notes,
            created_by,
            date_added
        ) VALUES (
            p_purchase_order_id,
            p_receipt_date,
            p_notes,
            p_user_id,
            NOW()
        );
        
        SET v_receipt_id = LAST_INSERT_ID();
        
        -- إضافة منتجات الاستلام
        -- يتم معالجة JSON في تطبيق PHP وإدخال السجلات هنا
        
        -- تحديث إجمالي الكميات المستلمة
        SELECT SUM(quantity), SUM(received_quantity)
        INTO v_total_quantity, v_total_received
        FROM `oc_purchase_order_product`
        WHERE purchase_order_id = p_purchase_order_id;
        
        -- تحديث حالة طلب الشراء
        IF v_total_received >= v_total_quantity THEN
            -- تم استلام جميع المنتجات
            UPDATE `oc_purchase_order`
            SET status = 'received'
            WHERE purchase_order_id = p_purchase_order_id;
        ELSE
            -- تم استلام بعض المنتجات
            UPDATE `oc_purchase_order`
            SET status = 'partial'
            WHERE purchase_order_id = p_purchase_order_id;
        END IF;
    END IF;
END //
DELIMITER ;

-- إجراء مخزن لإلغاء طلب الشراء
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `sp_cancel_purchase_order`(
    IN p_purchase_order_id INT
)
BEGIN
    DECLARE v_status VARCHAR(20);
    
    -- الحصول على حالة طلب الشراء
    SELECT status
    INTO v_status
    FROM `oc_purchase_order`
    WHERE purchase_order_id = p_purchase_order_id;
    
    -- التحقق من أن طلب الشراء في حالة مسودة أو معلق أو معتمد
    IF v_status IN ('draft', 'pending', 'approved') THEN
        -- تحديث حالة طلب الشراء إلى ملغي
        UPDATE `oc_purchase_order`
        SET status = 'cancelled'
        WHERE purchase_order_id = p_purchase_order_id;
    END IF;
END //
DELIMITER ;
