<?php
class ControllerAccountingSettings extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounting/settings');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('accounting', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('accounting/settings', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounting/settings', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('accounting/settings', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        // Load accounts
        $this->load->model('accounting/accounting_manager');
        $data['accounts'] = $this->model_accounting_accounting_manager->getAccounts();

        // Load settings
        if (isset($this->request->post['accounting_status'])) {
            $data['accounting_status'] = $this->request->post['accounting_status'];
        } else {
            $data['accounting_status'] = $this->config->get('accounting_status');
        }

        // Inventory account mappings
        $mappings = array(
            'purchase',
            'sale',
            'adjustment_increase',
            'adjustment_decrease',
            'transfer_in',
            'transfer_out',
            'initial',
            'return_in',
            'return_out',
            'scrap',
            'production',
            'consumption',
            'cost_adjustment'
        );

        foreach ($mappings as $mapping) {
            if (isset($this->request->post['accounting_' . $mapping . '_inventory_account'])) {
                $data['accounting_' . $mapping . '_inventory_account'] = $this->request->post['accounting_' . $mapping . '_inventory_account'];
            } else {
                $data['accounting_' . $mapping . '_inventory_account'] = $this->config->get('accounting_' . $mapping . '_inventory_account');
            }

            if (isset($this->request->post['accounting_' . $mapping . '_contra_account'])) {
                $data['accounting_' . $mapping . '_contra_account'] = $this->request->post['accounting_' . $mapping . '_contra_account'];
            } else {
                $data['accounting_' . $mapping . '_contra_account'] = $this->config->get('accounting_' . $mapping . '_contra_account');
            }
        }

        // Load existing mappings
        $existing_mappings = $this->model_accounting_accounting_manager->getInventoryAccountMappings();
        $data['existing_mappings'] = $existing_mappings;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounting/settings', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'accounting/settings')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function install() {
        // Create necessary tables
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "accounting_account` (
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
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "accounting_journal` (
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
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "accounting_journal_entry` (
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
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "accounting_inventory_mapping` (
              `mapping_id` int(11) NOT NULL AUTO_INCREMENT,
              `transaction_type` varchar(64) NOT NULL COMMENT 'purchase, sale, adjustment_increase, etc.',
              `inventory_account_id` int(11) NOT NULL,
              `contra_account_id` int(11) NOT NULL,
              `description` varchar(255) NOT NULL,
              PRIMARY KEY (`mapping_id`),
              UNIQUE KEY `transaction_type` (`transaction_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        // Add journal_id column to stock_movement table if it doesn't exist
        $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "stock_movement` LIKE 'journal_id'");
        if ($query->num_rows == 0) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "stock_movement` ADD `journal_id` int(11) DEFAULT NULL AFTER `user_id`");
        }

        // Add value_change column to stock_movement table if it doesn't exist
        $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "stock_movement` LIKE 'value_change'");
        if ($query->num_rows == 0) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "stock_movement` ADD `value_change` decimal(15,4) NOT NULL DEFAULT '0.0000' AFTER `new_cost`");
        }

        // Insert default accounts if table is empty
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "accounting_account`");
        if ($query->row['total'] == 0) {
            $this->insertDefaultAccounts();
        }

        // Insert default mappings if table is empty
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "accounting_inventory_mapping`");
        if ($query->row['total'] == 0) {
            $this->insertDefaultMappings();
        }

        // Add to menu
        $this->load->model('user/user_group');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'accounting/settings');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'accounting/settings');
    }

    private function insertDefaultAccounts() {
        $accounts = array(
            array('1000', 'Assets', 'Asset accounts', 'asset', NULL, 1),
            array('1100', 'Current Assets', 'Current asset accounts', 'asset', 1, 1),
            array('1200', 'Inventory Assets', 'Inventory asset accounts', 'asset', 1, 1),
            array('1300', 'Fixed Assets', 'Fixed asset accounts', 'asset', 1, 1),
            array('2000', 'Liabilities', 'Liability accounts', 'liability', NULL, 1),
            array('2100', 'Current Liabilities', 'Current liability accounts', 'liability', 5, 1),
            array('2200', 'Long-term Liabilities', 'Long-term liability accounts', 'liability', 5, 1),
            array('3000', 'Equity', 'Equity accounts', 'equity', NULL, 1),
            array('4000', 'Revenue', 'Revenue accounts', 'revenue', NULL, 1),
            array('5000', 'Expenses', 'Expense accounts', 'expense', NULL, 1),
            array('1210', 'Merchandise Inventory', 'Inventory of goods for sale', 'asset', 3, 1),
            array('1220', 'Raw Materials Inventory', 'Inventory of raw materials', 'asset', 3, 1),
            array('1230', 'Work in Process Inventory', 'Inventory of partially completed goods', 'asset', 3, 1),
            array('1240', 'Finished Goods Inventory', 'Inventory of completed goods', 'asset', 3, 1),
            array('5100', 'Cost of Goods Sold', 'Cost of goods sold expense', 'expense', 10, 1),
            array('5200', 'Inventory Adjustment', 'Inventory adjustment expense', 'expense', 10, 1),
            array('2110', 'Accounts Payable', 'Amounts owed to suppliers', 'liability', 6, 1),
            array('4100', 'Sales Revenue', 'Revenue from sales', 'revenue', 9, 1)
        );

        foreach ($accounts as $account) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "accounting_account` 
                (`code`, `name`, `description`, `type`, `parent_id`, `status`, `date_added`, `date_modified`) 
                VALUES ('" . $this->db->escape($account[0]) . "', '" . $this->db->escape($account[1]) . "', 
                '" . $this->db->escape($account[2]) . "', '" . $this->db->escape($account[3]) . "', 
                " . ($account[4] === NULL ? "NULL" : (int)$account[4]) . ", " . (int)$account[5] . ", 
                NOW(), NOW())");
        }
    }

    private function insertDefaultMappings() {
        $mappings = array(
            array('purchase', 11, 17, 'Purchase of inventory'),
            array('sale', 15, 11, 'Sale of inventory'),
            array('adjustment_increase', 11, 16, 'Inventory adjustment increase'),
            array('adjustment_decrease', 16, 11, 'Inventory adjustment decrease'),
            array('transfer_in', 11, 11, 'Inventory transfer in'),
            array('transfer_out', 11, 11, 'Inventory transfer out'),
            array('initial', 11, 8, 'Initial inventory setup'),
            array('return_in', 11, 15, 'Return of inventory from customer'),
            array('return_out', 17, 11, 'Return of inventory to supplier'),
            array('scrap', 16, 11, 'Scrapping of inventory'),
            array('production', 11, 13, 'Production of inventory'),
            array('consumption', 13, 11, 'Consumption of inventory'),
            array('cost_adjustment', 16, 11, 'Cost adjustment of inventory')
        );

        foreach ($mappings as $mapping) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "accounting_inventory_mapping` 
                (`transaction_type`, `inventory_account_id`, `contra_account_id`, `description`) 
                VALUES ('" . $this->db->escape($mapping[0]) . "', " . (int)$mapping[1] . ", 
                " . (int)$mapping[2] . ", '" . $this->db->escape($mapping[3]) . "')");
        }
    }

    public function uninstall() {
        // Remove from menu
        $this->load->model('user/user_group');
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'accounting/settings');
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'accounting/settings');
    }
}
