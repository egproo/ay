<?php
class ControllerEtaCodes extends Controller {
    public function index() {
        $this->load->language('eta/codes');
        $this->document->setTitle($this->language->get('text_eta_codes'));
        $this->load->model('eta/codes');
    $this->model_eta_codes->ensureEgsCodes();


        $data['gpc_codes'] = $this->model_eta_codes->getGpcs();
        $data['products_json'] = json_encode($this->getProductsx());

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
  	$data['user_token'] =  $this->session->data['user_token'];

        $this->response->setOutput($this->load->view('eta/codes_list', $data));
    }
public function triggerUpdateCodeStatus() {
    $this->load->model('eta/codes');
    $response = $this->model_eta_codes->checkAndUpdateCodeStatus();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode([
        'success' => $response ? 'OK, Will update ETA Status.' : 'No updates were necessary or an error occurred.'
    ]));
}
public function getProducts() {
    $this->load->model('eta/codes');
    $json = [];

    // Pagination and filtering parameters
    $limit = 200;  // Number of products per page
    $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
    $filter_name = isset($this->request->get['filter_name']) ? $this->request->get['filter_name'] : '';
    $filter_product_id = isset($this->request->get['filter_product_id']) ? (int)$this->request->get['filter_product_id'] : null;
    $filter_egs = isset($this->request->get['filter_egs']) ? $this->request->get['filter_egs'] : '';
    $filter_gpc = isset($this->request->get['filter_gpc']) ? $this->request->get['filter_gpc'] : '';

    $filter_data = [
        'start' => ($page - 1) * $limit,
        'limit' => $limit,
        'product_name' => $filter_name,
        'product_id' => $filter_product_id,
        'egs_code' => $filter_egs,
        'gpc_code' => $filter_gpc
    ];

    $products = $this->model_eta_codes->getProducts($filter_data);
    $total = $this->model_eta_codes->getTotalProducts($filter_data);

    foreach ($products as $product) {
        $json['products'][] = [
            'product_id' => $product['product_id'],
            'name' => $product['name'],
            'egs_code' => $product['egs_code'],
            'gpc_code' => $product['gpc_code'],
            'eta_status' => $product['eta_status']
        ];
    }

    $json['pagination'] = [
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'num_pages' => ceil($total / $limit)
    ];

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}
public function getProductsx() {
    $this->load->model('eta/codes');
    $json = [];

    // Pagination and filtering parameters
    $limit = 100;  // Number of products per page
    $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
    $filter_name = isset($this->request->get['filter_name']) ? $this->request->get['filter_name'] : '';
    $filter_product_id = isset($this->request->get['filter_product_id']) ? (int)$this->request->get['filter_product_id'] : null;
    $filter_egs = isset($this->request->get['filter_egs']) ? $this->request->get['filter_egs'] : '';
    $filter_gpc = isset($this->request->get['filter_gpc']) ? $this->request->get['filter_gpc'] : '';

    $filter_data = [
        'start' => ($page - 1) * $limit,
        'limit' => $limit,
        'product_name' => $filter_name,
        'product_id' => $filter_product_id,
        'egs_code' => $filter_egs,
        'gpc_code' => $filter_gpc
    ];

    $products = $this->model_eta_codes->getProducts($filter_data);
    $total = $this->model_eta_codes->getTotalProducts($filter_data);

    foreach ($products as $product) {
        $json['products'][] = [
            'product_id' => $product['product_id'],
            'name' => $product['name'],
            'egs_code' => $product['egs_code'],
            'gpc_code' => $product['gpc_code'],
            'eta_status' => $product['eta_status']
        ];
    }

    $json['pagination'] = [
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'num_pages' => ceil($total / $limit)
    ];

    return $json;
    
}


    public function updateProduct() {
        $this->load->model('eta/codes');
        if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validateProductUpdate()) {
            $product_id = $this->request->post['product_id'];
            $gpc_code = $this->request->post['gpc_code'];

            $success = $this->model_eta_codes->updateProduct($product_id, $gpc_code);
            $json = ['success' => $success ? 'Product updated successfully' : 'Update failed'];
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        } else {
            $this->response->setOutput(json_encode(['error' => 'Invalid request']));
        }
    }

public function sendToETA() {
    $this->load->model('eta/codes');
    if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validateSendToETA()) {
        $product_id = $this->request->post['product_id'];
        $product_data = $this->model_eta_codes->getProduct($product_id);
        
        // Check that all required product data is present
        if (!$product_data || !isset($product_data['name'], $product_data['gpc_code'])) {
            $json = ['error' => 'Incomplete product data'];
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        $result = $this->model_eta_codes->createEgsCode($product_id, $product_data);
        
        // Update ETA status based on response
        if ($result) {
            $this->model_eta_codes->updateEtaStatus($product_id, 'pending');
        }
        
        $json = ['success' => $result ? $this->model_eta_codes->getCodeDetails($product_data['egs_code']) : 'Failed to send data'];
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    } else {
        $this->response->setOutput(json_encode(['error' => 'Invalid request']));
    }
}

    // Validate product update
    private function validateProductUpdate() {
        // Implement your validation logic here
        return true;  // Temporary always true
    }

    // Validate data before sending to ETA
    private function validateSendToETA()

 {
        // Implement your validation logic here
        return true;  // Temporary always true
    }
}