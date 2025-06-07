<?php
/**
 * نظام موافقة المستندات المتقدم
 * Advanced Document Approval Controller
 * 
 * نظام موافقة ذكي للمستندات مع تكامل مع workflow والإشعارات
 * مطور بمستوى عالمي لتفوق على Odoo
 * 
 * @package    AYM ERP
 * @author     AYM ERP Development Team
 * @copyright  2024 AYM ERP
 * @license    Proprietary
 * @version    1.0.0
 * @link       https://aym-erp.com
 * @since      2024-12-19
 */

class ControllerDocumentsApproval extends Controller {
    
    /**
     * @var array خطأ في النظام
     */
    private $error = array();
    
    /**
     * عرض صفحة موافقة المستندات الرئيسية
     */
    public function index() {
        $this->load->language('documents/approval');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'documents/approval')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('documents/approval', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // تحميل طلبات الموافقة
        $this->load->model('documents/approval');
        
        // طلبات الموافقة المعلقة للمستخدم الحالي
        $data['pending_approvals'] = $this->model_documents_approval->getPendingApprovals($this->user->getId());
        
        // طلبات الموافقة المرسلة من المستخدم
        $data['sent_requests'] = $this->model_documents_approval->getSentRequests($this->user->getId());
        
        // طلبات الموافقة المكتملة
        $data['completed_approvals'] = $this->model_documents_approval->getCompletedApprovals($this->user->getId(), 10);
        
        // إحصائيات الموافقة
        $data['approval_stats'] = array(
            'pending_count' => count($data['pending_approvals']),
            'completed_today' => $this->model_documents_approval->getCompletedToday($this->user->getId()),
            'average_approval_time' => $this->model_documents_approval->getAverageApprovalTime($this->user->getId()),
            'approval_rate' => $this->model_documents_approval->getApprovalRate($this->user->getId()),
            'overdue_approvals' => $this->model_documents_approval->getOverdueApprovals($this->user->getId()),
            'urgent_approvals' => $this->model_documents_approval->getUrgentApprovals($this->user->getId())
        );
        
        // أنواع الموافقة المتخصصة للـ catalog/inventory
        $data['approval_types'] = array(
            'catalog' => array(
                'name' => $this->language->get('text_catalog_approvals'),
                'description' => $this->language->get('text_catalog_approvals_desc'),
                'icon' => 'fa-tags',
                'color' => 'primary',
                'types' => array(
                    'new_product' => array(
                        'name' => $this->language->get('text_new_product_approval'),
                        'workflow' => 'new_product_approval_workflow',
                        'required_fields' => array('product_name', 'category', 'specifications', 'pricing'),
                        'approvers' => array('catalog_manager', 'pricing_manager'),
                        'sla_hours' => 24
                    ),
                    'price_change' => array(
                        'name' => $this->language->get('text_price_change_approval'),
                        'workflow' => 'price_change_approval_workflow',
                        'required_fields' => array('product_id', 'old_price', 'new_price', 'justification'),
                        'approvers' => array('pricing_manager', 'sales_manager'),
                        'sla_hours' => 12
                    ),
                    'product_discontinuation' => array(
                        'name' => $this->language->get('text_product_discontinuation_approval'),
                        'workflow' => 'discontinuation_approval_workflow',
                        'required_fields' => array('product_id', 'reason', 'alternative_products', 'timeline'),
                        'approvers' => array('catalog_manager', 'inventory_manager', 'sales_manager'),
                        'sla_hours' => 48
                    )
                )
            ),
            'inventory' => array(
                'name' => $this->language->get('text_inventory_approvals'),
                'description' => $this->language->get('text_inventory_approvals_desc'),
                'icon' => 'fa-cubes',
                'color' => 'warning',
                'types' => array(
                    'stock_adjustment' => array(
                        'name' => $this->language->get('text_stock_adjustment_approval'),
                        'workflow' => 'stock_adjustment_approval_workflow',
                        'required_fields' => array('product_id', 'adjustment_type', 'quantity', 'reason'),
                        'approvers' => array('warehouse_manager', 'inventory_manager'),
                        'sla_hours' => 6
                    ),
                    'inventory_write_off' => array(
                        'name' => $this->language->get('text_inventory_write_off_approval'),
                        'workflow' => 'write_off_approval_workflow',
                        'required_fields' => array('products', 'quantities', 'reason', 'supporting_documents'),
                        'approvers' => array('inventory_manager', 'finance_manager'),
                        'sla_hours' => 24
                    ),
                    'warehouse_transfer' => array(
                        'name' => $this->language->get('text_warehouse_transfer_approval'),
                        'workflow' => 'transfer_approval_workflow',
                        'required_fields' => array('from_warehouse', 'to_warehouse', 'products', 'transfer_reason'),
                        'approvers' => array('warehouse_manager', 'logistics_manager'),
                        'sla_hours' => 12
                    )
                )
            ),
            'purchase' => array(
                'name' => $this->language->get('text_purchase_approvals'),
                'description' => $this->language->get('text_purchase_approvals_desc'),
                'icon' => 'fa-shopping-cart',
                'color' => 'success',
                'types' => array(
                    'purchase_order' => array(
                        'name' => $this->language->get('text_purchase_order_approval'),
                        'workflow' => 'purchase_order_approval_workflow',
                        'required_fields' => array('supplier', 'items', 'total_amount', 'delivery_terms'),
                        'approvers' => array('purchase_manager', 'finance_manager'),
                        'sla_hours' => 24
                    ),
                    'supplier_contract' => array(
                        'name' => $this->language->get('text_supplier_contract_approval'),
                        'workflow' => 'contract_approval_workflow',
                        'required_fields' => array('supplier', 'contract_terms', 'duration', 'value'),
                        'approvers' => array('purchase_manager', 'legal_manager', 'finance_manager'),
                        'sla_hours' => 72
                    )
                )
            ),
            'financial' => array(
                'name' => $this->language->get('text_financial_approvals'),
                'description' => $this->language->get('text_financial_approvals_desc'),
                'icon' => 'fa-money',
                'color' => 'danger',
                'types' => array(
                    'expense_claim' => array(
                        'name' => $this->language->get('text_expense_claim_approval'),
                        'workflow' => 'expense_approval_workflow',
                        'required_fields' => array('expense_type', 'amount', 'receipts', 'justification'),
                        'approvers' => array('direct_manager', 'finance_manager'),
                        'sla_hours' => 48
                    ),
                    'budget_variance' => array(
                        'name' => $this->language->get('text_budget_variance_approval'),
                        'workflow' => 'budget_variance_approval_workflow',
                        'required_fields' => array('budget_item', 'variance_amount', 'explanation', 'impact_analysis'),
                        'approvers' => array('department_manager', 'finance_manager', 'cfo'),
                        'sla_hours' => 24
                    )
                )
            )
        );
        
        // مستويات الموافقة
        $data['approval_levels'] = array(
            'level_1' => array(
                'name' => $this->language->get('text_level_1_approval'),
                'description' => $this->language->get('text_level_1_desc'),
                'amount_limit' => 1000,
                'approvers' => array('supervisor', 'team_lead')
            ),
            'level_2' => array(
                'name' => $this->language->get('text_level_2_approval'),
                'description' => $this->language->get('text_level_2_desc'),
                'amount_limit' => 10000,
                'approvers' => array('manager', 'department_head')
            ),
            'level_3' => array(
                'name' => $this->language->get('text_level_3_approval'),
                'description' => $this->language->get('text_level_3_desc'),
                'amount_limit' => 50000,
                'approvers' => array('director', 'finance_manager')
            ),
            'level_4' => array(
                'name' => $this->language->get('text_level_4_approval'),
                'description' => $this->language->get('text_level_4_desc'),
                'amount_limit' => null,
                'approvers' => array('ceo', 'board_member')
            )
        );
        
        // حالات الموافقة
        $data['approval_statuses'] = array(
            'pending' => array(
                'name' => $this->language->get('text_status_pending'),
                'color' => 'warning',
                'icon' => 'fa-clock-o'
            ),
            'approved' => array(
                'name' => $this->language->get('text_status_approved'),
                'color' => 'success',
                'icon' => 'fa-check'
            ),
            'rejected' => array(
                'name' => $this->language->get('text_status_rejected'),
                'color' => 'danger',
                'icon' => 'fa-times'
            ),
            'delegated' => array(
                'name' => $this->language->get('text_status_delegated'),
                'color' => 'info',
                'icon' => 'fa-share'
            ),
            'expired' => array(
                'name' => $this->language->get('text_status_expired'),
                'color' => 'secondary',
                'icon' => 'fa-clock-o'
            )
        );
        
        // تحليل الأداء
        $data['performance_metrics'] = array(
            'approval_trends' => $this->model_documents_approval->getApprovalTrends(30),
            'bottleneck_analysis' => $this->model_documents_approval->getBottleneckAnalysis(),
            'sla_compliance' => $this->model_documents_approval->getSLACompliance(),
            'user_performance' => $this->model_documents_approval->getUserPerformance($this->user->getId())
        );
        
        // الروابط
        $data['new_request'] = $this->url->link('documents/approval/new_request', 'user_token=' . $this->session->data['user_token'], true);
        $data['bulk_approve'] = $this->url->link('documents/approval/bulk_approve', 'user_token=' . $this->session->data['user_token'], true);
        $data['delegate'] = $this->url->link('documents/approval/delegate', 'user_token=' . $this->session->data['user_token'], true);
        $data['reports'] = $this->url->link('documents/approval/reports', 'user_token=' . $this->session->data['user_token'], true);
        $data['settings'] = $this->url->link('documents/approval/settings', 'user_token=' . $this->session->data['user_token'], true);
        
        // الرسائل
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('documents/approval', $data));
    }
    
    /**
     * طلب موافقة جديد
     */
    public function new_request() {
        $this->load->language('documents/approval');
        
        $this->document->setTitle($this->language->get('text_new_approval_request'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'documents/approval')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // معالجة إرسال الطلب
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateRequest()) {
            $this->load->model('documents/approval');
            
            $request_id = $this->model_documents_approval->createApprovalRequest($this->request->post);
            
            // تشغيل workflow تلقائياً
            $this->triggerApprovalWorkflow($request_id, $this->request->post);
            
            // إرسال إشعارات للمعتمدين
            $this->sendApprovalNotifications($request_id, $this->request->post);
            
            // تسجيل في نظام اللوج
            $this->logApprovalAction('create_request', $request_id, $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_request_submitted');
            
            $this->response->redirect($this->url->link('documents/approval/view_request', 'request_id=' . $request_id . '&user_token=' . $this->session->data['user_token'], true));
        }
        
        // عرض النموذج
        $this->getRequestForm();
    }
    
    /**
     * معالجة موافقة أو رفض
     */
    public function process() {
        $this->load->language('documents/approval');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'documents/approval')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateProcess()) {
                $this->load->model('documents/approval');
                
                $process_data = array(
                    'request_id' => $this->request->post['request_id'],
                    'action' => $this->request->post['action'], // approved, rejected, delegated
                    'comment' => $this->request->post['comment'],
                    'user_id' => $this->user->getId(),
                    'conditions' => $this->request->post['conditions'] ?? null
                );
                
                if ($this->request->post['action'] == 'delegated') {
                    $process_data['delegated_to'] = $this->request->post['delegated_to'];
                }
                
                $result = $this->model_documents_approval->processApproval($process_data);
                
                if ($result['success']) {
                    // تحديث workflow
                    $this->updateApprovalWorkflow($process_data['request_id'], $process_data['action']);
                    
                    // إرسال إشعارات
                    $this->sendProcessNotifications($process_data);
                    
                    // تسجيل في نظام اللوج
                    $this->logApprovalAction('process_approval', $process_data['request_id'], $process_data);
                    
                    // تنفيذ إجراءات ما بعد الموافقة
                    if ($process_data['action'] == 'approved') {
                        $this->executePostApprovalActions($process_data['request_id']);
                    }
                    
                    $json['success'] = true;
                    $json['message'] = $this->language->get('text_approval_processed');
                } else {
                    $json['error'] = $result['error'];
                }
            } else {
                $json['error'] = $this->language->get('error_process_validation');
                if ($this->error) {
                    $json['errors'] = $this->error;
                }
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * موافقة جماعية
     */
    public function bulk_approve() {
        $this->load->language('documents/approval');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'documents/approval')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($this->request->post['request_ids'])) {
                $this->load->model('documents/approval');
                
                $approved_count = 0;
                $failed_count = 0;
                
                foreach ($this->request->post['request_ids'] as $request_id) {
                    $process_data = array(
                        'request_id' => $request_id,
                        'action' => 'approved',
                        'comment' => $this->request->post['bulk_comment'] ?? 'موافقة جماعية',
                        'user_id' => $this->user->getId()
                    );
                    
                    $result = $this->model_documents_approval->processApproval($process_data);
                    
                    if ($result['success']) {
                        $approved_count++;
                        
                        // تحديث workflow وإرسال إشعارات
                        $this->updateApprovalWorkflow($request_id, 'approved');
                        $this->sendProcessNotifications($process_data);
                        $this->executePostApprovalActions($request_id);
                    } else {
                        $failed_count++;
                    }
                }
                
                $json['success'] = true;
                $json['approved_count'] = $approved_count;
                $json['failed_count'] = $failed_count;
                $json['message'] = sprintf($this->language->get('text_bulk_approval_result'), $approved_count, $failed_count);
            } else {
                $json['error'] = $this->language->get('error_no_requests_selected');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * تشغيل workflow الموافقة
     */
    private function triggerApprovalWorkflow($request_id, $request_data) {
        $this->load->model('workflow/workflow');
        
        // تحديد نوع الـ workflow بناءً على نوع الطلب
        $workflow_type = $request_data['approval_type'];
        
        // البحث عن workflow مناسب
        $workflow = $this->model_workflow_workflow->getWorkflowByType($workflow_type);
        
        if ($workflow) {
            // إنشاء instance جديد من الـ workflow
            $workflow_instance_data = array(
                'workflow_id' => $workflow['workflow_id'],
                'reference_type' => 'document_approval',
                'reference_id' => $request_id,
                'requester_id' => $this->user->getId(),
                'title' => $request_data['title'],
                'description' => $request_data['description'],
                'priority' => $request_data['priority'] ?? 'normal',
                'status' => 'pending'
            );
            
            $this->model_workflow_workflow->createWorkflowInstance($workflow_instance_data);
        }
    }
    
    /**
     * إرسال إشعارات الموافقة
     */
    private function sendApprovalNotifications($request_id, $request_data) {
        $this->load->model('notification/center');
        $this->load->model('documents/approval');
        
        // الحصول على المعتمدين للخطوة الأولى
        $approvers = $this->model_documents_approval->getRequestApprovers($request_id, 1);
        
        foreach ($approvers as $approver) {
            $notification_data = array(
                'type' => 'document_approval_request',
                'recipient_id' => $approver['user_id'],
                'title' => 'طلب موافقة مستند: ' . $request_data['title'],
                'message' => 'لديك طلب موافقة مستند جديد يتطلب مراجعتك',
                'priority' => $request_data['priority'] ?? 'medium',
                'link' => 'documents/approval/view_request&request_id=' . $request_id,
                'reference_type' => 'document_approval',
                'reference_id' => $request_id
            );
            
            $this->model_notification_center->addNotification($notification_data);
        }
    }
    
    /**
     * تنفيذ إجراءات ما بعد الموافقة
     */
    private function executePostApprovalActions($request_id) {
        $this->load->model('documents/approval');
        
        $request_info = $this->model_documents_approval->getApprovalRequest($request_id);
        
        if ($request_info) {
            switch ($request_info['approval_type']) {
                case 'new_product':
                    $this->activateNewProduct($request_info['reference_data']);
                    break;
                case 'price_change':
                    $this->updateProductPrice($request_info['reference_data']);
                    break;
                case 'stock_adjustment':
                    $this->executeStockAdjustment($request_info['reference_data']);
                    break;
                case 'purchase_order':
                    $this->activatePurchaseOrder($request_info['reference_data']);
                    break;
            }
        }
    }
    
    /**
     * تسجيل إجراء الموافقة
     */
    private function logApprovalAction($action, $request_id, $data) {
        $this->load->model('logging/user_activity');
        
        $activity_data = array(
            'action_type' => 'approval_' . $action,
            'module' => 'documents/approval',
            'description' => 'تم ' . $action . ' طلب الموافقة رقم ' . $request_id,
            'reference_type' => 'document_approval',
            'reference_id' => $request_id
        );
        
        $this->model_logging_user_activity->addActivity($activity_data);
    }
    
    /**
     * دوال مساعدة لتنفيذ الإجراءات
     */
    private function activateNewProduct($data) {
        // منطق تفعيل المنتج الجديد
    }
    
    private function updateProductPrice($data) {
        // منطق تحديث سعر المنتج
    }
    
    private function executeStockAdjustment($data) {
        // منطق تنفيذ تسوية المخزون
    }
    
    private function activatePurchaseOrder($data) {
        // منطق تفعيل أمر الشراء
    }
    
    /**
     * التحقق من صحة الطلب
     */
    protected function validateRequest() {
        if (!$this->user->hasPermission('modify', 'documents/approval')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (empty($this->request->post['title'])) {
            $this->error['title'] = $this->language->get('error_title_required');
        }
        
        if (empty($this->request->post['approval_type'])) {
            $this->error['approval_type'] = $this->language->get('error_approval_type_required');
        }
        
        return !$this->error;
    }
    
    /**
     * التحقق من صحة المعالجة
     */
    protected function validateProcess() {
        if (!$this->user->hasPermission('modify', 'documents/approval')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (empty($this->request->post['request_id'])) {
            $this->error['request_id'] = $this->language->get('error_request_id_required');
        }
        
        if (empty($this->request->post['action'])) {
            $this->error['action'] = $this->language->get('error_action_required');
        }
        
        return !$this->error;
    }
    
    /**
     * نموذج طلب الموافقة
     */
    protected function getRequestForm() {
        // كود النموذج
    }
}
