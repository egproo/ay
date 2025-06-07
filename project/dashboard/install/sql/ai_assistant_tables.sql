-- جداول المساعد الذكي لنظام أيم ERP

-- جدول إعدادات المساعد الذكي
CREATE TABLE IF NOT EXISTS `cod_ai_assistant_settings` (
  `user_id` int(11) NOT NULL,
  `settings` text NOT NULL,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- جدول محادثات المساعد الذكي
CREATE TABLE IF NOT EXISTS `cod_ai_assistant_conversation` (
  `conversation_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `sender` varchar(10) NOT NULL,
  `message` text NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`conversation_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;