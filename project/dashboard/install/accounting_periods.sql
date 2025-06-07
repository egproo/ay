-- Accounting Periods Tables for Inventory Management System

-- Accounting Periods Table
CREATE TABLE IF NOT EXISTS `oc_accounting_period` (
  `period_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `description` text,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=Open, 1=Closed, 2=Locked',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`period_id`),
  KEY `start_date` (`start_date`),
  KEY `end_date` (`end_date`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Accounting Period Closing History Table
CREATE TABLE IF NOT EXISTS `oc_accounting_period_closing` (
  `closing_id` int(11) NOT NULL AUTO_INCREMENT,
  `period_id` int(11) NOT NULL,
  `closing_date` datetime NOT NULL,
  `closing_notes` text,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`closing_id`),
  KEY `period_id` (`period_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Accounting Period Closing Journal Entries Table
CREATE TABLE IF NOT EXISTS `oc_accounting_period_closing_entry` (
  `closing_entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `closing_id` int(11) NOT NULL,
  `journal_id` int(11) NOT NULL,
  PRIMARY KEY (`closing_entry_id`),
  KEY `closing_id` (`closing_id`),
  KEY `journal_id` (`journal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Add period_id column to accounting_journal table if it doesn't exist
ALTER TABLE `oc_accounting_journal` ADD COLUMN IF NOT EXISTS `period_id` int(11) DEFAULT NULL AFTER `reference_id`;

-- Add index for period_id in accounting_journal table
ALTER TABLE `oc_accounting_journal` ADD INDEX IF NOT EXISTS `period_id` (`period_id`);

-- Insert default fiscal year if table is empty
INSERT INTO `oc_accounting_period` (`name`, `description`, `start_date`, `end_date`, `status`, `date_added`, `date_modified`, `user_id`)
SELECT 'Fiscal Year 2023', 'Default fiscal year', '2023-01-01', '2023-12-31', 0, NOW(), NOW(), 1
FROM DUAL
WHERE NOT EXISTS (SELECT * FROM `oc_accounting_period` LIMIT 1);
