<?php
class ControllerExtensionDashboardCodaym extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/dashboard/codaym');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/dashboard/codaym', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['user_token'] = $this->session->data['user_token'];

        // Load model and get data
        $this->load->model('extension/dashboard/codaym');
        $data['latest_orders'] = $this->model_extension_dashboard_codaym->getLatestOrders();
        $data['missing_orders'] = $this->model_extension_dashboard_codaym->getMissingOrders();
        $data['abandoned_carts'] = $this->model_extension_dashboard_codaym->getAbandonedCarts();

        // Render output
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/dashboard/codaym', $data));
    }
    
    public function update() {
        $this->load->language('extension/dashboard/codaym');
        $json = array();
    
        $this->load->model('extension/dashboard/codaym');
    
        // Get the updated data
        $json['latest_orders'] = $this->model_extension_dashboard_codaym->getLatestOrders();
        $json['missing_orders'] = $this->model_extension_dashboard_codaym->getMissingOrders();
        $json['abandoned_carts'] = $this->model_extension_dashboard_codaym->getAbandonedCarts();
    
        // Adding additional details to orders and carts for JSON response
        foreach ($json['latest_orders'] as $key => $order) {
            $json['latest_orders'][$key]['customer_name'] = $order['firstname'] . ' ' . $order['lastname'];
            $json['latest_orders'][$key]['date_added'] = date($this->language->get('date_format_short'), strtotime($order['date_added']));
        }
    
        foreach ($json['missing_orders'] as $key => $order) {
            $json['missing_orders'][$key]['customer_name'] = $order['firstname'] . ' ' . $order['lastname'];
            $json['missing_orders'][$key]['date_added'] = date($this->language->get('date_format_short'), strtotime($order['date_added']));
        }
    
        foreach ($json['abandoned_carts'] as $key => $cart) {
            // Assuming that $cart includes product name and customer details
            $json['abandoned_carts'][$key]['details'] = $cart['product_name'] . ' x ' . $cart['quantity'];
            $json['abandoned_carts'][$key]['date_added'] = date($this->language->get('date_format_short'), strtotime($cart['date_added']));
        }
    
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }


    public function install() {
        // Code to run when the extension is installed
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('dashboard_codaym', ['dashboard_codaym_status' => 1]);
    }

    public function uninstall() {
        // Code to run when the extension is uninstalled
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('dashboard_codaym');
    }
}