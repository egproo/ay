<?php
/**
 * @package     AYM CMS
 * @author      Team AYM <info@aymcms.com>
 * @copyright   Copyright (c) 2021 AYM. (https://www.aymcms.com)
 * @license     https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 */

class ControllerExtensionMessageInstall extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/message/install');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/message/install', 'user_token=' . $this->session->data['user_token'])
        );
        
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_installation'] = $this->language->get('text_installation');
        $data['text_install_description'] = $this->language->get('text_install_description');
        $data['text_prerequisites'] = $this->language->get('text_prerequisites');
        $data['text_steps'] = $this->language->get('text_steps');
        
        $data['button_install'] = $this->language->get('button_install');
        $data['button_cancel'] = $this->language->get('button_cancel');
        
        $data['action'] = $this->url->link('extension/message/install/install', 'user_token=' . $this->session->data['user_token']);
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']);
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/message/install', $data));
    }
    
    public function install() {
        $this->load->language('extension/message/install');
        
        if (!$this->user->hasPermission('modify', 'extension/message/install')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (!$this->error) {
            $this->load->model('extension/message/install');
            
            try {
                // Create database tables
                $this->model_extension_message_install->createTables();
                
                // Create upload directory
                $this->model_extension_message_install->createDirectories();
                
                // Add menu item
                $this->model_extension_message_install->addMenuItem();
                
                // Set permissions
                $this->model_extension_message_install->addPermissions();
                
                $this->session->data['success'] = $this->language->get('text_success');
                
                $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']));
            } catch (Exception $e) {
                $this->error['warning'] = $e->getMessage();
                
                $this->index();
            }
        } else {
            $this->index();
        }
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'extension/message/install')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
    
    private function createUploadDirectory() {
        $directory = DIR_UPLOAD . 'message_attachments';
        
        if (!is_dir($directory)) {
            @mkdir($directory, 0777, true);
            
            // Create index.html file for security
            $index_file = fopen($directory . '/index.html', 'w');
            fwrite($index_file, '<html><head><title>403 Forbidden</title></head><body><p>Directory access is forbidden.</p></body></html>');
            fclose($index_file);
        }
    }
    
    private function addMenuItem() {
        // Add to admin menu
        $this->load->model('setting/setting');
        
        $menus = $this->model_setting_setting->getSetting('menu_items');
        
        if (!isset($menus['menu_items'])) {
            $menus['menu_items'] = array();
        }
        
        // Check if menu already exists
        $exists = false;
        foreach ($menus['menu_items'] as $menu) {
            if (isset($menu['route']) && $menu['route'] == 'extension/message/message') {
                $exists = true;
                break;
            }
        }
        
        if (!$exists) {
            $menus['menu_items'][] = array(
                'name' => 'Messaging System',
                'icon' => 'fa-envelope',
                'route' => 'extension/message/message',
                'parent' => 'tools',
                'sort_order' => 5,
                'status' => 1
            );
            
            $this->model_setting_setting->editSetting('menu_items', $menus);
        }
    }
    
    public function uninstall() {
        // Remove database tables
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message_history`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message_attachment`");
        
        // Remove from extension
        $this->load->model('setting/extension');
        $this->model_setting_extension->uninstall('extension', 'message');
        
        // Remove from menu
        $this->load->model('setting/setting');
        
        $menus = $this->model_setting_setting->getSetting('menu_items');
        
        if (isset($menus['menu_items'])) {
            foreach ($menus['menu_items'] as $key => $menu) {
                if (isset($menu['route']) && $menu['route'] == 'extension/message/message') {
                    unset($menus['menu_items'][$key]);
                    break;
                }
            }
            
            $this->model_setting_setting->editSetting('menu_items', $menus);
        }
        
        $this->session->data['success'] = $this->language->get('text_success_uninstall');
    }
} 