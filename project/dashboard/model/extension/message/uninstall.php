<?php
class ModelExtensionMessageUninstall extends Model {
    public function uninstall() {
        $this->dropTables();
        $this->removeDirectories();
        $this->removeMenuItem();
        $this->removePermissions();
    }
    
    protected function dropTables() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message_attachment`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message_recipient`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message`");
    }
    
    protected function removeDirectories() {
        // We'll only remove directory if it's empty
        $directory = DIR_IMAGE . 'catalog/message';
        
        if (is_dir($directory)) {
            // First delete the index.html file
            if (file_exists($directory . '/index.html')) {
                unlink($directory . '/index.html');
            }
            
            // Check if directory is empty before removing
            if (count(scandir($directory)) <= 2) { // . and .. are always present
                rmdir($directory);
            }
        }
    }
    
    protected function removeMenuItem() {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'message'");
    }
    
    protected function removePermissions() {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "user_group_permission` WHERE `permission` LIKE 'extension/message%'");
    }
} 