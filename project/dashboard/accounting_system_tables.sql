-- جداول النظام المحاسبي المتكامل
-- يجب تنفيذ هذه الأوامر في phpMyAdmin

-- جدول دليل الحسابات الرئيسي
CREATE TABLE `cod_accounts` (
  `account_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_code` bigint(20) NOT NULL COMMENT 'رقم الحساب',
  `parent_id` int(11) DEFAULT NULL COMMENT 'الحساب الأب',
  `account_type` enum('asset','liability','equity','revenue','expense') COLLATE utf8mb4_general_ci NOT NULL COMMENT 'نوع الحساب',
  `account_nature` enum('debit','credit') COLLATE utf8mb4_general_ci NOT NULL COMMENT 'طبيعة الحساب',
  `level` int(11) NOT NULL DEFAULT '1' COMMENT 'مستوى الحساب في الشجرة',
  `is_parent` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'هل هو حساب أب',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'نشط أم لا',
  `allow_posting` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'يسمح بالترحيل إليه',
  `current_balance` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'الرصيد الحالي',
  `opening_balance` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'رصيد أول المدة',
  `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT 'ترتيب العرض',
  `created_by` int(11) NOT NULL COMMENT 'منشئ الحساب',
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `account_code` (`account_code`),
  KEY `parent_id` (`parent_id`),
  KEY `account_type` (`account_type`),
  KEY `level` (`level`),
  KEY `is_active` (`is_active`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='دليل الحسابات';

-- جدول أوصاف الحسابات متعددة اللغات
CREATE TABLE `cod_account_description` (
  `account_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'اسم الحساب',
  `description` text COLLATE utf8mb4_general_ci COMMENT 'وصف الحساب',
  PRIMARY KEY (`account_id`,`language_id`),
  KEY `language_id` (`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='أوصاف الحسابات';

-- جدول القيود المحاسبية (رؤوس القيود)
CREATE TABLE `cod_journal_entry` (
  `journal_id` int(11) NOT NULL AUTO_INCREMENT,
  `journal_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'رقم القيد',
  `journal_date` date NOT NULL COMMENT 'تاريخ القيد',
  `reference_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'نوع المرجع',
  `reference_id` int(11) DEFAULT NULL COMMENT 'معرف المرجع',
  `reference_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'رقم المرجع',
  `description` text COLLATE utf8mb4_general_ci NOT NULL COMMENT 'وصف القيد',
  `total_debit` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'إجمالي المدين',
  `total_credit` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'إجمالي الدائن',
  `status` enum('draft','posted','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft' COMMENT 'حالة القيد',
  `is_auto` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'قيد تلقائي',
  `is_reversed` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'قيد معكوس',
  `reversed_journal_id` int(11) DEFAULT NULL COMMENT 'معرف القيد المعكوس',
  `period_id` int(11) DEFAULT NULL COMMENT 'معرف الفترة المحاسبية',
  `branch_id` int(11) DEFAULT NULL COMMENT 'معرف الفرع',
  `created_by` int(11) NOT NULL COMMENT 'منشئ القيد',
  `posted_by` int(11) DEFAULT NULL COMMENT 'مرحل القيد',
  `posted_date` datetime DEFAULT NULL COMMENT 'تاريخ الترحيل',
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`journal_id`),
  UNIQUE KEY `journal_number` (`journal_number`),
  KEY `journal_date` (`journal_date`),
  KEY `reference_type` (`reference_type`,`reference_id`),
  KEY `status` (`status`),
  KEY `period_id` (`period_id`),
  KEY `branch_id` (`branch_id`),
  KEY `created_by` (`created_by`),
  KEY `posted_by` (`posted_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='القيود المحاسبية';

-- جدول تفاصيل القيود المحاسبية
CREATE TABLE `cod_journal_entry_line` (
  `line_id` int(11) NOT NULL AUTO_INCREMENT,
  `journal_id` int(11) NOT NULL COMMENT 'معرف القيد',
  `account_id` int(11) NOT NULL COMMENT 'معرف الحساب',
  `debit_amount` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'المبلغ المدين',
  `credit_amount` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'المبلغ الدائن',
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'وصف السطر',
  `cost_center_id` int(11) DEFAULT NULL COMMENT 'مركز التكلفة',
  `project_id` int(11) DEFAULT NULL COMMENT 'معرف المشروع',
  `currency_id` int(11) DEFAULT NULL COMMENT 'معرف العملة',
  `exchange_rate` decimal(15,8) NOT NULL DEFAULT '1.00000000' COMMENT 'سعر الصرف',
  `line_order` int(11) NOT NULL DEFAULT '1' COMMENT 'ترتيب السطر',
  PRIMARY KEY (`line_id`),
  KEY `journal_id` (`journal_id`),
  KEY `account_id` (`account_id`),
  KEY `cost_center_id` (`cost_center_id`),
  KEY `project_id` (`project_id`),
  KEY `currency_id` (`currency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='تفاصيل القيود المحاسبية';

-- جدول الفترات المحاسبية
CREATE TABLE `cod_accounting_period` (
  `period_id` int(11) NOT NULL AUTO_INCREMENT,
  `period_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'اسم الفترة',
  `start_date` date NOT NULL COMMENT 'تاريخ البداية',
  `end_date` date NOT NULL COMMENT 'تاريخ النهاية',
  `fiscal_year` int(11) NOT NULL COMMENT 'السنة المالية',
  `period_type` enum('monthly','quarterly','yearly') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'monthly' COMMENT 'نوع الفترة',
  `status` enum('open','closed','locked') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'open' COMMENT 'حالة الفترة',
  `closed_by` int(11) DEFAULT NULL COMMENT 'مغلق بواسطة',
  `closed_date` datetime DEFAULT NULL COMMENT 'تاريخ الإغلاق',
  `notes` text COLLATE utf8mb4_general_ci COMMENT 'ملاحظات',
  `created_by` int(11) NOT NULL COMMENT 'منشئ الفترة',
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`period_id`),
  UNIQUE KEY `period_dates` (`start_date`,`end_date`),
  KEY `fiscal_year` (`fiscal_year`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='الفترات المحاسبية';

-- جدول أرصدة الحسابات
CREATE TABLE `cod_account_balance` (
  `balance_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL COMMENT 'معرف الحساب',
  `period_id` int(11) NOT NULL COMMENT 'معرف الفترة',
  `branch_id` int(11) DEFAULT NULL COMMENT 'معرف الفرع',
  `opening_balance` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'رصيد أول المدة',
  `debit_total` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'إجمالي المدين',
  `credit_total` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'إجمالي الدائن',
  `closing_balance` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'رصيد آخر المدة',
  `last_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`balance_id`),
  UNIQUE KEY `account_period_branch` (`account_id`,`period_id`,`branch_id`),
  KEY `period_id` (`period_id`),
  KEY `branch_id` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='أرصدة الحسابات';

-- جدول مراكز التكلفة
CREATE TABLE `cod_cost_center` (
  `cost_center_id` int(11) NOT NULL AUTO_INCREMENT,
  `cost_center_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'رمز مركز التكلفة',
  `cost_center_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'اسم مركز التكلفة',
  `parent_id` int(11) DEFAULT NULL COMMENT 'مركز التكلفة الأب',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'نشط أم لا',
  `description` text COLLATE utf8mb4_general_ci COMMENT 'وصف مركز التكلفة',
  `created_by` int(11) NOT NULL COMMENT 'منشئ مركز التكلفة',
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cost_center_id`),
  UNIQUE KEY `cost_center_code` (`cost_center_code`),
  KEY `parent_id` (`parent_id`),
  KEY `is_active` (`is_active`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='مراكز التكلفة';

-- جدول إعدادات المحاسبة
CREATE TABLE `cod_accounting_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'مفتاح الإعداد',
  `setting_value` text COLLATE utf8mb4_general_ci COMMENT 'قيمة الإعداد',
  `setting_type` enum('text','number','boolean','json') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'text' COMMENT 'نوع الإعداد',
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'وصف الإعداد',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'إعداد نظام',
  `updated_by` int(11) DEFAULT NULL COMMENT 'محدث الإعداد',
  `date_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `setting_type` (`setting_type`),
  KEY `updated_by` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='إعدادات المحاسبة';

-- إدراج الإعدادات الأساسية للمحاسبة
INSERT INTO `cod_accounting_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `is_system`) VALUES
('default_currency_id', '1', 'number', 'العملة الافتراضية', 1),
('auto_journal_numbering', '1', 'boolean', 'ترقيم القيود تلقائياً', 1),
('journal_number_prefix', 'JE', 'text', 'بادئة رقم القيد', 1),
('require_journal_approval', '0', 'boolean', 'يتطلب موافقة على القيود', 1),
('allow_backdated_entries', '0', 'boolean', 'السماح بقيود بتاريخ سابق', 1),
('fiscal_year_start', '01-01', 'text', 'بداية السنة المالية (شهر-يوم)', 1),
('default_cost_center_required', '0', 'boolean', 'مركز التكلفة مطلوب', 1),
('inventory_integration_enabled', '1', 'boolean', 'تفعيل تكامل المخزون', 1),
('sales_account_id', '41000', 'number', 'حساب المبيعات الافتراضي', 1),
('cogs_account_id', '51000', 'number', 'حساب تكلفة المبيعات', 1),
('inventory_account_id', '13000', 'number', 'حساب المخزون', 1),
('cash_account_id', '11100', 'number', 'حساب النقدية', 1),
('bank_account_id', '11200', 'number', 'حساب البنك الافتراضي', 1),
('ar_account_id', '12000', 'number', 'حساب العملاء', 1),
('ap_account_id', '21000', 'number', 'حساب الموردين', 1),
('vat_payable_account_id', '22100', 'number', 'حساب ضريبة القيمة المضافة', 1);

-- إدراج دليل الحسابات الأساسي
INSERT INTO `cod_accounts` (`account_code`, `parent_id`, `account_type`, `account_nature`, `level`, `is_parent`, `opening_balance`, `sort_order`, `created_by`) VALUES
-- الأصول (Assets) - 1xxxx
(10000, NULL, 'asset', 'debit', 1, 1, 0.0000, 1, 1),
(11000, 1, 'asset', 'debit', 2, 1, 0.0000, 2, 1),
(11100, 2, 'asset', 'debit', 3, 0, 0.0000, 3, 1),
(11200, 2, 'asset', 'debit', 3, 0, 0.0000, 4, 1),
(11300, 2, 'asset', 'debit', 3, 0, 0.0000, 5, 1),
(12000, 1, 'asset', 'debit', 2, 1, 0.0000, 6, 1),
(12100, 6, 'asset', 'debit', 3, 0, 0.0000, 7, 1),
(12200, 6, 'asset', 'debit', 3, 0, 0.0000, 8, 1),
(13000, 1, 'asset', 'debit', 2, 1, 0.0000, 9, 1),
(13100, 9, 'asset', 'debit', 3, 0, 0.0000, 10, 1),
(13200, 9, 'asset', 'debit', 3, 0, 0.0000, 11, 1),
(14000, 1, 'asset', 'debit', 2, 1, 0.0000, 12, 1),
(14100, 12, 'asset', 'debit', 3, 0, 0.0000, 13, 1),
(14200, 12, 'asset', 'debit', 3, 0, 0.0000, 14, 1),
(15000, 1, 'asset', 'debit', 2, 1, 0.0000, 15, 1),
(15100, 15, 'asset', 'debit', 3, 0, 0.0000, 16, 1),
(15200, 15, 'asset', 'debit', 3, 0, 0.0000, 17, 1),

-- الخصوم (Liabilities) - 2xxxx
(20000, NULL, 'liability', 'credit', 1, 1, 0.0000, 18, 1),
(21000, 18, 'liability', 'credit', 2, 1, 0.0000, 19, 1),
(21100, 19, 'liability', 'credit', 3, 0, 0.0000, 20, 1),
(21200, 19, 'liability', 'credit', 3, 0, 0.0000, 21, 1),
(22000, 18, 'liability', 'credit', 2, 1, 0.0000, 22, 1),
(22100, 22, 'liability', 'credit', 3, 0, 0.0000, 23, 1),
(22200, 22, 'liability', 'credit', 3, 0, 0.0000, 24, 1),
(23000, 18, 'liability', 'credit', 2, 1, 0.0000, 25, 1),
(23100, 25, 'liability', 'credit', 3, 0, 0.0000, 26, 1),

-- حقوق الملكية (Equity) - 3xxxx
(30000, NULL, 'equity', 'credit', 1, 1, 0.0000, 27, 1),
(31000, 27, 'equity', 'credit', 2, 0, 0.0000, 28, 1),
(32000, 27, 'equity', 'credit', 2, 0, 0.0000, 29, 1),
(33000, 27, 'equity', 'credit', 2, 0, 0.0000, 30, 1),

-- الإيرادات (Revenue) - 4xxxx
(40000, NULL, 'revenue', 'credit', 1, 1, 0.0000, 31, 1),
(41000, 31, 'revenue', 'credit', 2, 1, 0.0000, 32, 1),
(41100, 32, 'revenue', 'credit', 3, 0, 0.0000, 33, 1),
(41200, 32, 'revenue', 'credit', 3, 0, 0.0000, 34, 1),
(42000, 31, 'revenue', 'credit', 2, 1, 0.0000, 35, 1),
(42100, 35, 'revenue', 'credit', 3, 0, 0.0000, 36, 1),

-- المصروفات (Expenses) - 5xxxx
(50000, NULL, 'expense', 'debit', 1, 1, 0.0000, 37, 1),
(51000, 37, 'expense', 'debit', 2, 1, 0.0000, 38, 1),
(51100, 38, 'expense', 'debit', 3, 0, 0.0000, 39, 1),
(52000, 37, 'expense', 'debit', 2, 1, 0.0000, 40, 1),
(52100, 40, 'expense', 'debit', 3, 0, 0.0000, 41, 1),
(52200, 40, 'expense', 'debit', 3, 0, 0.0000, 42, 1),
(53000, 37, 'expense', 'debit', 2, 1, 0.0000, 43, 1),
(53100, 43, 'expense', 'debit', 3, 0, 0.0000, 44, 1),
(53200, 43, 'expense', 'debit', 3, 0, 0.0000, 45, 1);

-- إدراج أوصاف الحسابات باللغة العربية
INSERT INTO `cod_account_description` (`account_id`, `language_id`, `name`, `description`) VALUES
-- الأصول
(1, 1, 'الأصول', 'إجمالي الأصول'),
(2, 1, 'الأصول المتداولة', 'الأصول قصيرة الأجل'),
(3, 1, 'النقدية في الصندوق', 'النقدية المتوفرة في الصندوق'),
(4, 1, 'البنوك', 'الأرصدة في البنوك'),
(5, 1, 'الاستثمارات قصيرة الأجل', 'استثمارات مؤقتة'),
(6, 1, 'الذمم المدينة', 'المبالغ المستحقة من العملاء'),
(7, 1, 'العملاء', 'حسابات العملاء'),
(8, 1, 'أوراق القبض', 'الكمبيالات والسندات الإذنية'),
(9, 1, 'المخزون', 'البضائع والمواد الخام'),
(10, 1, 'مخزون البضائع', 'بضائع جاهزة للبيع'),
(11, 1, 'مخزون المواد الخام', 'مواد خام للإنتاج'),
(12, 1, 'الأصول الثابتة', 'الأصول طويلة الأجل'),
(13, 1, 'الأراضي والمباني', 'العقارات'),
(14, 1, 'الآلات والمعدات', 'المعدات الإنتاجية'),
(15, 1, 'الأصول غير الملموسة', 'براءات الاختراع والعلامات التجارية'),
(16, 1, 'الشهرة', 'شهرة المحل'),
(17, 1, 'برامج الحاسوب', 'البرمجيات والتطبيقات'),

-- الخصوم
(18, 1, 'الخصوم', 'إجمالي الخصوم'),
(19, 1, 'الخصوم المتداولة', 'الالتزامات قصيرة الأجل'),
(20, 1, 'الموردين', 'المبالغ المستحقة للموردين'),
(21, 1, 'أوراق الدفع', 'الكمبيالات المستحقة الدفع'),
(22, 1, 'الخصوم الضريبية', 'الضرائب المستحقة'),
(23, 1, 'ضريبة القيمة المضافة', 'ضريبة القيمة المضافة المستحقة'),
(24, 1, 'ضريبة الدخل', 'ضريبة الدخل المستحقة'),
(25, 1, 'الخصوم طويلة الأجل', 'القروض والالتزامات طويلة الأجل'),
(26, 1, 'القروض طويلة الأجل', 'قروض بنكية طويلة الأجل'),

-- حقوق الملكية
(27, 1, 'حقوق الملكية', 'حقوق أصحاب المنشأة'),
(28, 1, 'رأس المال', 'رأس المال المدفوع'),
(29, 1, 'الأرباح المحتجزة', 'الأرباح غير الموزعة'),
(30, 1, 'أرباح السنة الحالية', 'صافي ربح السنة الجارية'),

-- الإيرادات
(31, 1, 'الإيرادات', 'إجمالي الإيرادات'),
(32, 1, 'إيرادات المبيعات', 'إيرادات من بيع البضائع'),
(33, 1, 'مبيعات البضائع', 'مبيعات البضائع الجاهزة'),
(34, 1, 'مبيعات الخدمات', 'إيرادات من تقديم الخدمات'),
(35, 1, 'الإيرادات الأخرى', 'إيرادات متنوعة'),
(36, 1, 'إيرادات الاستثمار', 'أرباح من الاستثمارات'),

-- المصروفات
(37, 1, 'المصروفات', 'إجمالي المصروفات'),
(38, 1, 'تكلفة المبيعات', 'تكلفة البضائع المباعة'),
(39, 1, 'تكلفة البضائع المباعة', 'التكلفة المباشرة للمبيعات'),
(40, 1, 'المصروفات التشغيلية', 'مصروفات العمليات'),
(41, 1, 'مصروفات الرواتب', 'رواتب وأجور الموظفين'),
(42, 1, 'مصروفات الإيجار', 'إيجار المباني والمعدات'),
(43, 1, 'المصروفات الإدارية', 'مصروفات إدارية عامة'),
(44, 1, 'مصروفات الكهرباء', 'فواتير الكهرباء والمياه'),
(45, 1, 'مصروفات الاتصالات', 'فواتير الهاتف والإنترنت');

-- إدراج أوصاف الحسابات باللغة الإنجليزية
INSERT INTO `cod_account_description` (`account_id`, `language_id`, `name`, `description`) VALUES
-- Assets
(1, 2, 'Assets', 'Total Assets'),
(2, 2, 'Current Assets', 'Short-term Assets'),
(3, 2, 'Cash on Hand', 'Cash available in treasury'),
(4, 2, 'Banks', 'Bank balances'),
(5, 2, 'Short-term Investments', 'Temporary investments'),
(6, 2, 'Accounts Receivable', 'Amounts due from customers'),
(7, 2, 'Customers', 'Customer accounts'),
(8, 2, 'Notes Receivable', 'Bills and promissory notes'),
(9, 2, 'Inventory', 'Goods and raw materials'),
(10, 2, 'Finished Goods Inventory', 'Ready-to-sell goods'),
(11, 2, 'Raw Materials Inventory', 'Raw materials for production'),
(12, 2, 'Fixed Assets', 'Long-term assets'),
(13, 2, 'Land and Buildings', 'Real estate'),
(14, 2, 'Machinery and Equipment', 'Production equipment'),
(15, 2, 'Intangible Assets', 'Patents and trademarks'),
(16, 2, 'Goodwill', 'Business goodwill'),
(17, 2, 'Computer Software', 'Software and applications'),

-- Liabilities
(18, 2, 'Liabilities', 'Total Liabilities'),
(19, 2, 'Current Liabilities', 'Short-term obligations'),
(20, 2, 'Suppliers', 'Amounts due to suppliers'),
(21, 2, 'Notes Payable', 'Bills payable'),
(22, 2, 'Tax Liabilities', 'Taxes payable'),
(23, 2, 'VAT Payable', 'Value Added Tax payable'),
(24, 2, 'Income Tax Payable', 'Income tax payable'),
(25, 2, 'Long-term Liabilities', 'Long-term loans and obligations'),
(26, 2, 'Long-term Loans', 'Long-term bank loans'),

-- Equity
(27, 2, 'Equity', 'Owner\'s equity'),
(28, 2, 'Capital', 'Paid-in capital'),
(29, 2, 'Retained Earnings', 'Undistributed profits'),
(30, 2, 'Current Year Profit', 'Net profit for current year'),

-- Revenue
(31, 2, 'Revenue', 'Total Revenue'),
(32, 2, 'Sales Revenue', 'Revenue from goods sales'),
(33, 2, 'Goods Sales', 'Finished goods sales'),
(34, 2, 'Service Sales', 'Revenue from services'),
(35, 2, 'Other Revenue', 'Miscellaneous revenue'),
(36, 2, 'Investment Income', 'Profits from investments'),

-- Expenses
(37, 2, 'Expenses', 'Total Expenses'),
(38, 2, 'Cost of Sales', 'Cost of goods sold'),
(39, 2, 'Cost of Goods Sold', 'Direct cost of sales'),
(40, 2, 'Operating Expenses', 'Operational expenses'),
(41, 2, 'Salary Expenses', 'Employee salaries and wages'),
(42, 2, 'Rent Expenses', 'Building and equipment rent'),
(43, 2, 'Administrative Expenses', 'General administrative expenses'),
(44, 2, 'Utility Expenses', 'Electricity and water bills'),
(45, 2, 'Communication Expenses', 'Phone and internet bills');

-- إدراج مراكز التكلفة الأساسية
INSERT INTO `cod_cost_center` (`cost_center_code`, `cost_center_name`, `parent_id`, `description`, `created_by`) VALUES
('CC001', 'الإدارة العامة', NULL, 'مركز تكلفة الإدارة العامة', 1),
('CC002', 'المبيعات', NULL, 'مركز تكلفة المبيعات', 1),
('CC003', 'المشتريات', NULL, 'مركز تكلفة المشتريات', 1),
('CC004', 'المخازن', NULL, 'مركز تكلفة المخازن', 1),
('CC005', 'المحاسبة', NULL, 'مركز تكلفة المحاسبة', 1),
('CC006', 'الموارد البشرية', NULL, 'مركز تكلفة الموارد البشرية', 1),
('CC007', 'تقنية المعلومات', NULL, 'مركز تكلفة تقنية المعلومات', 1);

-- إدراج فترة محاسبية افتراضية
INSERT INTO `cod_accounting_period` (`period_name`, `start_date`, `end_date`, `fiscal_year`, `period_type`, `created_by`) VALUES
('يناير 2024', '2024-01-01', '2024-01-31', 2024, 'monthly', 1),
('فبراير 2024', '2024-02-01', '2024-02-29', 2024, 'monthly', 1),
('مارس 2024', '2024-03-01', '2024-03-31', 2024, 'monthly', 1),
('الربع الأول 2024', '2024-01-01', '2024-03-31', 2024, 'quarterly', 1),
('السنة المالية 2024', '2024-01-01', '2024-12-31', 2024, 'yearly', 1);

-- إضافة المفاتيح الخارجية
ALTER TABLE `cod_accounts`
  ADD CONSTRAINT `fk_accounts_parent` FOREIGN KEY (`parent_id`) REFERENCES `cod_accounts` (`account_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_accounts_created_by` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`);

ALTER TABLE `cod_account_description`
  ADD CONSTRAINT `fk_account_desc_account` FOREIGN KEY (`account_id`) REFERENCES `cod_accounts` (`account_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_account_desc_language` FOREIGN KEY (`language_id`) REFERENCES `cod_language` (`language_id`);

ALTER TABLE `cod_journal_entry`
  ADD CONSTRAINT `fk_journal_period` FOREIGN KEY (`period_id`) REFERENCES `cod_accounting_period` (`period_id`),
  ADD CONSTRAINT `fk_journal_branch` FOREIGN KEY (`branch_id`) REFERENCES `cod_branch` (`branch_id`),
  ADD CONSTRAINT `fk_journal_created_by` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`),
  ADD CONSTRAINT `fk_journal_posted_by` FOREIGN KEY (`posted_by`) REFERENCES `cod_user` (`user_id`),
  ADD CONSTRAINT `fk_journal_reversed` FOREIGN KEY (`reversed_journal_id`) REFERENCES `cod_journal_entry` (`journal_id`);

ALTER TABLE `cod_journal_entry_line`
  ADD CONSTRAINT `fk_journal_line_journal` FOREIGN KEY (`journal_id`) REFERENCES `cod_journal_entry` (`journal_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_journal_line_account` FOREIGN KEY (`account_id`) REFERENCES `cod_accounts` (`account_id`),
  ADD CONSTRAINT `fk_journal_line_cost_center` FOREIGN KEY (`cost_center_id`) REFERENCES `cod_cost_center` (`cost_center_id`),
  ADD CONSTRAINT `fk_journal_line_currency` FOREIGN KEY (`currency_id`) REFERENCES `cod_currency` (`currency_id`);

ALTER TABLE `cod_accounting_period`
  ADD CONSTRAINT `fk_period_created_by` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`),
  ADD CONSTRAINT `fk_period_closed_by` FOREIGN KEY (`closed_by`) REFERENCES `cod_user` (`user_id`);

ALTER TABLE `cod_account_balance`
  ADD CONSTRAINT `fk_balance_account` FOREIGN KEY (`account_id`) REFERENCES `cod_accounts` (`account_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_balance_period` FOREIGN KEY (`period_id`) REFERENCES `cod_accounting_period` (`period_id`),
  ADD CONSTRAINT `fk_balance_branch` FOREIGN KEY (`branch_id`) REFERENCES `cod_branch` (`branch_id`);

ALTER TABLE `cod_cost_center`
  ADD CONSTRAINT `fk_cost_center_parent` FOREIGN KEY (`parent_id`) REFERENCES `cod_cost_center` (`cost_center_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cost_center_created_by` FOREIGN KEY (`created_by`) REFERENCES `cod_user` (`user_id`);

ALTER TABLE `cod_accounting_settings`
  ADD CONSTRAINT `fk_accounting_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `cod_user` (`user_id`);

-- إنشاء فهارس إضافية لتحسين الأداء
CREATE INDEX idx_accounts_code_type ON cod_accounts (account_code, account_type);
CREATE INDEX idx_accounts_parent_level ON cod_accounts (parent_id, level);
CREATE INDEX idx_journal_date_status ON cod_journal_entry (journal_date, status);
CREATE INDEX idx_journal_reference ON cod_journal_entry (reference_type, reference_id);
CREATE INDEX idx_journal_line_amounts ON cod_journal_entry_line (debit_amount, credit_amount);
CREATE INDEX idx_balance_account_period ON cod_account_balance (account_id, period_id);

-- إنشاء Views مفيدة للتقارير
CREATE VIEW vw_chart_of_accounts AS
SELECT
    a.account_id,
    a.account_code,
    a.parent_id,
    a.account_type,
    a.account_nature,
    a.level,
    a.is_parent,
    a.is_active,
    a.allow_posting,
    a.current_balance,
    a.opening_balance,
    a.sort_order,
    ad.name AS account_name,
    ad.description AS account_description,
    CASE
        WHEN a.parent_id IS NULL THEN a.account_code
        ELSE CONCAT(pa.account_code, ' - ', a.account_code)
    END AS full_account_code,
    CASE
        WHEN a.parent_id IS NULL THEN ad.name
        ELSE CONCAT(pad.name, ' - ', ad.name)
    END AS full_account_name
FROM cod_accounts a
LEFT JOIN cod_account_description ad ON a.account_id = ad.account_id AND ad.language_id = 1
LEFT JOIN cod_accounts pa ON a.parent_id = pa.account_id
LEFT JOIN cod_account_description pad ON pa.account_id = pad.account_id AND pad.language_id = 1
WHERE a.is_active = 1
ORDER BY a.account_code;

CREATE VIEW vw_journal_entries AS
SELECT
    je.journal_id,
    je.journal_number,
    je.journal_date,
    je.reference_type,
    je.reference_id,
    je.reference_number,
    je.description,
    je.total_debit,
    je.total_credit,
    je.status,
    je.is_auto,
    je.is_reversed,
    ap.period_name,
    b.name AS branch_name,
    CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS created_by_name,
    je.date_added
FROM cod_journal_entry je
LEFT JOIN cod_accounting_period ap ON je.period_id = ap.period_id
LEFT JOIN cod_branch b ON je.branch_id = b.branch_id
LEFT JOIN cod_user u ON je.created_by = u.user_id
ORDER BY je.journal_date DESC, je.journal_number DESC;

CREATE VIEW vw_account_balances AS
SELECT
    a.account_id,
    a.account_code,
    ad.name AS account_name,
    a.account_type,
    a.account_nature,
    a.current_balance,
    CASE
        WHEN a.account_nature = 'debit' AND a.current_balance >= 0 THEN a.current_balance
        WHEN a.account_nature = 'credit' AND a.current_balance < 0 THEN ABS(a.current_balance)
        ELSE 0
    END AS debit_balance,
    CASE
        WHEN a.account_nature = 'credit' AND a.current_balance >= 0 THEN a.current_balance
        WHEN a.account_nature = 'debit' AND a.current_balance < 0 THEN ABS(a.current_balance)
        ELSE 0
    END AS credit_balance
FROM cod_accounts a
LEFT JOIN cod_account_description ad ON a.account_id = ad.account_id AND ad.language_id = 1
WHERE a.is_active = 1 AND a.allow_posting = 1
ORDER BY a.account_code;

CREATE VIEW vw_trial_balance AS
SELECT
    a.account_code,
    ad.name AS account_name,
    a.account_type,
    SUM(CASE WHEN jel.debit_amount > 0 THEN jel.debit_amount ELSE 0 END) AS total_debit,
    SUM(CASE WHEN jel.credit_amount > 0 THEN jel.credit_amount ELSE 0 END) AS total_credit,
    SUM(jel.debit_amount - jel.credit_amount) AS balance
FROM cod_accounts a
LEFT JOIN cod_account_description ad ON a.account_id = ad.account_id AND ad.language_id = 1
LEFT JOIN cod_journal_entry_line jel ON a.account_id = jel.account_id
LEFT JOIN cod_journal_entry je ON jel.journal_id = je.journal_id AND je.status = 'posted'
WHERE a.is_active = 1 AND a.allow_posting = 1
GROUP BY a.account_id, a.account_code, ad.name, a.account_type
HAVING SUM(jel.debit_amount - jel.credit_amount) != 0
ORDER BY a.account_code;

-- إنشاء Triggers لتحديث الأرصدة تلقائياً
DELIMITER $$

CREATE TRIGGER tr_update_account_balance_after_journal_post
AFTER UPDATE ON cod_journal_entry
FOR EACH ROW
BEGIN
    IF NEW.status = 'posted' AND OLD.status != 'posted' THEN
        -- تحديث أرصدة الحسابات المتأثرة
        UPDATE cod_accounts a
        SET current_balance = (
            SELECT COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0)
            FROM cod_journal_entry_line jel
            JOIN cod_journal_entry je ON jel.journal_id = je.journal_id
            WHERE jel.account_id = a.account_id AND je.status = 'posted'
        )
        WHERE a.account_id IN (
            SELECT DISTINCT jel.account_id
            FROM cod_journal_entry_line jel
            WHERE jel.journal_id = NEW.journal_id
        );
    END IF;
END$$

CREATE TRIGGER tr_validate_journal_entry_balance
BEFORE UPDATE ON cod_journal_entry
FOR EACH ROW
BEGIN
    DECLARE debit_total DECIMAL(15,4);
    DECLARE credit_total DECIMAL(15,4);

    IF NEW.status = 'posted' THEN
        SELECT
            COALESCE(SUM(debit_amount), 0),
            COALESCE(SUM(credit_amount), 0)
        INTO debit_total, credit_total
        FROM cod_journal_entry_line
        WHERE journal_id = NEW.journal_id;

        IF debit_total != credit_total THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Journal entry is not balanced. Debit and credit totals must be equal.';
        END IF;

        SET NEW.total_debit = debit_total;
        SET NEW.total_credit = credit_total;
    END IF;
END$$

CREATE TRIGGER tr_auto_generate_journal_number
BEFORE INSERT ON cod_journal_entry
FOR EACH ROW
BEGIN
    DECLARE next_number INT;
    DECLARE prefix VARCHAR(10);

    IF NEW.journal_number IS NULL OR NEW.journal_number = '' THEN
        SELECT setting_value INTO prefix
        FROM cod_accounting_settings
        WHERE setting_key = 'journal_number_prefix';

        SELECT COALESCE(MAX(CAST(SUBSTRING(journal_number, LENGTH(prefix) + 1) AS UNSIGNED)), 0) + 1
        INTO next_number
        FROM cod_journal_entry
        WHERE journal_number LIKE CONCAT(prefix, '%')
        AND YEAR(journal_date) = YEAR(NEW.journal_date);

        SET NEW.journal_number = CONCAT(prefix, LPAD(next_number, 6, '0'));
    END IF;
END$$

DELIMITER ;

-- إنشاء stored procedures مفيدة
DELIMITER $$

CREATE PROCEDURE sp_get_account_statement(
    IN p_account_id INT,
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    SELECT
        je.journal_date,
        je.journal_number,
        je.description,
        jel.description AS line_description,
        jel.debit_amount,
        jel.credit_amount,
        @running_balance := @running_balance + (jel.debit_amount - jel.credit_amount) AS running_balance
    FROM cod_journal_entry_line jel
    JOIN cod_journal_entry je ON jel.journal_id = je.journal_id
    CROSS JOIN (SELECT @running_balance := 0) r
    WHERE jel.account_id = p_account_id
    AND je.journal_date BETWEEN p_start_date AND p_end_date
    AND je.status = 'posted'
    ORDER BY je.journal_date, je.journal_id, jel.line_id;
END$$

CREATE PROCEDURE sp_close_accounting_period(
    IN p_period_id INT,
    IN p_user_id INT
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_account_id INT;
    DECLARE v_balance DECIMAL(15,4);

    DECLARE balance_cursor CURSOR FOR
        SELECT account_id, current_balance
        FROM cod_accounts
        WHERE account_type IN ('revenue', 'expense')
        AND current_balance != 0;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    START TRANSACTION;

    -- إنشاء قيد إقفال الحسابات المؤقتة
    INSERT INTO cod_journal_entry (
        journal_date, description, period_id, created_by, status
    ) VALUES (
        (SELECT end_date FROM cod_accounting_period WHERE period_id = p_period_id),
        'قيد إقفال الحسابات المؤقتة',
        p_period_id,
        p_user_id,
        'posted'
    );

    SET @journal_id = LAST_INSERT_ID();

    -- إقفال حسابات الإيرادات والمصروفات
    OPEN balance_cursor;
    read_loop: LOOP
        FETCH balance_cursor INTO v_account_id, v_balance;
        IF done THEN
            LEAVE read_loop;
        END IF;

        IF v_balance != 0 THEN
            INSERT INTO cod_journal_entry_line (
                journal_id, account_id, debit_amount, credit_amount, description
            ) VALUES (
                @journal_id,
                v_account_id,
                CASE WHEN v_balance < 0 THEN ABS(v_balance) ELSE 0 END,
                CASE WHEN v_balance > 0 THEN v_balance ELSE 0 END,
                'إقفال الحساب'
            );
        END IF;
    END LOOP;
    CLOSE balance_cursor;

    -- تحديث حالة الفترة
    UPDATE cod_accounting_period
    SET status = 'closed', closed_by = p_user_id, closed_date = NOW()
    WHERE period_id = p_period_id;

    COMMIT;
END$$

DELIMITER ;
