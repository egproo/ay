<?php
namespace Opencart\Admin\Controller\Migration;

class Review extends \Opencart\System\Engine\Controller {
    public function index() {
        $this->load->language('migration/migration');

        $this->document->setTitle($this->language->get('text_migration_review'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_migration'),
            'href' => $this->url->link('migration/review', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['heading_title'] = $this->language->get('text_migration_review');

        // Load common language strings
        $data['text_success'] = $this->language->get('text_success');
        $data['text_error'] = $this->language->get('text_error');
        $data['alert_review_needed'] = $this->language->get('alert_review_needed');

        // Load column language strings
        $data['column_source'] = $this->language->get('column_source');
        $data['column_destination'] = $this->language->get('column_destination');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_date'] = $this->language->get('column_date');
        $data['column_user'] = $this->language->get('column_user');
        $data['column_records'] = $this->language->get('column_records');
        $data['column_action'] = $this->language->get('column_action');

        // Load status language strings
        $data['status_pending'] = $this->language->get('status_pending');
        $data['status_approved'] = $this->language->get('status_approved');
        $data['status_rejected'] = $this->language->get('status_rejected');
        $data['status_completed'] = $this->language->get('status_completed');
        $data['status_failed'] = $this->language->get('status_failed');

        // Load button language strings
        $data['button_approve'] = $this->language->get('button_approve');
        $data['button_reject'] = $this->language->get('button_reject');
        $data['button_rollback'] = $this->language->get('button_rollback');

        // Load error language strings
        $data['error_permission'] = $this->language->get('error_permission');
        $data['error_validation'] = $this->language->get('error_validation');
        $data['error_rollback'] = $this->language->get('error_rollback');

        // Add user token for AJAX calls
        $data['user_token'] = $this->session->data['user_token'];

        // Add common template data
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // Load the template
        $this->response->setOutput($this->load->view('migration/review', $data));
    }

    public function approve() {
        $this->load->language('migration/migration');

        $json = [];

        if (!$this->user->hasPermission('modify', 'migration/review')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $migration_id = isset($this->request->post['migration_id']) ? (int)$this->request->post['migration_id'] : 0;
            
            if (!$migration_id) {
                $json['error'] = $this->language->get('error_migration_id');
            } else {
                $this->load->model('migration/migration');
                
                try {
                    // Get migration details
                    $migration_info = $this->model_migration_migration->getMigration($migration_id);
                    
                    if ($migration_info) {
                        // Validate imported data
                        $validation_result = $this->model_migration_migration->validateMigrationData($migration_id);
                        
                        if ($validation_result['valid']) {
                            // Process and store data
                            $this->model_migration_migration->processMigrationData($migration_id);
                            
                            // Update migration status
                            $this->model_migration_migration->updateMigrationStatus($migration_id, 'approved');
                            
                            $json['success'] = $this->language->get('text_migration_approved');
                        } else {
                            $json['error'] = $validation_result['errors'];
                        }
                    } else {
                        $json['error'] = $this->language->get('error_migration_not_found');
                    }
                } catch (\Exception $e) {
                    $json['error'] = $this->language->get('error_processing');
                    $this->log->write('Migration approval error: ' . $e->getMessage());
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function reject() {
        $this->load->language('migration/migration');

        $json = [];

        if (!$this->user->hasPermission('modify', 'migration/review')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $migration_id = isset($this->request->post['migration_id']) ? (int)$this->request->post['migration_id'] : 0;
            $reject_reason = isset($this->request->post['reject_reason']) ? $this->request->post['reject_reason'] : '';
            
            if (!$migration_id) {
                $json['error'] = $this->language->get('error_migration_id');
            } elseif (empty($reject_reason)) {
                $json['error'] = $this->language->get('error_reject_reason');
            } else {
                $this->load->model('migration/migration');
                
                try {
                    // Get migration details
                    $migration_info = $this->model_migration_migration->getMigration($migration_id);
                    
                    if ($migration_info) {
                        // Update migration status and add reject reason
                        $this->model_migration_migration->updateMigrationStatus($migration_id, 'rejected', $reject_reason);
                        
                        // Clean up temporary data
                        $this->model_migration_migration->cleanupTemporaryData($migration_id);
                        
                        $json['success'] = $this->language->get('text_migration_rejected');
                    } else {
                        $json['error'] = $this->language->get('error_migration_not_found');
                    }
                } catch (\Exception $e) {
                    $json['error'] = $this->language->get('error_processing');
                    $this->log->write('Migration rejection error: ' . $e->getMessage());
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function rollback() {
        $this->load->language('migration/migration');

        $json = [];

        if (!$this->user->hasPermission('modify', 'migration/review')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $migration_id = isset($this->request->post['migration_id']) ? (int)$this->request->post['migration_id'] : 0;
            
            if (!$migration_id) {
                $json['error'] = $this->language->get('error_migration_id');
            } else {
                $this->load->model('migration/migration');
                
                try {
                    // Get migration details
                    $migration_info = $this->model_migration_migration->getMigration($migration_id);
                    
                    if ($migration_info && $migration_info['status'] === 'approved') {
                        // Check if backup exists
                        if ($this->model_migration_migration->hasBackup($migration_id)) {
                            // Restore data from backup
                            $this->model_migration_migration->restoreFromBackup($migration_id);
                            
                            // Update migration status
                            $this->model_migration_migration->updateMigrationStatus($migration_id, 'rolled_back');
                            
                            $json['success'] = $this->language->get('text_migration_rolled_back');
                        } else {
                            $json['error'] = $this->language->get('error_no_backup');
                        }
                    } else {
                        $json['error'] = $this->language->get('error_invalid_migration_status');
                    }
                } catch (\Exception $e) {
                    $json['error'] = $this->language->get('error_rollback');
                    $this->log->write('Migration rollback error: ' . $e->getMessage());
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}