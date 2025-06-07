-- Create communication table for storing message details
CREATE TABLE IF NOT EXISTS `oc_communication` (
  `communication_id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `sender_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `forwarded_id` int(11) DEFAULT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `is_draft` tinyint(1) NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `is_announcement` tinyint(1) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  `date_modified` datetime DEFAULT NULL,
  PRIMARY KEY (`communication_id`),
  KEY `sender_id` (`sender_id`),
  KEY `parent_id` (`parent_id`),
  KEY `forwarded_id` (`forwarded_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Create communication_recipient table for storing recipients
CREATE TABLE IF NOT EXISTS `oc_communication_recipient` (
  `communication_recipient_id` int(11) NOT NULL AUTO_INCREMENT,
  `communication_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `date_read` datetime DEFAULT NULL,
  PRIMARY KEY (`communication_recipient_id`),
  KEY `communication_id` (`communication_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Create communication_cc table for storing CC recipients
CREATE TABLE IF NOT EXISTS `oc_communication_cc` (
  `communication_cc_id` int(11) NOT NULL AUTO_INCREMENT,
  `communication_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`communication_cc_id`),
  KEY `communication_id` (`communication_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Create communication_group table for storing group recipients (for announcements)
CREATE TABLE IF NOT EXISTS `oc_communication_group` (
  `communication_group_id` int(11) NOT NULL AUTO_INCREMENT,
  `communication_id` int(11) NOT NULL,
  `user_group_id` int(11) NOT NULL,
  PRIMARY KEY (`communication_group_id`),
  KEY `communication_id` (`communication_id`),
  KEY `user_group_id` (`user_group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Create communication_attachment table for storing attachments
CREATE TABLE IF NOT EXISTS `oc_communication_attachment` (
  `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
  `communication_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `mask` varchar(255) NOT NULL,
  `size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attachment_id`),
  KEY `communication_id` (`communication_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci; 