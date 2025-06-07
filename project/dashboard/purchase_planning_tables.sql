-- جداول تخطيط المشتريات
-- يجب تنفيذ هذه الأوامر في phpMyAdmin

-- جدول خطط الشراء
CREATE TABLE `cod_purchase_plan` (
  `plan_id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'اسم الخطة',
  `plan_description` text COLLATE utf8mb4_general_ci COMMENT 'وصف الخطة',
  `plan_period` enum('monthly','quarterly','yearly','custom') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'monthly' COMMENT 'فترة الخطة',
  `start_date` date NOT NULL COMMENT 'تاريخ البداية',
  `end_date` date NOT NULL COMMENT 'تاريخ النهاية',
  `total_budget` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'إجمالي الميزانية',
  `used_budget` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'الميزانية المستخدمة',
  `status` enum('draft','active','completed','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft' COMMENT 'حالة الخطة',
  `notes` text COLLATE utf8mb4_general_ci COMMENT 'ملاحظات',
  `created_by` int(11) NOT NULL COMMENT 'منشئ الخطة',
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'تاريخ الإضافة',
  `modified_by` int(11) DEFAULT NULL COMMENT 'معدل الخطة',
  `date_modified` datetime DEFAULT NULL COMMENT 'تاريخ التعديل',
  PRIMARY KEY (`plan_id`),
  KEY `plan_name` (`plan_name`),
  KEY `plan_period` (`plan_period`),
  KEY `start_date` (`start_date`),
  KEY `end_date` (`end_date`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`),
  KEY `modified_by` (`modified_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='خطط الشراء';

-- جدول عناصر خطة الشراء
CREATE TABLE `cod_purchase_plan_item` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL COMMENT 'معرف الخطة',
  `product_id` int(11) NOT NULL COMMENT 'معرف المنتج',
  `category_id` int(11) DEFAULT NULL COMMENT 'معرف الفئة',
  `planned_quantity` decimal(15,4) NOT NULL COMMENT 'الكمية المخططة',
  `purchased_quantity` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'الكمية المشتراة',
  `estimated_price` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'السعر المقدر',
  `actual_price` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'السعر الفعلي',
  `total_amount` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'إجمالي المبلغ',
  `priority` enum('high','medium','low') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'medium' COMMENT 'الأولوية',
  `target_date` date DEFAULT NULL COMMENT 'التاريخ المستهدف',
  `notes` text COLLATE utf8mb4_general_ci COMMENT 'ملاحظات',
  `status` enum('pending','ordered','received','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending' COMMENT 'حالة العنصر',
  PRIMARY KEY (`item_id`),
  KEY `plan_id` (`plan_id`),
  KEY `product_id` (`product_id`),
  KEY `category_id` (`category_id`),
  KEY `priority` (`priority`),
  KEY `status` (`status`),
  KEY `target_date` (`target_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='عناصر خطة الشراء';

-- جدول تتبع تنفيذ خطة الشراء
CREATE TABLE `cod_purchase_plan_execution` (
  `execution_id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL COMMENT 'معرف الخطة',
  `item_id` int(11) NOT NULL COMMENT 'معرف العنصر',
  `order_id` int(11) DEFAULT NULL COMMENT 'معرف أمر الشراء',
  `execution_date` date NOT NULL COMMENT 'تاريخ التنفيذ',
  `quantity` decimal(15,4) NOT NULL COMMENT 'الكمية',
  `price` decimal(15,4) NOT NULL COMMENT 'السعر',
  `total_amount` decimal(15,4) NOT NULL COMMENT 'إجمالي المبلغ',
  `notes` text COLLATE utf8mb4_general_ci COMMENT 'ملاحظات',
  `created_by` int(11) NOT NULL COMMENT 'منشئ السجل',
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'تاريخ الإضافة',
  PRIMARY KEY (`execution_id`),
  KEY `plan_id` (`plan_id`),
  KEY `item_id` (`item_id`),
  KEY `order_id` (`order_id`),
  KEY `execution_date` (`execution_date`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='تتبع تنفيذ خطة الشراء';

-- جدول موافقات خطة الشراء
CREATE TABLE `cod_purchase_plan_approval` (
  `approval_id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL COMMENT 'معرف الخطة',
  `approver_id` int(11) NOT NULL COMMENT 'معرف الموافق',
  `approval_level` int(11) NOT NULL DEFAULT '1' COMMENT 'مستوى الموافقة',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending' COMMENT 'حالة الموافقة',
  `approval_date` datetime DEFAULT NULL COMMENT 'تاريخ الموافقة',
  `comments` text COLLATE utf8mb4_general_ci COMMENT 'تعليقات الموافقة',
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'تاريخ الإضافة',
  PRIMARY KEY (`approval_id`),
  KEY `plan_id` (`plan_id`),
  KEY `approver_id` (`approver_id`),
  KEY `approval_level` (`approval_level`),
  KEY `status` (`status`),
  KEY `approval_date` (`approval_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='موافقات خطة الشراء';

-- جدول تعديلات خطة الشراء
CREATE TABLE `cod_purchase_plan_revision` (
  `revision_id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL COMMENT 'معرف الخطة',
  `revision_number` int(11) NOT NULL DEFAULT '1' COMMENT 'رقم التعديل',
  `revision_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'سبب التعديل',
  `changes_description` text COLLATE utf8mb4_general_ci COMMENT 'وصف التغييرات',
  `old_budget` decimal(15,4) DEFAULT NULL COMMENT 'الميزانية القديمة',
  `new_budget` decimal(15,4) DEFAULT NULL COMMENT 'الميزانية الجديدة',
  `old_end_date` date DEFAULT NULL COMMENT 'تاريخ النهاية القديم',
  `new_end_date` date DEFAULT NULL COMMENT 'تاريخ النهاية الجديد',
  `revised_by` int(11) NOT NULL COMMENT 'معدل الخطة',
  `revision_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'تاريخ التعديل',
  `approved_by` int(11) DEFAULT NULL COMMENT 'موافق على التعديل',
  `approval_date` datetime DEFAULT NULL COMMENT 'تاريخ موافقة التعديل',
  PRIMARY KEY (`revision_id`),
  KEY `plan_id` (`plan_id`),
  KEY `revision_number` (`revision_number`),
  KEY `revised_by` (`revised_by`),
  KEY `approved_by` (`approved_by`),
  KEY `revision_date` (`revision_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='تعديلات خطة الشراء';

-- جدول قوالب خطط الشراء
CREATE TABLE `cod_purchase_plan_template` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'اسم القالب',
  `template_description` text COLLATE utf8mb4_general_ci COMMENT 'وصف القالب',
  `template_type` enum('monthly','quarterly','yearly','custom') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'monthly' COMMENT 'نوع القالب',
  `default_budget` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'الميزانية الافتراضية',
  `template_data` longtext COLLATE utf8mb4_general_ci COMMENT 'بيانات القالب (JSON)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'نشط أم لا',
  `created_by` int(11) NOT NULL COMMENT 'منشئ القالب',
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'تاريخ الإضافة',
  `modified_by` int(11) DEFAULT NULL COMMENT 'معدل القالب',
  `date_modified` datetime DEFAULT NULL COMMENT 'تاريخ التعديل',
  PRIMARY KEY (`template_id`),
  KEY `template_name` (`template_name`),
  KEY `template_type` (`template_type`),
  KEY `is_active` (`is_active`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='قوالب خطط الشراء';

-- إضافة المفاتيح الخارجية
ALTER TABLE `cod_purchase_plan`
  ADD CONSTRAINT `fk_purchase_plan_created_by` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`),
  ADD CONSTRAINT `fk_purchase_plan_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `cod_user` (`user_id`);

ALTER TABLE `cod_purchase_plan_item`
  ADD CONSTRAINT `fk_purchase_plan_item_plan` FOREIGN KEY (`plan_id`) REFERENCES `cod_purchase_plan` (`plan_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_purchase_plan_item_product` FOREIGN KEY (`product_id`) REFERENCES `cod_product` (`product_id`),
  ADD CONSTRAINT `fk_purchase_plan_item_category` FOREIGN KEY (`category_id`) REFERENCES `cod_category` (`category_id`);

ALTER TABLE `cod_purchase_plan_execution`
  ADD CONSTRAINT `fk_purchase_plan_execution_plan` FOREIGN KEY (`plan_id`) REFERENCES `cod_purchase_plan` (`plan_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_purchase_plan_execution_item` FOREIGN KEY (`item_id`) REFERENCES `cod_purchase_plan_item` (`item_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_purchase_plan_execution_order` FOREIGN KEY (`order_id`) REFERENCES `cod_purchase_order` (`order_id`),
  ADD CONSTRAINT `fk_purchase_plan_execution_created_by` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`);

ALTER TABLE `cod_purchase_plan_approval`
  ADD CONSTRAINT `fk_purchase_plan_approval_plan` FOREIGN KEY (`plan_id`) REFERENCES `cod_purchase_plan` (`plan_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_purchase_plan_approval_approver` FOREIGN KEY (`approver_id`) REFERENCES `cod_user` (`user_id`);

ALTER TABLE `cod_purchase_plan_revision`
  ADD CONSTRAINT `fk_purchase_plan_revision_plan` FOREIGN KEY (`plan_id`) REFERENCES `cod_purchase_plan` (`plan_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_purchase_plan_revision_revised_by` FOREIGN KEY (`revised_by`) REFERENCES `cod_user` (`user_id`),
  ADD CONSTRAINT `fk_purchase_plan_revision_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `cod_user` (`user_id`);

ALTER TABLE `cod_purchase_plan_template`
  ADD CONSTRAINT `fk_purchase_plan_template_created_by` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`),
  ADD CONSTRAINT `fk_purchase_plan_template_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `cod_user` (`user_id`);

-- إنشاء فهارس إضافية لتحسين الأداء
CREATE INDEX idx_purchase_plan_period_status ON cod_purchase_plan (plan_period, status);
CREATE INDEX idx_purchase_plan_dates ON cod_purchase_plan (start_date, end_date);
CREATE INDEX idx_purchase_plan_budget ON cod_purchase_plan (total_budget, used_budget);

CREATE INDEX idx_purchase_plan_item_priority_status ON cod_purchase_plan_item (priority, status);
CREATE INDEX idx_purchase_plan_item_quantities ON cod_purchase_plan_item (planned_quantity, purchased_quantity);

CREATE INDEX idx_purchase_plan_execution_date_amount ON cod_purchase_plan_execution (execution_date, total_amount);

-- إنشاء views مفيدة
CREATE VIEW vw_purchase_plan_summary AS
SELECT 
    pp.plan_id,
    pp.plan_name,
    pp.plan_period,
    pp.start_date,
    pp.end_date,
    pp.total_budget,
    pp.used_budget,
    (pp.total_budget - pp.used_budget) AS remaining_budget,
    CASE 
        WHEN pp.total_budget > 0 THEN ROUND((pp.used_budget / pp.total_budget) * 100, 2)
        ELSE 0
    END AS budget_utilization_percentage,
    pp.status,
    COUNT(ppi.item_id) AS total_items,
    SUM(CASE WHEN ppi.status = 'received' THEN 1 ELSE 0 END) AS completed_items,
    CASE 
        WHEN COUNT(ppi.item_id) > 0 THEN ROUND((SUM(CASE WHEN ppi.status = 'received' THEN 1 ELSE 0 END) / COUNT(ppi.item_id)) * 100, 2)
        ELSE 0
    END AS completion_percentage,
    CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS created_by_name,
    pp.date_added
FROM cod_purchase_plan pp
LEFT JOIN cod_purchase_plan_item ppi ON pp.plan_id = ppi.plan_id
LEFT JOIN cod_user u ON pp.created_by = u.user_id
GROUP BY pp.plan_id;

CREATE VIEW vw_purchase_plan_item_details AS
SELECT 
    ppi.*,
    p.name AS product_name,
    p.model AS product_model,
    p.sku AS product_sku,
    cd.name AS category_name,
    pp.plan_name,
    pp.plan_period,
    (ppi.planned_quantity - ppi.purchased_quantity) AS remaining_quantity,
    CASE 
        WHEN ppi.planned_quantity > 0 THEN ROUND((ppi.purchased_quantity / ppi.planned_quantity) * 100, 2)
        ELSE 0
    END AS fulfillment_percentage
FROM cod_purchase_plan_item ppi
LEFT JOIN cod_product p ON ppi.product_id = p.product_id
LEFT JOIN cod_category_description cd ON ppi.category_id = cd.category_id AND cd.language_id = 1
LEFT JOIN cod_purchase_plan pp ON ppi.plan_id = pp.plan_id;

-- إدراج بيانات تجريبية (اختيارية)
INSERT INTO `cod_purchase_plan_template` (`template_name`, `template_description`, `template_type`, `default_budget`, `template_data`, `created_by`) VALUES
('قالب شهري أساسي', 'قالب أساسي للخطط الشهرية', 'monthly', 10000.0000, '{"categories": ["office_supplies", "raw_materials"], "default_items": []}', 1),
('قالب ربع سنوي', 'قالب للخطط ربع السنوية', 'quarterly', 30000.0000, '{"categories": ["equipment", "maintenance"], "default_items": []}', 1),
('قالب سنوي', 'قالب للخطط السنوية', 'yearly', 120000.0000, '{"categories": ["all"], "default_items": []}', 1);

-- إنشاء triggers لتحديث الميزانية المستخدمة تلقائياً
DELIMITER $$

CREATE TRIGGER tr_update_plan_budget_after_execution_insert
AFTER INSERT ON cod_purchase_plan_execution
FOR EACH ROW
BEGIN
    UPDATE cod_purchase_plan 
    SET used_budget = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM cod_purchase_plan_execution 
        WHERE plan_id = NEW.plan_id
    )
    WHERE plan_id = NEW.plan_id;
END$$

CREATE TRIGGER tr_update_plan_budget_after_execution_update
AFTER UPDATE ON cod_purchase_plan_execution
FOR EACH ROW
BEGIN
    UPDATE cod_purchase_plan 
    SET used_budget = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM cod_purchase_plan_execution 
        WHERE plan_id = NEW.plan_id
    )
    WHERE plan_id = NEW.plan_id;
END$$

CREATE TRIGGER tr_update_plan_budget_after_execution_delete
AFTER DELETE ON cod_purchase_plan_execution
FOR EACH ROW
BEGIN
    UPDATE cod_purchase_plan 
    SET used_budget = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM cod_purchase_plan_execution 
        WHERE plan_id = OLD.plan_id
    )
    WHERE plan_id = OLD.plan_id;
END$$

DELIMITER ;
