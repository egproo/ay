<?php
/**
 * نظام الموافقات الذكي للمشتريات
 * مستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
 */
class ControllerPurchaseSmartApprovalSystem extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('purchase/smart_approval_system');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('purchase/smart_approval_system');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/purchase/smart_approval.css');
        $this->document->addScript('view/javascript/purchase/smart_approval.js');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'smart_approval_system',
            'record_id' => 0,
            'description' => 'عرض نظام الموافقات الذكي',
            'module' => 'smart_approval_system'
        ]);

        $this->getList();
    }

    public function getPendingApprovals() {
        $this->load->model('purchase/smart_approval_system');

        $json = array();

        try {
            $filter_data = array(
                'user_id' => $this->user->getId(),
                'status' => 'pending',
                'start' => $this->request->get['start'] ?? 0,
                'limit' => $this->request->get['limit'] ?? 20
            );

            $approvals = $this->model_purchase_smart_approval_system->getPendingApprovals($filter_data);
            $total = $this->model_purchase_smart_approval_system->getTotalPendingApprovals($filter_data);
            
            $json['success'] = true;
            $json['approvals'] = $approvals;
            $json['total'] = $total;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function processApproval() {
        $this->load->model('purchase/smart_approval_system');
        $this->load->model('accounts/audit_trail');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            try {
                // التحقق من الصلاحيات
                if (!$this->user->hasPermission('modify', 'purchase/smart_approval_system')) {
                    throw new Exception('ليس لديك صلاحية لمعالجة الموافقات');
                }

                $approval_data = array(
                    'approval_id' => $this->request->post['approval_id'],
                    'action' => $this->request->post['action'], // approve, reject, delegate
                    'comments' => $this->request->post['comments'] ?? '',
                    'delegate_to' => $this->request->post['delegate_to'] ?? null,
                    'conditions' => $this->request->post['conditions'] ?? array()
                );

                $result = $this->model_purchase_smart_approval_system->processApproval($approval_data);
                
                if ($result['success']) {
                    $json['success'] = true;
                    $json['message'] = 'تم معالجة الموافقة بنجاح';
                    $json['details'] = $result['details'];
                    
                    // تسجيل في سجل المراجعة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'process_approval',
                        'table_name' => 'smart_approval_system',
                        'record_id' => $approval_data['approval_id'],
                        'description' => 'معالجة موافقة: ' . $approval_data['action'] . ' - رقم الموافقة: ' . $approval_data['approval_id'],
                        'module' => 'smart_approval_system'
                    ]);
                } else {
                    $json['error'] = 'فشل في معالجة الموافقة';
                    $json['details'] = $result['details'];
                }
                
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'طريقة طلب غير صحيحة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function bulkApproval() {
        $this->load->model('purchase/smart_approval_system');
        $this->load->model('accounts/audit_trail');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            try {
                // التحقق من الصلاحيات العليا
                if (!$this->user->hasPermission('modify', 'purchase/bulk_approval')) {
                    throw new Exception('ليس لديك صلاحية للموافقة المجمعة');
                }

                $bulk_data = array(
                    'approval_ids' => $this->request->post['approval_ids'],
                    'action' => $this->request->post['action'],
                    'comments' => $this->request->post['comments'] ?? '',
                    'criteria' => $this->request->post['criteria'] ?? array()
                );

                $result = $this->model_purchase_smart_approval_system->processBulkApproval($bulk_data);
                
                if ($result['success']) {
                    $json['success'] = true;
                    $json['message'] = 'تم معالجة الموافقات المجمعة بنجاح';
                    $json['details'] = $result['details'];
                    
                    // تسجيل في سجل المراجعة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'bulk_approval',
                        'table_name' => 'smart_approval_system',
                        'record_id' => 0,
                        'description' => 'موافقة مجمعة على ' . count($bulk_data['approval_ids']) . ' طلب',
                        'module' => 'smart_approval_system'
                    ]);
                } else {
                    $json['error'] = 'فشل في معالجة الموافقات المجمعة';
                    $json['details'] = $result['details'];
                }
                
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'طريقة طلب غير صحيحة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getApprovalWorkflow() {
        $this->load->model('purchase/smart_approval_system');

        $json = array();

        if (isset($this->request->get['document_type']) && isset($this->request->get['document_id'])) {
            try {
                $workflow = $this->model_purchase_smart_approval_system->getApprovalWorkflow(
                    $this->request->get['document_type'],
                    $this->request->get['document_id']
                );
                
                $json['success'] = true;
                $json['workflow'] = $workflow;
                
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معاملات مطلوبة مفقودة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getApprovalRules() {
        $this->load->model('purchase/smart_approval_system');

        $json = array();

        try {
            $rules = $this->model_purchase_smart_approval_system->getApprovalRules();
            
            $json['success'] = true;
            $json['rules'] = $rules;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function updateApprovalRules() {
        $this->load->model('purchase/smart_approval_system');
        $this->load->model('accounts/audit_trail');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            try {
                // التحقق من الصلاحيات العليا
                if (!$this->user->hasPermission('modify', 'purchase/approval_rules')) {
                    throw new Exception('ليس لديك صلاحية لتعديل قواعد الموافقة');
                }

                $rules = $this->request->post['rules'];
                
                $result = $this->model_purchase_smart_approval_system->updateApprovalRules($rules);
                
                if ($result) {
                    $json['success'] = true;
                    $json['message'] = 'تم تحديث قواعد الموافقة بنجاح';
                    
                    // تسجيل في سجل المراجعة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'update_approval_rules',
                        'table_name' => 'approval_rules',
                        'record_id' => 0,
                        'description' => 'تحديث قواعد الموافقة الذكية',
                        'module' => 'smart_approval_system'
                    ]);
                } else {
                    $json['error'] = 'فشل في تحديث قواعد الموافقة';
                }
                
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'طريقة طلب غير صحيحة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getApprovalAnalytics() {
        $this->load->model('purchase/smart_approval_system');

        $json = array();

        try {
            $filter_data = array(
                'date_from' => $this->request->get['date_from'] ?? date('Y-m-01'),
                'date_to' => $this->request->get['date_to'] ?? date('Y-m-d'),
                'department_id' => $this->request->get['department_id'] ?? null,
                'user_id' => $this->request->get['user_id'] ?? null
            );

            $analytics = $this->model_purchase_smart_approval_system->getApprovalAnalytics($filter_data);
            
            $json['success'] = true;
            $json['analytics'] = $analytics;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function escalateApproval() {
        $this->load->model('purchase/smart_approval_system');
        $this->load->model('accounts/audit_trail');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            try {
                $escalation_data = array(
                    'approval_id' => $this->request->post['approval_id'],
                    'escalate_to' => $this->request->post['escalate_to'],
                    'reason' => $this->request->post['reason'] ?? '',
                    'priority' => $this->request->post['priority'] ?? 'normal'
                );

                $result = $this->model_purchase_smart_approval_system->escalateApproval($escalation_data);
                
                if ($result['success']) {
                    $json['success'] = true;
                    $json['message'] = 'تم تصعيد الموافقة بنجاح';
                    
                    // تسجيل في سجل المراجعة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'escalate_approval',
                        'table_name' => 'smart_approval_system',
                        'record_id' => $escalation_data['approval_id'],
                        'description' => 'تصعيد موافقة رقم: ' . $escalation_data['approval_id'] . ' إلى: ' . $escalation_data['escalate_to'],
                        'module' => 'smart_approval_system'
                    ]);
                } else {
                    $json['error'] = 'فشل في تصعيد الموافقة';
                }
                
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'طريقة طلب غير صحيحة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function getList() {
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/smart_approval_system', 'user_token=' . $this->session->data['user_token'], true)
        );

        // URLs للـ AJAX
        $data['pending_approvals_url'] = $this->url->link('purchase/smart_approval_system/getPendingApprovals', 'user_token=' . $this->session->data['user_token'], true);
        $data['process_approval_url'] = $this->url->link('purchase/smart_approval_system/processApproval', 'user_token=' . $this->session->data['user_token'], true);
        $data['bulk_approval_url'] = $this->url->link('purchase/smart_approval_system/bulkApproval', 'user_token=' . $this->session->data['user_token'], true);
        $data['workflow_url'] = $this->url->link('purchase/smart_approval_system/getApprovalWorkflow', 'user_token=' . $this->session->data['user_token'], true);
        $data['rules_url'] = $this->url->link('purchase/smart_approval_system/getApprovalRules', 'user_token=' . $this->session->data['user_token'], true);
        $data['update_rules_url'] = $this->url->link('purchase/smart_approval_system/updateApprovalRules', 'user_token=' . $this->session->data['user_token'], true);
        $data['analytics_url'] = $this->url->link('purchase/smart_approval_system/getApprovalAnalytics', 'user_token=' . $this->session->data['user_token'], true);
        $data['escalate_url'] = $this->url->link('purchase/smart_approval_system/escalateApproval', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];

        // تحميل قوائم البيانات
        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();

        $this->load->model('setting/department');
        $data['departments'] = $this->model_setting_department->getDepartments();

        // الصلاحيات
        $data['can_process_approval'] = $this->user->hasPermission('modify', 'purchase/smart_approval_system');
        $data['can_bulk_approval'] = $this->user->hasPermission('modify', 'purchase/bulk_approval');
        $data['can_update_rules'] = $this->user->hasPermission('modify', 'purchase/approval_rules');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/smart_approval_system', $data));
    }
}
