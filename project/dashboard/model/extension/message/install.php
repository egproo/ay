<?php
class ModelExtensionMessageInstall extends Model {
    public function createTables() {
        // Create messages table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "message` (
            `message_id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `subject` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `date_added` DATETIME NOT NULL,
            `read` TINYINT(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`message_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
        
        // Create message recipients table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "message_recipient` (
            `message_recipient_id` INT(11) NOT NULL AUTO_INCREMENT,
            `message_id` INT(11) NOT NULL,
            `user_id` INT(11) NOT NULL,
            `read` TINYINT(1) NOT NULL DEFAULT '0',
            `date_read` DATETIME DEFAULT NULL,
            PRIMARY KEY (`message_recipient_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
        
        // Create message attachments table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "message_attachment` (
            `attachment_id` INT(11) NOT NULL AUTO_INCREMENT,
            `message_id` INT(11) NOT NULL,
            `filename` VARCHAR(255) NOT NULL,
            `filepath` VARCHAR(255) NOT NULL,
            `filesize` INT(11) NOT NULL,
            `date_added` DATETIME NOT NULL,
            PRIMARY KEY (`attachment_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
    }
    
    public function createDirectories() {
        $dir = DIR_IMAGE . 'catalog/message';
        
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception($this->language->get('error_directory'));
            }
            
            // Create index.html file to prevent directory listing
            $indexFile = $dir . '/index.html';
            file_put_contents($indexFile, '<html><head><title>403 Forbidden</title></head><body><p>Directory access is forbidden.</p></body></html>');
        }
    }
    
    public function addMenuItem() {
        // Add main menu item
        $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` (`code`, `key`, `value`, `serialized`) VALUES 
            ('message', 'message_status', '1', '0')");
            
        // Add to menu
        $this->load->model('user/user_group');
        
        // Add permission for access
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/message/message');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/message/message');
    }
    
    public function addPermissions() {
        $this->load->model('user/user_group');
        
        // Add permissions for all admin user groups
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "user_group`");
        
        foreach ($query->rows as $user_group) {
            $this->model_user_user_group->addPermission($user_group['user_group_id'], 'access', 'extension/message/message');
            $this->model_user_user_group->addPermission($user_group['user_group_id'], 'modify', 'extension/message/message');
        }
    }
} 