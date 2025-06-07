-- جداول عقود الموردين
-- يجب تنفيذ هذه الأوامر في phpMyAdmin

-- جدول عقود الموردين الرئيسي
CREATE TABLE `cod_supplier_contract` (
  `contract_id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_number` varchar(64) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'رقم العقد',
  `supplier_id` int(11) NOT NULL COMMENT 'معرف المورد',
  `contract_type` enum('general','framework','exclusive','service','maintenance') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'general' COMMENT 'نوع العقد',
  `contract_date` date NOT NULL COMMENT 'تاريخ العقد',
  `start_date` date NOT NULL COMMENT 'تاريخ بداية العقد',
  `end_date` date NOT NULL COMMENT 'تاريخ نهاية العقد',
  `contract_value` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'قيمة العقد',
  `currency_id` int(11) NOT NULL DEFAULT '1' COMMENT 'معرف العملة',
  `payment_terms` text COLLATE utf8mb4_general_ci COMMENT 'شروط الدفع',
  `delivery_terms` text COLLATE utf8mb4_general_ci COMMENT 'شروط التسليم',
  `terms_conditions` text COLLATE utf8mb4_general_ci COMMENT 'الشروط والأحكام',
  `notes` text COLLATE utf8mb4_general_ci COMMENT 'ملاحظات',
  `status` enum('draft','pending_approval','active','suspended','expired','terminated') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft' COMMENT 'حالة العقد',
  `auto_renewal` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'تجديد تلقائي',
  `renewal_period` int(11) DEFAULT NULL COMMENT 'فترة التجديد بالأشهر',
  `renewal_notice_days` int(11) DEFAULT '30' COMMENT 'أيام الإشعار قبل التجديد',
  `performance_rating` decimal(3,2) DEFAULT NULL COMMENT 'تقييم الأداء من 1 إلى 5',
  `created_by` int(11) NOT NULL COMMENT 'منشئ العقد',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'تاريخ الإنشاء',
  `modified_by` int(11) DEFAULT NULL COMMENT 'معدل العقد',
  `date_modified` datetime DEFAULT NULL COMMENT 'تاريخ التعديل',
  `renewed_by` int(11) DEFAULT NULL COMMENT 'مجدد العقد',
  `renewed_at` datetime DEFAULT NULL COMMENT 'تاريخ التجديد',
  `renewal_notes` text COLLATE utf8mb4_general_ci COMMENT 'ملاحظات التجديد',
  `terminated_by` int(11) DEFAULT NULL COMMENT 'منهي العقد',
  `terminated_at` datetime DEFAULT NULL COMMENT 'تاريخ الإنهاء',
  `termination_reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'سبب الإنهاء',
  `termination_notes` text COLLATE utf8mb4_general_ci COMMENT 'ملاحظات الإنهاء',
  PRIMARY KEY (`contract_id`),
  UNIQUE KEY `contract_number` (`contract_number`),
  KEY `supplier_id` (`supplier_id`),
  KEY `currency_id` (`currency_id`),
  KEY `status` (`status`),
  KEY `start_date` (`start_date`),
  KEY `end_date` (`end_date`),
  KEY `created_by` (`created_by`),
  KEY `modified_by` (`modified_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='عقود الموردين';

-- جدول تاريخ عقود الموردين
CREATE TABLE `cod_supplier_contract_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_id` int(11) NOT NULL COMMENT 'معرف العقد',
  `action` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'نوع الإجراء',
  `notes` text COLLATE utf8mb4_general_ci COMMENT 'ملاحظات الإجراء',
  `user_id` int(11) NOT NULL COMMENT 'معرف المستخدم',
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'تاريخ الإجراء',
  PRIMARY KEY (`history_id`),
  KEY `contract_id` (`contract_id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='تاريخ عقود الموردين';

-- جدول بنود عقود الموردين
CREATE TABLE `cod_supplier_contract_item` (
  `contract_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_id` int(11) NOT NULL COMMENT 'معرف العقد',
  `product_id` int(11) DEFAULT NULL COMMENT 'معرف المنتج (اختياري)',
  `item_description` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'وصف البند',
  `quantity` decimal(15,4) DEFAULT NULL COMMENT 'الكمية',
  `unit_id` int(11) DEFAULT NULL COMMENT 'معرف الوحدة',
  `unit_price` decimal(15,4) DEFAULT NULL COMMENT 'سعر الوحدة',
  `total_price` decimal(15,4) DEFAULT NULL COMMENT 'السعر الإجمالي',
  `discount_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'نسبة الخصم',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'نسبة الضريبة',
  `delivery_date` date DEFAULT NULL COMMENT 'تاريخ التسليم المطلوب',
  `specifications` text COLLATE utf8mb4_general_ci COMMENT 'المواصفات التفصيلية',
  `notes` text COLLATE utf8mb4_general_ci COMMENT 'ملاحظات البند',
  `status` enum('active','inactive','completed','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'حالة البند',
  `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT 'ترتيب البند',
  PRIMARY KEY (`contract_item_id`),
  KEY `contract_id` (`contract_id`),
  KEY `product_id` (`product_id`),
  KEY `unit_id` (`unit_id`),
  KEY `status` (`status`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='بنود عقود الموردين';

-- جدول مستندات عقود الموردين
CREATE TABLE `cod_supplier_contract_document` (
  `document_id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_id` int(11) NOT NULL COMMENT 'معرف العقد',
  `document_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'اسم المستند',
  `document_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'نوع المستند',
  `file_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'اسم الملف',
  `file_path` varchar(500) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'مسار الملف',
  `file_size` int(11) NOT NULL DEFAULT '0' COMMENT 'حجم الملف بالبايت',
  `mime_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'نوع الملف',
  `description` text COLLATE utf8mb4_general_ci COMMENT 'وصف المستند',
  `uploaded_by` int(11) NOT NULL COMMENT 'رافع المستند',
  `date_uploaded` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'تاريخ الرفع',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'نشط أم لا',
  PRIMARY KEY (`document_id`),
  KEY `contract_id` (`contract_id`),
  KEY `document_type` (`document_type`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='مستندات عقود الموردين';

-- جدول تقييم أداء عقود الموردين
CREATE TABLE `cod_supplier_contract_performance` (
  `performance_id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_id` int(11) NOT NULL COMMENT 'معرف العقد',
  `evaluation_date` date NOT NULL COMMENT 'تاريخ التقييم',
  `delivery_rating` decimal(3,2) NOT NULL DEFAULT '0.00' COMMENT 'تقييم التسليم من 1 إلى 5',
  `quality_rating` decimal(3,2) NOT NULL DEFAULT '0.00' COMMENT 'تقييم الجودة من 1 إلى 5',
  `cost_rating` decimal(3,2) NOT NULL DEFAULT '0.00' COMMENT 'تقييم التكلفة من 1 إلى 5',
  `service_rating` decimal(3,2) NOT NULL DEFAULT '0.00' COMMENT 'تقييم الخدمة من 1 إلى 5',
  `overall_rating` decimal(3,2) NOT NULL DEFAULT '0.00' COMMENT 'التقييم الإجمالي من 1 إلى 5',
  `strengths` text COLLATE utf8mb4_general_ci COMMENT 'نقاط القوة',
  `weaknesses` text COLLATE utf8mb4_general_ci COMMENT 'نقاط الضعف',
  `recommendations` text COLLATE utf8mb4_general_ci COMMENT 'التوصيات',
  `notes` text COLLATE utf8mb4_general_ci COMMENT 'ملاحظات التقييم',
  `evaluated_by` int(11) NOT NULL COMMENT 'معرف المقيم',
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'تاريخ الإضافة',
  PRIMARY KEY (`performance_id`),
  KEY `contract_id` (`contract_id`),
  KEY `evaluation_date` (`evaluation_date`),
  KEY `overall_rating` (`overall_rating`),
  KEY `evaluated_by` (`evaluated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='تقييم أداء عقود الموردين';

-- جدول تجديدات عقود الموردين
CREATE TABLE `cod_supplier_contract_renewal` (
  `renewal_id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_id` int(11) NOT NULL COMMENT 'معرف العقد',
  `original_end_date` date NOT NULL COMMENT 'تاريخ النهاية الأصلي',
  `new_end_date` date NOT NULL COMMENT 'تاريخ النهاية الجديد',
  `renewal_period_months` int(11) NOT NULL COMMENT 'فترة التجديد بالأشهر',
  `renewal_value` decimal(15,4) DEFAULT NULL COMMENT 'قيمة التجديد',
  `renewal_terms` text COLLATE utf8mb4_general_ci COMMENT 'شروط التجديد',
  `renewal_reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'سبب التجديد',
  `notes` text COLLATE utf8mb4_general_ci COMMENT 'ملاحظات التجديد',
  `renewed_by` int(11) NOT NULL COMMENT 'مجدد العقد',
  `renewal_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'تاريخ التجديد',
  `approved_by` int(11) DEFAULT NULL COMMENT 'موافق على التجديد',
  `approval_date` datetime DEFAULT NULL COMMENT 'تاريخ الموافقة',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending' COMMENT 'حالة التجديد',
  PRIMARY KEY (`renewal_id`),
  KEY `contract_id` (`contract_id`),
  KEY `renewal_date` (`renewal_date`),
  KEY `renewed_by` (`renewed_by`),
  KEY `approved_by` (`approved_by`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='تجديدات عقود الموردين';

-- إضافة المفاتيح الخارجية
ALTER TABLE `cod_supplier_contract`
  ADD CONSTRAINT `fk_supplier_contract_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `cod_supplier` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_supplier_contract_currency` FOREIGN KEY (`currency_id`) REFERENCES `cod_currency` (`currency_id`),
  ADD CONSTRAINT `fk_supplier_contract_created_by` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`),
  ADD CONSTRAINT `fk_supplier_contract_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `cod_user` (`user_id`);

ALTER TABLE `cod_supplier_contract_history`
  ADD CONSTRAINT `fk_supplier_contract_history_contract` FOREIGN KEY (`contract_id`) REFERENCES `cod_supplier_contract` (`contract_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_supplier_contract_history_user` FOREIGN KEY (`user_id`) REFERENCES `cod_user` (`user_id`);

ALTER TABLE `cod_supplier_contract_item`
  ADD CONSTRAINT `fk_supplier_contract_item_contract` FOREIGN KEY (`contract_id`) REFERENCES `cod_supplier_contract` (`contract_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_supplier_contract_item_product` FOREIGN KEY (`product_id`) REFERENCES `cod_product` (`product_id`),
  ADD CONSTRAINT `fk_supplier_contract_item_unit` FOREIGN KEY (`unit_id`) REFERENCES `cod_unit` (`unit_id`);

ALTER TABLE `cod_supplier_contract_document`
  ADD CONSTRAINT `fk_supplier_contract_document_contract` FOREIGN KEY (`contract_id`) REFERENCES `cod_supplier_contract` (`contract_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_supplier_contract_document_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `cod_user` (`user_id`);

ALTER TABLE `cod_supplier_contract_performance`
  ADD CONSTRAINT `fk_supplier_contract_performance_contract` FOREIGN KEY (`contract_id`) REFERENCES `cod_supplier_contract` (`contract_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_supplier_contract_performance_evaluated_by` FOREIGN KEY (`evaluated_by`) REFERENCES `cod_user` (`user_id`);

ALTER TABLE `cod_supplier_contract_renewal`
  ADD CONSTRAINT `fk_supplier_contract_renewal_contract` FOREIGN KEY (`contract_id`) REFERENCES `cod_supplier_contract` (`contract_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_supplier_contract_renewal_renewed_by` FOREIGN KEY (`renewed_by`) REFERENCES `cod_user` (`user_id`),
  ADD CONSTRAINT `fk_supplier_contract_renewal_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `cod_user` (`user_id`);

-- إدراج بيانات تجريبية (اختياري)
INSERT INTO `cod_supplier_contract` (`contract_number`, `supplier_id`, `contract_type`, `contract_date`, `start_date`, `end_date`, `contract_value`, `currency_id`, `payment_terms`, `delivery_terms`, `terms_conditions`, `notes`, `status`, `created_by`) VALUES
('CON-2024-001', 1, 'general', '2024-01-01', '2024-01-01', '2024-12-31', 50000.0000, 1, 'دفع خلال 30 يوم من تاريخ الفاتورة', 'التسليم خلال 15 يوم من تاريخ الطلب', 'شروط وأحكام عامة للتوريد', 'عقد توريد مواد خام', 'active', 1),
('CON-2024-002', 2, 'service', '2024-02-01', '2024-02-01', '2025-01-31', 25000.0000, 1, 'دفع شهري مقدم', 'خدمة مستمرة', 'شروط وأحكام عقد الخدمات', 'عقد خدمات صيانة', 'active', 1);
