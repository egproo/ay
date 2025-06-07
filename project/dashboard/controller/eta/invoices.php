<?php
class ControllerEtaInvoices extends Controller {
    public function index() {
        $this->load->language('eta/invoices');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('eta/invoices');

        $filter_data = [
            'filter_status' => $this->request->get['filter_status'] ?? '',
            'filter_date_from' => $this->request->get['filter_date_from'] ?? '',
            'filter_date_to' => $this->request->get['filter_date_to'] ?? '',
            'filter_supplier' => $this->request->get['filter_supplier'] ?? ''
        ];

        $data['invoices'] = $this->model_eta_invoices->getInvoices($filter_data);
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $data['user_token'] = $this->session->data['user_token'];

        $this->response->setOutput($this->load->view('eta/invoice_list', $data));
    }

    public function view() {
        $invoice_id = $this->request->get['invoice_id'];
        $this->load->model('eta/invoices');
        $data['invoice'] = $this->model_eta_invoices->getInvoice($invoice_id);
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $data['user_token'] = $this->session->data['user_token'];

        $this->response->setOutput($this->load->view('eta/invoice_view', $data));
    }

    public function saveInvoice($invoice_data) {
        $this->load->model('eta/invoices');
        $this->model_eta_invoices->saveInvoice($invoice_data);
    }

    // Additional methods for handling API interactions with ETA
    private function getSubmissionData($uuid) {
        $token = $this->accessToken();
        $url = "https://api.preprod.invoicing.eta.gov.eg/api/v1.0/submissions/$uuid";
        return $this->sendGetRequest($url, $token);
    }

    private function getDocumentDetails($uuid) {
        $token = $this->accessToken();
        $url = "https://api.preprod.invoicing.eta.gov.eg/api/v1.0/documents/$uuid/details";
        return $this->sendGetRequest($url, $token);
    }

    private function getDocumentPrintout($uuid) {
        $token = $this->accessToken();
        $url = "https://api.preprod.invoicing.eta.gov.eg/api/v1.0/documents/$uuid/print";
        return $this->sendGetRequest($url, $token);
    }

    private function getRecentDocuments() {
        $token = $this->accessToken();
        $url = "https://api.preprod.invoicing.eta.gov.eg/api/v1.0/recentDocuments";
        return $this->sendGetRequest($url, $token);
    }

    private function sendGetRequest($url, $token) {
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }

    private function accessToken() {
        $client_id = $this->config->get('config_eta_client_id');
        $client_secret = $this->config->get('config_eta_secret_1');
        $url = 'https://id.preprod.eta.gov.eg/connect/token';

        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);

        if (curl_errno($curl)) {
            curl_close($curl);
            return false;
        }

        curl_close($curl);

        if ($info['http_code'] != 200) {
            return false;
        }

        $result = json_decode($response, true);
        if (isset($result['error'])) {
            return false;
        }

        return $result['access_token'] ?? false;
    }
}
