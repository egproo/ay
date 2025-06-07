-- جداول حسابات الموردين والدفعات
-- يجب تنفيذ هذه الأوامر في phpMyAdmin

-- جدول حسابات الموردين
CREATE TABLE `cod_supplier_account` (
  `account_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL COMMENT 'معرف المورد',
  `account_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'رقم الحساب',
  `current_balance` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'الرصيد الحالي',
  `credit_limit` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'حد الائتمان',
  `payment_terms` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'net_30' COMMENT 'شروط الدفع',
  `account_status` enum('active','suspended','closed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'حالة الحساب',
  `last_transaction_date` datetime DEFAULT NULL COMMENT 'تاريخ آخر معاملة',
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'تاريخ إنشاء الحساب',
  `created_by` int(11) NOT NULL COMMENT 'منشئ الحساب',
  `date_modified` datetime DEFAULT NULL COMMENT 'تاريخ التعديل',
  `modified_by` int(11) DEFAULT NULL COMMENT 'معدل الحساب',
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `supplier_id` (`supplier_id`),
  UNIQUE KEY `account_number` (`account_number`),
  KEY `account_status` (`account_status`),
  KEY `current_balance` (`current_balance`),
  KEY `created_by` (`created_by`),
  KEY `modified_by` (`modified_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='حسابات الموردين';

-- جدول معاملات الموردين
CREATE TABLE `cod_supplier_transaction` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL COMMENT 'معرف المورد',
  `transaction_type` enum('purchase','invoice','payment','credit','debit','adjustment') COLLATE utf8mb4_general_ci NOT NULL COMMENT 'نوع المعاملة',
  `amount` decimal(15,4) NOT NULL COMMENT 'مبلغ المعاملة',
  `transaction_date` date NOT NULL COMMENT 'تاريخ المعاملة',
  `reference` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'المرجع',
  `description` text COLLATE utf8mb4_general_ci COMMENT 'وصف المعاملة',
  `related_document_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'نوع المستند المرتبط',
  `related_document_id` int(11) DEFAULT NULL COMMENT 'معرف المستند المرتبط',
  `user_id` int(11) NOT NULL COMMENT 'معرف المستخدم',
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'تاريخ الإضافة',
  `is_reversed` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'هل تم عكس المعاملة',
  `reversed_by` int(11) DEFAULT NULL COMMENT 'من عكس المعاملة',
  `reversed_at` datetime DEFAULT NULL COMMENT 'تاريخ عكس المعاملة',
  `reversal_reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'سبب عكس المعاملة',
  PRIMARY KEY (`transaction_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `transaction_type` (`transaction_type`),
  KEY `transaction_date` (`transaction_date`),
  KEY `user_id` (`user_id`),
  KEY `related_document` (`related_document_type`,`related_document_id`),
  KEY `is_reversed` (`is_reversed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='معاملات الموردين';

-- جدول دفعات الموردين
CREATE TABLE `cod_supplier_payment` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'رقم الدفعة',
  `supplier_id` int(11) NOT NULL COMMENT 'معرف المورد',
  `payment_amount` decimal(15,4) NOT NULL COMMENT 'مبلغ الدفعة',
  `currency_id` int(11) NOT NULL DEFAULT '1' COMMENT 'معرف العملة',
  `payment_method_id` int(11) NOT NULL COMMENT 'معرف طريقة الدفع',
  `payment_date` date NOT NULL COMMENT 'تاريخ الدفع',
  `reference_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'رقم المرجع',
  `bank_account_id` int(11) DEFAULT NULL COMMENT 'معرف الحساب البنكي',
  `check_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'رقم الشيك',
  `check_date` date DEFAULT NULL COMMENT 'تاريخ الشيك',
  `notes` text COLLATE utf8mb4_general_ci COMMENT 'ملاحظات الدفعة',
  `status` enum('pending','approved','paid','cancelled','returned') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending' COMMENT 'حالة الدفعة',
  `approval_required` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'يتطلب موافقة',
  `approved_by` int(11) DEFAULT NULL COMMENT 'موافق عليها من',
  `approval_date` datetime DEFAULT NULL COMMENT 'تاريخ الموافقة',
  `approval_notes` text COLLATE utf8mb4_general_ci COMMENT 'ملاحظات الموافقة',
  `created_by` int(11) NOT NULL COMMENT 'منشئ الدفعة',
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'تاريخ الإضافة',
  `modified_by` int(11) DEFAULT NULL COMMENT 'معدل الدفعة',
  `date_modified` datetime DEFAULT NULL COMMENT 'تاريخ التعديل',
  `cancelled_by` int(11) DEFAULT NULL COMMENT 'ملغي الدفعة',
  `cancellation_date` datetime DEFAULT NULL COMMENT 'تاريخ الإلغاء',
  `cancellation_reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'سبب الإلغاء',
  PRIMARY KEY (`payment_id`),
  UNIQUE KEY `payment_number` (`payment_number`),
  KEY `supplier_id` (`supplier_id`),
  KEY `payment_method_id` (`payment_method_id`),
  KEY `currency_id` (`currency_id`),
  KEY `bank_account_id` (`bank_account_id`),
  KEY `payment_date` (`payment_date`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`),
  KEY `approved_by` (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='دفعات الموردين';

-- جدول تفاصيل دفعات الموردين (ربط الدفعة بالفواتير)
CREATE TABLE `cod_supplier_payment_detail` (
  `payment_detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL COMMENT 'معرف الدفعة',
  `invoice_id` int(11) DEFAULT NULL COMMENT 'معرف الفاتورة',
  `allocated_amount` decimal(15,4) NOT NULL COMMENT 'المبلغ المخصص',
  `discount_amount` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'مبلغ الخصم',
  `notes` text COLLATE utf8mb4_general_ci COMMENT 'ملاحظات التخصيص',
  PRIMARY KEY (`payment_detail_id`),
  KEY `payment_id` (`payment_id`),
  KEY `invoice_id` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='تفاصيل دفعات الموردين';

-- جدول طرق الدفع
CREATE TABLE `cod_payment_method` (
  `payment_method_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'اسم طريقة الدفع',
  `code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'كود طريقة الدفع',
  `description` text COLLATE utf8mb4_general_ci COMMENT 'وصف طريقة الدفع',
  `requires_reference` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'يتطلب رقم مرجع',
  `requires_bank_account` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'يتطلب حساب بنكي',
  `requires_check_details` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'يتطلب تفاصيل الشيك',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'نشط أم لا',
  `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT 'ترتيب العرض',
  PRIMARY KEY (`payment_method_id`),
  UNIQUE KEY `code` (`code`),
  KEY `is_active` (`is_active`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='طرق الدفع';

-- جدول الحسابات البنكية
CREATE TABLE `cod_bank_account` (
  `bank_account_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'اسم الحساب',
  `bank_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'اسم البنك',
  `account_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'رقم الحساب',
  `iban` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'رقم الآيبان',
  `swift_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'كود السويفت',
  `currency_id` int(11) NOT NULL DEFAULT '1' COMMENT 'معرف العملة',
  `current_balance` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'الرصيد الحالي',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'نشط أم لا',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'افتراضي أم لا',
  `notes` text COLLATE utf8mb4_general_ci COMMENT 'ملاحظات',
  PRIMARY KEY (`bank_account_id`),
  UNIQUE KEY `account_number` (`account_number`),
  KEY `currency_id` (`currency_id`),
  KEY `is_active` (`is_active`),
  KEY `is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='الحسابات البنكية';

-- إضافة المفاتيح الخارجية
ALTER TABLE `cod_supplier_account`
  ADD CONSTRAINT `fk_supplier_account_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `cod_supplier` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_supplier_account_created_by` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`),
  ADD CONSTRAINT `fk_supplier_account_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `cod_user` (`user_id`);

ALTER TABLE `cod_supplier_transaction`
  ADD CONSTRAINT `fk_supplier_transaction_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `cod_supplier` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_supplier_transaction_user` FOREIGN KEY (`user_id`) REFERENCES `cod_user` (`user_id`),
  ADD CONSTRAINT `fk_supplier_transaction_reversed_by` FOREIGN KEY (`reversed_by`) REFERENCES `cod_user` (`user_id`);

ALTER TABLE `cod_supplier_payment`
  ADD CONSTRAINT `fk_supplier_payment_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `cod_supplier` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_supplier_payment_currency` FOREIGN KEY (`currency_id`) REFERENCES `cod_currency` (`currency_id`),
  ADD CONSTRAINT `fk_supplier_payment_method` FOREIGN KEY (`payment_method_id`) REFERENCES `cod_payment_method` (`payment_method_id`),
  ADD CONSTRAINT `fk_supplier_payment_bank_account` FOREIGN KEY (`bank_account_id`) REFERENCES `cod_bank_account` (`bank_account_id`),
  ADD CONSTRAINT `fk_supplier_payment_created_by` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`),
  ADD CONSTRAINT `fk_supplier_payment_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `cod_user` (`user_id`),
  ADD CONSTRAINT `fk_supplier_payment_cancelled_by` FOREIGN KEY (`cancelled_by`) REFERENCES `cod_user` (`user_id`);

ALTER TABLE `cod_supplier_payment_detail`
  ADD CONSTRAINT `fk_supplier_payment_detail_payment` FOREIGN KEY (`payment_id`) REFERENCES `cod_supplier_payment` (`payment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_supplier_payment_detail_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `cod_supplier_invoice` (`invoice_id`);

ALTER TABLE `cod_bank_account`
  ADD CONSTRAINT `fk_bank_account_currency` FOREIGN KEY (`currency_id`) REFERENCES `cod_currency` (`currency_id`);

-- إدراج بيانات أساسية لطرق الدفع
INSERT INTO `cod_payment_method` (`name`, `code`, `description`, `requires_reference`, `requires_bank_account`, `requires_check_details`, `is_active`, `sort_order`) VALUES
('نقداً', 'cash', 'دفع نقدي', 0, 0, 0, 1, 1),
('تحويل بنكي', 'bank_transfer', 'تحويل بنكي', 1, 1, 0, 1, 2),
('شيك', 'check', 'دفع بالشيك', 0, 0, 1, 1, 3),
('بطاقة ائتمان', 'credit_card', 'دفع بالبطاقة الائتمانية', 1, 0, 0, 1, 4),
('حوالة', 'money_order', 'حوالة مالية', 1, 0, 0, 1, 5);

-- إدراج حساب بنكي افتراضي
INSERT INTO `cod_bank_account` (`account_name`, `bank_name`, `account_number`, `currency_id`, `current_balance`, `is_active`, `is_default`, `notes`) VALUES
('الحساب الرئيسي', 'البنك الأهلي', '1234567890', 1, 0.0000, 1, 1, 'الحساب البنكي الرئيسي للشركة');

-- إنشاء فهارس إضافية لتحسين الأداء
CREATE INDEX idx_supplier_transaction_date_type ON cod_supplier_transaction (transaction_date, transaction_type);
CREATE INDEX idx_supplier_payment_date_status ON cod_supplier_payment (payment_date, status);
CREATE INDEX idx_supplier_account_balance ON cod_supplier_account (current_balance);

-- إنشاء views مفيدة
CREATE VIEW vw_supplier_balance AS
SELECT 
    s.supplier_id,
    CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
    sa.account_number,
    sa.current_balance,
    sa.credit_limit,
    (sa.credit_limit - sa.current_balance) AS available_credit,
    sa.account_status,
    sa.last_transaction_date
FROM cod_supplier s
LEFT JOIN cod_supplier_account sa ON s.supplier_id = sa.supplier_id;

CREATE VIEW vw_supplier_payment_summary AS
SELECT 
    sp.payment_id,
    sp.payment_number,
    CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
    sp.payment_amount,
    c.code AS currency_code,
    pm.name AS payment_method_name,
    sp.payment_date,
    sp.status,
    CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS created_by_name
FROM cod_supplier_payment sp
LEFT JOIN cod_supplier s ON sp.supplier_id = s.supplier_id
LEFT JOIN cod_currency c ON sp.currency_id = c.currency_id
LEFT JOIN cod_payment_method pm ON sp.payment_method_id = pm.payment_method_id
LEFT JOIN cod_user u ON sp.created_by = u.user_id;
