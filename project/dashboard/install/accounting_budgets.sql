-- Accounting Budgets Tables for Inventory Management System

-- Budget Table
CREATE TABLE IF NOT EXISTS `oc_accounting_budget` (
  `budget_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `description` text,
  `year` int(4) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`budget_id`),
  KEY `year` (`year`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Budget Details Table
CREATE TABLE IF NOT EXISTS `oc_accounting_budget_account` (
  `budget_account_id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `january` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `february` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `march` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `april` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `may` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `june` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `july` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `august` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `september` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `october` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `november` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `december` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `total` decimal(15,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`budget_account_id`),
  KEY `budget_id` (`budget_id`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Budget Notes Table
CREATE TABLE IF NOT EXISTS `oc_accounting_budget_note` (
  `note_id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `month` int(2) NOT NULL,
  `note` text NOT NULL,
  `date_added` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`note_id`),
  KEY `budget_id` (`budget_id`),
  KEY `account_id` (`account_id`),
  KEY `month` (`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Budget Versions Table
CREATE TABLE IF NOT EXISTS `oc_accounting_budget_version` (
  `version_id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `description` text,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`version_id`),
  KEY `budget_id` (`budget_id`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Budget Version Details Table
CREATE TABLE IF NOT EXISTS `oc_accounting_budget_version_account` (
  `version_account_id` int(11) NOT NULL AUTO_INCREMENT,
  `version_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `january` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `february` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `march` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `april` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `may` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `june` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `july` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `august` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `september` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `october` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `november` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `december` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `total` decimal(15,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`version_account_id`),
  KEY `version_id` (`version_id`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Insert default budget for current year if table is empty
INSERT INTO `oc_accounting_budget` (`name`, `description`, `year`, `status`, `date_added`, `date_modified`, `user_id`)
SELECT CONCAT('Budget ', YEAR(NOW())), 'Default budget', YEAR(NOW()), 1, NOW(), NOW(), 1
FROM DUAL
WHERE NOT EXISTS (SELECT * FROM `oc_accounting_budget` LIMIT 1);
