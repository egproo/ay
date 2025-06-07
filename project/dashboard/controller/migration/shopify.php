<?php
namespace Opencart\Admin\Controller\Migration;

class Shopify extends \Opencart\System\Engine\Controller {
    public function index() {
        $this->load->language('migration/migration');

        $this->document->setTitle($this->language->get('text_shopify_migration'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_migration'),
            'href' => $this->url->link('migration/shopify', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['heading_title'] = $this->language->get('text_shopify_migration');

        // Load common language strings
        $data['text_success'] = $this->language->get('text_success');
        $data['text_error'] = $this->language->get('text_error');
        $data['alert_backup'] = $this->language->get('alert_backup');
        $data['alert_required_fields'] = $this->language->get('alert_required_fields');

        // Load form fields language strings
        $data['entry_source'] = $this->language->get('entry_source');
        $data['entry_file'] = $this->language->get('entry_file');
        $data['entry_encoding'] = $this->language->get('entry_encoding');
        $data['entry_delimiter'] = $this->language->get('entry_delimiter');
        $data['entry_mapping'] = $this->language->get('entry_mapping');
        $data['entry_skip_rows'] = $this->language->get('entry_skip_rows');
        $data['entry_batch_size'] = $this->language->get('entry_batch_size');

        // Load button language strings
        $data['button_import'] = $this->language->get('button_import');
        $data['button_review'] = $this->language->get('button_review');

        // Load error language strings
        $data['error_permission'] = $this->language->get('error_permission');
        $data['error_file'] = $this->language->get('error_file');
        $data['error_encoding'] = $this->language->get('error_encoding');
        $data['error_mapping'] = $this->language->get('error_mapping');
        $data['error_required'] = $this->language->get('error_required');
        $data['error_invalid_source'] = $this->language->get('error_invalid_source');
        $data['error_connection'] = $this->language->get('error_connection');

        // Add user token for AJAX calls
        $data['user_token'] = $this->session->data['user_token'];

        // Add common template data
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // Load the template
        $this->response->setOutput($this->load->view('migration/shopify', $data));
    }

    public function import() {
        $this->load->language('migration/migration');

        $json = [];

        if (!$this->user->hasPermission('modify', 'migration/shopify')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('migration/migration');
            
            try {
                // Validate API credentials
                if (empty($this->request->post['api_key']) || empty($this->request->post['api_secret'])) {
                    $json['error'] = $this->language->get('error_credentials');
                } else {
                    // Process the import
                    $import_data = $this->model_migration_migration->processShopifyImport([
                        'api_key' => $this->request->post['api_key'],
                        'api_secret' => $this->request->post['api_secret'],
                        'store_url' => $this->request->post['store_url']
                    ]);
                    
                    if ($import_data['success']) {
                        // Store data in temporary tables
                        $migration_id = $this->model_migration_migration->createMigration([
                            'source' => 'shopify',
                            'store_url' => $this->request->post['store_url'],
                            'total_records' => $import_data['total_records'],
                            'status' => 'pending'
                        ]);
                        
                        $this->model_migration_migration->storeTemporaryData($migration_id, $import_data['data']);
                        
                        $json['success'] = sprintf($this->language->get('text_records_imported'), $import_data['total_records']);
                        $json['migration_id'] = $migration_id;
                    } else {
                        $json['error'] = $import_data['error'];
                    }
                }
            } catch (\Exception $e) {
                $json['error'] = $this->language->get('error_processing');
                $this->log->write('Shopify import error: ' . $e->getMessage());
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}