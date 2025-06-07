<?php
class ControllerPosSettings extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('pos/settings');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('pos/settings');
        
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('pos', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('pos/settings', 'user_token=' . $this->session->data['user_token'], true));
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
            'href' => $this->url->link('pos/settings', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('pos/settings', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        // Get standard settings
        $settings = array(
            'pos_default_pricing_type',
            'pos_require_customer',
            'pos_auto_print_receipt',
            'pos_allow_discount',
            'pos_max_discount_percentage',
            'pos_allow_price_change',
            'pos_default_category',
            'pos_items_per_page',
            'pos_barcode_mode',
            'pos_default_payment_method',
            'pos_default_shipping_method',
            'pos_require_shift',
            'pos_enable_quick_sale'
        );

        foreach ($settings as $setting) {
            if (isset($this->request->post[$setting])) {
                $data[$setting] = $this->request->post[$setting];
            } else {
                $data[$setting] = $this->config->get($setting);
            }
        }

        // Get available terminals
        $this->load->model('pos/terminal');
        $data['terminals'] = $this->model_pos_terminal->getTerminals();

        // Get all user groups
        $this->load->model('user/user_group');
        $data['user_groups'] = $this->model_user_user_group->getUserGroups();

        $data['pricing_types'] = array(
            'retail' => $this->language->get('text_retail'),
            'wholesale' => $this->language->get('text_wholesale'),
            'half_wholesale' => $this->language->get('text_half_wholesale'),
            'custom' => $this->language->get('text_custom')
        );

        // Get categories for default category selection
        $this->load->model('catalog/category');
        $data['categories'] = $this->model_catalog_category->getCategories();

        // Payment Methods
        $this->load->model('setting/extension');
        $payment_methods = $this->model_setting_extension->getInstalled('payment');
        
        $data['payment_methods'] = array();
        
        foreach ($payment_methods as $payment) {
            if ($this->config->get('payment_' . $payment . '_status')) {
                $this->load->language('extension/payment/' . $payment);
                $data['payment_methods'][] = array(
                    'code' => $payment,
                    'title' => $this->language->get('heading_title')
                );
            }
        }

        // Shipping Methods
        $shipping_methods = $this->model_setting_extension->getInstalled('shipping');
        
        $data['shipping_methods'] = array();
        
        foreach ($shipping_methods as $shipping) {
            if ($this->config->get('shipping_' . $shipping . '_status')) {
                $this->load->language('extension/shipping/' . $shipping);
                $data['shipping_methods'][] = array(
                    'code' => $shipping,
                    'title' => $this->language->get('heading_title')
                );
            }
        }

        // Display tabs
        $data['tabs'] = array(
            'general' => array(
                'title' => $this->language->get('tab_general'),
                'icon' => 'fa-cog'
            ),
            'security' => array(
                'title' => $this->language->get('tab_security'),
                'icon' => 'fa-lock'
            ),
            'display' => array(
                'title' => $this->language->get('tab_display'),
                'icon' => 'fa-desktop'
            ),
            'printing' => array(
                'title' => $this->language->get('tab_printing'),
                'icon' => 'fa-print'
            ),
            'terminals' => array(
                'title' => $this->language->get('tab_terminals'),
                'icon' => 'fa-laptop'
            )
        );

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('pos/settings', $data));
    }

    public function terminal() {
        $this->load->language('pos/settings');
        $this->document->setTitle($this->language->get('heading_title_terminal'));
        $this->load->model('pos/terminal');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateTerminal()) {
            $terminal_id = isset($this->request->get['terminal_id']) ? $this->request->get['terminal_id'] : 0;
            
            if ($terminal_id) {
                $this->model_pos_terminal->editTerminal($terminal_id, $this->request->post);
                $this->session->data['success'] = $this->language->get('text_success_edit');
            } else {
                $this->model_pos_terminal->addTerminal($this->request->post);
                $this->session->data['success'] = $this->language->get('text_success_add');
            }
            
            $this->response->redirect($this->url->link('pos/settings', 'user_token=' . $this->session->data['user_token'] . '&tab=terminals', true));
        }

        if (isset($this->request->get['terminal_id'])) {
            $terminal_id = $this->request->get['terminal_id'];
            $data['action'] = $this->url->link('pos/settings/terminal', 'user_token=' . $this->session->data['user_token'] . '&terminal_id=' . $terminal_id, true);
            $terminal_info = $this->model_pos_terminal->getTerminal($terminal_id);
        } else {
            $terminal_id = 0;
            $data['action'] = $this->url->link('pos/settings/terminal', 'user_token=' . $this->session->data['user_token'], true);
            $terminal_info = array();
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('pos/settings', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $terminal_id ? $this->language->get('text_edit_terminal') : $this->language->get('text_add_terminal'),
            'href' => $this->url->link('pos/settings/terminal', 'user_token=' . $this->session->data['user_token'] . ($terminal_id ? '&terminal_id=' . $terminal_id : ''), true)
        );

        $data['cancel'] = $this->url->link('pos/settings', 'user_token=' . $this->session->data['user_token'] . '&tab=terminals', true);

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['name'])) {
            $data['error_name'] = $this->error['name'];
        } else {
            $data['error_name'] = '';
        }

        if (isset($this->error['branch_id'])) {
            $data['error_branch'] = $this->error['branch_id'];
        } else {
            $data['error_branch'] = '';
        }

        // Load branches for select
        $this->load->model('branch/branch');
        $data['branches'] = $this->model_branch_branch->getBranches();

        // Set form values
        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($terminal_info)) {
            $data['name'] = $terminal_info['name'];
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['branch_id'])) {
            $data['branch_id'] = $this->request->post['branch_id'];
        } elseif (!empty($terminal_info)) {
            $data['branch_id'] = $terminal_info['branch_id'];
        } else {
            $data['branch_id'] = 0;
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($terminal_info)) {
            $data['status'] = $terminal_info['status'];
        } else {
            $data['status'] = 1;
        }

        if (isset($this->request->post['printer_type'])) {
            $data['printer_type'] = $this->request->post['printer_type'];
        } elseif (!empty($terminal_info)) {
            $data['printer_type'] = $terminal_info['printer_type'];
        } else {
            $data['printer_type'] = '';
        }

        if (isset($this->request->post['printer_name'])) {
            $data['printer_name'] = $this->request->post['printer_name'];
        } elseif (!empty($terminal_info)) {
            $data['printer_name'] = $terminal_info['printer_name'];
        } else {
            $data['printer_name'] = '';
        }

        $data['printer_types'] = array(
            'thermal' => $this->language->get('text_thermal_printer'),
            'inkjet' => $this->language->get('text_inkjet_printer'),
            'laser' => $this->language->get('text_laser_printer'),
            'network' => $this->language->get('text_network_printer')
        );

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('pos/terminal_form', $data));
    }

    public function deleteTerminal() {
        $this->load->language('pos/settings');
        $json = array();

        if (isset($this->request->post['terminal_id'])) {
            if (!$this->user->hasPermission('modify', 'pos/settings')) {
                $json['error'] = $this->language->get('error_permission');
            } else {
                $this->load->model('pos/terminal');
                $this->load->model('pos/shift');
                
                $terminal_id = $this->request->post['terminal_id'];
                
                // Check if terminal is in use
                if ($this->model_pos_shift->isTerminalInUse($terminal_id)) {
                    $json['error'] = $this->language->get('error_terminal_in_use');
                } else {
                    $this->model_pos_terminal->deleteTerminal($terminal_id);
                    $json['success'] = $this->language->get('text_success_delete');
                }
            }
        } else {
            $json['error'] = $this->language->get('error_terminal');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'pos/settings')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->post['pos_max_discount_percentage']) && ($this->request->post['pos_max_discount_percentage'] < 0 || $this->request->post['pos_max_discount_percentage'] > 100)) {
            $this->error['warning'] = $this->language->get('error_discount_percentage');
        }

        return !$this->error;
    }

    protected function validateTerminal() {
        if (!$this->user->hasPermission('modify', 'pos/settings')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = $this->language->get('error_terminal_name');
        }

        if (empty($this->request->post['branch_id'])) {
            $this->error['branch_id'] = $this->language->get('error_branch');
        }

        return !$this->error;
    }
}