-- Accounting Tables for Inventory Management System

-- Accounting Accounts Table
CREATE TABLE IF NOT EXISTS `oc_accounting_account` (
  `account_id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `name` varchar(128) NOT NULL,
  `description` text,
  `type` varchar(32) NOT NULL COMMENT 'asset, liability, equity, revenue, expense',
  `parent_id` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Accounting Journal Table
CREATE TABLE IF NOT EXISTS `oc_accounting_journal` (
  `journal_id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_type` varchar(64) NOT NULL COMMENT 'inventory_movement, purchase, sale, etc.',
  `reference_id` int(11) NOT NULL,
  `description` text,
  `date_added` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`journal_id`),
  KEY `reference_type` (`reference_type`,`reference_id`),
  KEY `date_added` (`date_added`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Accounting Journal Entry Table
CREATE TABLE IF NOT EXISTS `oc_accounting_journal_entry` (
  `journal_entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `journal_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `credit` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `description` text,
  PRIMARY KEY (`journal_entry_id`),
  KEY `journal_id` (`journal_id`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Accounting Inventory Mapping Table
CREATE TABLE IF NOT EXISTS `oc_accounting_inventory_mapping` (
  `mapping_id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_type` varchar(64) NOT NULL COMMENT 'purchase, sale, adjustment_increase, etc.',
  `inventory_account_id` int(11) NOT NULL,
  `contra_account_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`mapping_id`),
  UNIQUE KEY `transaction_type` (`transaction_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Insert default accounts
INSERT INTO `oc_accounting_account` (`code`, `name`, `description`, `type`, `parent_id`, `status`, `date_added`, `date_modified`) VALUES
('1000', 'Assets', 'Asset accounts', 'asset', NULL, 1, NOW(), NOW()),
('1100', 'Current Assets', 'Current asset accounts', 'asset', 1, 1, NOW(), NOW()),
('1200', 'Inventory Assets', 'Inventory asset accounts', 'asset', 1, 1, NOW(), NOW()),
('1300', 'Fixed Assets', 'Fixed asset accounts', 'asset', 1, 1, NOW(), NOW()),
('2000', 'Liabilities', 'Liability accounts', 'liability', NULL, 1, NOW(), NOW()),
('2100', 'Current Liabilities', 'Current liability accounts', 'liability', 5, 1, NOW(), NOW()),
('2200', 'Long-term Liabilities', 'Long-term liability accounts', 'liability', 5, 1, NOW(), NOW()),
('3000', 'Equity', 'Equity accounts', 'equity', NULL, 1, NOW(), NOW()),
('4000', 'Revenue', 'Revenue accounts', 'revenue', NULL, 1, NOW(), NOW()),
('5000', 'Expenses', 'Expense accounts', 'expense', NULL, 1, NOW(), NOW()),
('1210', 'Merchandise Inventory', 'Inventory of goods for sale', 'asset', 3, 1, NOW(), NOW()),
('1220', 'Raw Materials Inventory', 'Inventory of raw materials', 'asset', 3, 1, NOW(), NOW()),
('1230', 'Work in Process Inventory', 'Inventory of partially completed goods', 'asset', 3, 1, NOW(), NOW()),
('1240', 'Finished Goods Inventory', 'Inventory of completed goods', 'asset', 3, 1, NOW(), NOW()),
('5100', 'Cost of Goods Sold', 'Cost of goods sold expense', 'expense', 10, 1, NOW(), NOW()),
('5200', 'Inventory Adjustment', 'Inventory adjustment expense', 'expense', 10, 1, NOW(), NOW()),
('2110', 'Accounts Payable', 'Amounts owed to suppliers', 'liability', 6, 1, NOW(), NOW()),
('4100', 'Sales Revenue', 'Revenue from sales', 'revenue', 9, 1, NOW(), NOW());

-- Insert default inventory mappings
INSERT INTO `oc_accounting_inventory_mapping` (`transaction_type`, `inventory_account_id`, `contra_account_id`, `description`) VALUES
('purchase', 11, 17, 'Purchase of inventory'),
('sale', 15, 11, 'Sale of inventory'),
('adjustment_increase', 11, 16, 'Inventory adjustment increase'),
('adjustment_decrease', 16, 11, 'Inventory adjustment decrease'),
('transfer_in', 11, 11, 'Inventory transfer in'),
('transfer_out', 11, 11, 'Inventory transfer out'),
('initial', 11, 8, 'Initial inventory setup'),
('return_in', 11, 15, 'Return of inventory from customer'),
('return_out', 17, 11, 'Return of inventory to supplier'),
('scrap', 16, 11, 'Scrapping of inventory'),
('production', 11, 13, 'Production of inventory'),
('consumption', 13, 11, 'Consumption of inventory'),
('cost_adjustment', 16, 11, 'Cost adjustment of inventory');
