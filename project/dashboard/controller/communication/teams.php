<?php
/**
 * نظام إدارة الفرق والأقسام المتكامل مع Workflow
 * Advanced Teams & Departments Management Controller
 *
 * نظام إلكتروني متكامل يحل محل الأوراق والحركة الفيزيائية بين الأقسام
 * مع تكامل كامل مع workflow والإشعارات لأتمتة العمليات
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

class ControllerCommunicationTeams extends Controller {

    /**
     * @var array خطأ في النظام
     */
    private $error = array();

    /**
     * عرض صفحة إدارة الفرق الرئيسية
     */
    public function index() {
        $this->load->language('communication/teams');

        $this->document->setTitle($this->language->get('heading_title'));

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'communication/teams')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('communication/teams', 'user_token=' . $this->session->data['user_token'], true)
        );

        // تحميل الفرق والأقسام
        $this->load->model('communication/teams');

        // الأقسام الرئيسية
        $data['departments'] = $this->model_communication_teams->getDepartments();

        // الفرق النشطة
        $data['active_teams'] = $this->model_communication_teams->getActiveTeams();

        // طلبات الموافقة المعلقة للمستخدم الحالي
        $data['pending_approvals'] = $this->model_communication_teams->getPendingApprovals($this->user->getId());

        // المهام المعلقة
        $data['pending_tasks'] = $this->model_communication_teams->getPendingTasks($this->user->getId());

        // الوثائق المطلوب مراجعتها
        $data['pending_documents'] = $this->model_communication_teams->getPendingDocuments($this->user->getId());

        // إحصائيات سير العمل
        $data['workflow_stats'] = array(
            'total_pending_approvals' => $this->model_communication_teams->getTotalPendingApprovals(),
            'completed_today' => $this->model_communication_teams->getCompletedToday(),
            'overdue_tasks' => $this->model_communication_teams->getOverdueTasks(),
            'active_workflows' => $this->model_communication_teams->getActiveWorkflows()
        );

        // أنواع العمليات الإلكترونية (بدلاً من الأوراق)
        $data['digital_processes'] = array(
            'document_approval' => array(
                'name' => $this->language->get('text_document_approval'),
                'description' => $this->language->get('text_document_approval_desc'),
                'icon' => 'fa-file-text',
                'color' => 'primary',
                'href' => $this->url->link('communication/teams/document_approval', 'user_token=' . $this->session->data['user_token'], true),
                'pending_count' => $this->model_communication_teams->getPendingDocumentApprovals()
            ),
            'purchase_approval' => array(
                'name' => $this->language->get('text_purchase_approval'),
                'description' => $this->language->get('text_purchase_approval_desc'),
                'icon' => 'fa-shopping-cart',
                'color' => 'success',
                'href' => $this->url->link('communication/teams/purchase_approval', 'user_token=' . $this->session->data['user_token'], true),
                'pending_count' => $this->model_communication_teams->getPendingPurchaseApprovals()
            ),
            'expense_approval' => array(
                'name' => $this->language->get('text_expense_approval'),
                'description' => $this->language->get('text_expense_approval_desc'),
                'icon' => 'fa-money',
                'color' => 'warning',
                'href' => $this->url->link('communication/teams/expense_approval', 'user_token=' . $this->session->data['user_token'], true),
                'pending_count' => $this->model_communication_teams->getPendingExpenseApprovals()
            ),
            'leave_request' => array(
                'name' => $this->language->get('text_leave_request'),
                'description' => $this->language->get('text_leave_request_desc'),
                'icon' => 'fa-calendar',
                'color' => 'info',
                'href' => $this->url->link('communication/teams/leave_request', 'user_token=' . $this->session->data['user_token'], true),
                'pending_count' => $this->model_communication_teams->getPendingLeaveRequests()
            ),
            'inventory_approval' => array(
                'name' => $this->language->get('text_inventory_approval'),
                'description' => $this->language->get('text_inventory_approval_desc'),
                'icon' => 'fa-cubes',
                'color' => 'danger',
                'href' => $this->url->link('communication/teams/inventory_approval', 'user_token=' . $this->session->data['user_token'], true),
                'pending_count' => $this->model_communication_teams->getPendingInventoryApprovals()
            ),
            'catalog_approval' => array(
                'name' => $this->language->get('text_catalog_approval'),
                'description' => $this->language->get('text_catalog_approval_desc'),
                'icon' => 'fa-tags',
                'color' => 'secondary',
                'href' => $this->url->link('communication/teams/catalog_approval', 'user_token=' . $this->session->data['user_token'], true),
                'pending_count' => $this->model_communication_teams->getPendingCatalogApprovals()
            )
        );

        // العمليات السريعة (الأكثر استخداماً)
        $data['quick_actions'] = array(
            'new_approval_request' => array(
                'name' => $this->language->get('text_new_approval_request'),
                'href' => $this->url->link('communication/teams/new_request', 'user_token=' . $this->session->data['user_token'], true),
                'icon' => 'fa-plus-circle'
            ),
            'my_pending_approvals' => array(
                'name' => $this->language->get('text_my_pending_approvals'),
                'href' => $this->url->link('communication/teams/my_approvals', 'user_token=' . $this->session->data['user_token'], true),
                'icon' => 'fa-clock-o',
                'badge' => count($data['pending_approvals'])
            ),
            'team_dashboard' => array(
                'name' => $this->language->get('text_team_dashboard'),
                'href' => $this->url->link('communication/teams/dashboard', 'user_token=' . $this->session->data['user_token'], true),
                'icon' => 'fa-dashboard'
            ),
            'workflow_designer' => array(
                'name' => $this->language->get('text_workflow_designer'),
                'href' => $this->url->link('workflow/workflow/designer', 'user_token=' . $this->session->data['user_token'], true),
                'icon' => 'fa-sitemap'
            )
        );

        // الفرق المتخصصة للـ catalog/inventory
        $data['specialized_teams'] = array(
            'catalog_team' => array(
                'name' => $this->language->get('text_catalog_management_team'),
                'description' => $this->language->get('text_catalog_team_desc'),
                'members_count' => $this->model_communication_teams->getTeamMembersCount('catalog'),
                'active_workflows' => $this->model_communication_teams->getTeamActiveWorkflows('catalog'),
                'href' => $this->url->link('communication/teams/team_detail', 'team=catalog&user_token=' . $this->session->data['user_token'], true)
            ),
            'inventory_team' => array(
                'name' => $this->language->get('text_inventory_management_team'),
                'description' => $this->language->get('text_inventory_team_desc'),
                'members_count' => $this->model_communication_teams->getTeamMembersCount('inventory'),
                'active_workflows' => $this->model_communication_teams->getTeamActiveWorkflows('inventory'),
                'href' => $this->url->link('communication/teams/team_detail', 'team=inventory&user_token=' . $this->session->data['user_token'], true)
            ),
            'warehouse_team' => array(
                'name' => $this->language->get('text_warehouse_operations_team'),
                'description' => $this->language->get('text_warehouse_team_desc'),
                'members_count' => $this->model_communication_teams->getTeamMembersCount('warehouse'),
                'active_workflows' => $this->model_communication_teams->getTeamActiveWorkflows('warehouse'),
                'href' => $this->url->link('communication/teams/team_detail', 'team=warehouse&user_token=' . $this->session->data['user_token'], true)
            ),
            'purchase_team' => array(
                'name' => $this->language->get('text_purchase_team'),
                'description' => $this->language->get('text_purchase_team_desc'),
                'members_count' => $this->model_communication_teams->getTeamMembersCount('purchase'),
                'active_workflows' => $this->model_communication_teams->getTeamActiveWorkflows('purchase'),
                'href' => $this->url->link('communication/teams/team_detail', 'team=purchase&user_token=' . $this->session->data['user_token'], true)
            )
        );

        // الروابط
        $data['new_team'] = $this->url->link('communication/teams/add_team', 'user_token=' . $this->session->data['user_token'], true);
        $data['workflow_management'] = $this->url->link('workflow/workflow', 'user_token=' . $this->session->data['user_token'], true);
        $data['reports'] = $this->url->link('communication/teams/reports', 'user_token=' . $this->session->data['user_token'], true);

        // الرسائل
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        $data['current_user_id'] = $this->user->getId();
        $data['current_user_name'] = $this->user->getUserName();

        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('communication/teams', $data));
    }

    /**
     * طلب موافقة جديد (يحل محل الأوراق)
     */
    public function new_request() {
        $this->load->language('communication/teams');

        $this->document->setTitle($this->language->get('text_new_approval_request'));

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'communication/teams')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        // معالجة إرسال الطلب
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateRequest()) {
            $this->load->model('communication/teams');

            $request_id = $this->model_communication_teams->createApprovalRequest($this->request->post);

            // تشغيل workflow تلقائياً
            $this->triggerWorkflow($request_id, $this->request->post);

            // إرسال إشعارات للمعنيين
            $this->sendApprovalNotifications($request_id, $this->request->post);

            $this->session->data['success'] = $this->language->get('text_request_submitted');

            $this->response->redirect($this->url->link('communication/teams/view_request', 'request_id=' . $request_id . '&user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('communication/teams', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_new_approval_request'),
            'href' => $this->url->link('communication/teams/new_request', 'user_token=' . $this->session->data['user_token'], true)
        );

        // أنواع الطلبات المتاحة
        $data['request_types'] = array(
            'document_approval' => array(
                'name' => $this->language->get('text_document_approval'),
                'description' => $this->language->get('text_document_approval_desc'),
                'workflow_template' => 'document_approval_workflow',
                'required_fields' => array('document_title', 'document_type', 'description', 'attachments')
            ),
            'purchase_approval' => array(
                'name' => $this->language->get('text_purchase_approval'),
                'description' => $this->language->get('text_purchase_approval_desc'),
                'workflow_template' => 'purchase_approval_workflow',
                'required_fields' => array('supplier_name', 'total_amount', 'items', 'justification')
            ),
            'inventory_adjustment' => array(
                'name' => $this->language->get('text_inventory_adjustment'),
                'description' => $this->language->get('text_inventory_adjustment_desc'),
                'workflow_template' => 'inventory_approval_workflow',
                'required_fields' => array('product_id', 'adjustment_type', 'quantity', 'reason')
            ),
            'catalog_update' => array(
                'name' => $this->language->get('text_catalog_update'),
                'description' => $this->language->get('text_catalog_update_desc'),
                'workflow_template' => 'catalog_approval_workflow',
                'required_fields' => array('product_id', 'update_type', 'new_values', 'justification')
            ),
            'expense_claim' => array(
                'name' => $this->language->get('text_expense_claim'),
                'description' => $this->language->get('text_expense_claim_desc'),
                'workflow_template' => 'expense_approval_workflow',
                'required_fields' => array('expense_type', 'amount', 'date', 'receipts')
            ),
            'leave_request' => array(
                'name' => $this->language->get('text_leave_request'),
                'description' => $this->language->get('text_leave_request_desc'),
                'workflow_template' => 'leave_approval_workflow',
                'required_fields' => array('leave_type', 'start_date', 'end_date', 'reason')
            )
        );

        // تحميل البيانات المساعدة
        $this->load->model('user/user_group');
        $this->load->model('catalog/product');
        $this->load->model('purchase/supplier');

        $data['user_groups'] = $this->model_user_user_group->getUserGroups();
        $data['products'] = $this->model_catalog_product->getProducts(array('limit' => 100));
        $data['suppliers'] = $this->model_purchase_supplier->getSuppliers(array('limit' => 50));

        // الروابط
        $data['action'] = $this->url->link('communication/teams/new_request', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('communication/teams', 'user_token=' . $this->session->data['user_token'], true);

        // التوكن
        $data['user_token'] = $this->session->data['user_token'];

        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('communication/teams_new_request', $data));
    }

    /**
     * عرض طلبات الموافقة المعلقة للمستخدم
     */
    public function my_approvals() {
        $this->load->language('communication/teams');

        $this->document->setTitle($this->language->get('text_my_pending_approvals'));

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'communication/teams')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->load->model('communication/teams');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('communication/teams', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_my_pending_approvals'),
            'href' => $this->url->link('communication/teams/my_approvals', 'user_token=' . $this->session->data['user_token'], true)
        );

        // طلبات الموافقة المعلقة
        $data['pending_approvals'] = $this->model_communication_teams->getPendingApprovals($this->user->getId());

        // طلبات الموافقة المكتملة (آخر 10)
        $data['completed_approvals'] = $this->model_communication_teams->getCompletedApprovals($this->user->getId(), 10);

        // إحصائيات المستخدم
        $data['user_stats'] = array(
            'pending_count' => count($data['pending_approvals']),
            'completed_today' => $this->model_communication_teams->getUserCompletedToday($this->user->getId()),
            'total_completed' => $this->model_communication_teams->getUserTotalCompleted($this->user->getId()),
            'average_response_time' => $this->model_communication_teams->getUserAverageResponseTime($this->user->getId())
        );

        // الروابط
        $data['approve_all'] = $this->url->link('communication/teams/bulk_approve', 'user_token=' . $this->session->data['user_token'], true);
        $data['delegate'] = $this->url->link('communication/teams/delegate', 'user_token=' . $this->session->data['user_token'], true);

        // التوكن
        $data['user_token'] = $this->session->data['user_token'];

        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('communication/teams_my_approvals', $data));
    }

    /**
     * معالجة موافقة أو رفض طلب
     */
    public function process_approval() {
        $this->load->language('communication/teams');

        $json = array();

        if (!$this->user->hasPermission('modify', 'communication/teams')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateApproval()) {
                $this->load->model('communication/teams');

                $approval_data = array(
                    'request_id' => $this->request->post['request_id'],
                    'action' => $this->request->post['action'], // approved, rejected, delegated
                    'comment' => $this->request->post['comment'],
                    'user_id' => $this->user->getId()
                );

                if ($this->request->post['action'] == 'delegated') {
                    $approval_data['delegated_to'] = $this->request->post['delegated_to'];
                }

                $result = $this->model_communication_teams->processApproval($approval_data);

                if ($result['success']) {
                    // تحديث workflow
                    $this->updateWorkflowStatus($approval_data['request_id'], $approval_data['action']);

                    // إرسال إشعارات
                    $this->sendApprovalUpdateNotifications($approval_data);

                    // تسجيل في نظام اللوج
                    $this->logApprovalAction($approval_data);

                    $json['success'] = true;
                    $json['message'] = $this->language->get('text_approval_processed');
                } else {
                    $json['error'] = $result['error'];
                }
            } else {
                $json['error'] = $this->language->get('error_approval_validation');
                if ($this->error) {
                    $json['errors'] = $this->error;
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تشغيل workflow تلقائياً عند إنشاء طلب
     */
    private function triggerWorkflow($request_id, $request_data) {
        $this->load->model('workflow/workflow');

        // تحديد نوع الـ workflow بناءً على نوع الطلب
        $workflow_type = $request_data['request_type'];

        // البحث عن workflow مناسب
        $workflow = $this->model_workflow_workflow->getWorkflowByType($workflow_type);

        if ($workflow) {
            // إنشاء instance جديد من الـ workflow
            $workflow_instance_data = array(
                'workflow_id' => $workflow['workflow_id'],
                'reference_type' => 'approval_request',
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
     * إرسال إشعارات للمعنيين بالموافقة
     */
    private function sendApprovalNotifications($request_id, $request_data) {
        $this->load->model('notification/center');
        $this->load->model('communication/teams');

        // الحصول على المعتمدين للخطوة الأولى
        $approvers = $this->model_communication_teams->getRequestApprovers($request_id, 1);

        foreach ($approvers as $approver) {
            $notification_data = array(
                'type' => 'approval_request',
                'recipient_id' => $approver['user_id'],
                'title' => 'طلب موافقة جديد: ' . $request_data['title'],
                'message' => 'لديك طلب موافقة جديد يتطلب مراجعتك',
                'priority' => $request_data['priority'] ?? 'medium',
                'link' => 'communication/teams/view_request&request_id=' . $request_id,
                'reference_type' => 'approval_request',
                'reference_id' => $request_id
            );

            $this->model_notification_center->addNotification($notification_data);
        }

        // إرسال رسالة في نظام التواصل الداخلي
        $this->createApprovalConversation($request_id, $request_data, $approvers);
    }

    /**
     * إنشاء محادثة للطلب في نظام التواصل الداخلي
     */
    private function createApprovalConversation($request_id, $request_data, $approvers) {
        $this->load->model('communication/advanced_internal_communication');

        // إنشاء محادثة جديدة
        $conversation_data = array(
            'title' => 'طلب موافقة: ' . $request_data['title'],
            'description' => 'محادثة خاصة بطلب الموافقة رقم ' . $request_id,
            'conversation_type' => 'approval',
            'is_group' => 1,
            'associated_module' => 'approval_request',
            'reference_id' => $request_id,
            'participants' => array_merge(
                array($this->user->getId()), // مقدم الطلب
                array_column($approvers, 'user_id') // المعتمدين
            )
        );

        $conversation_id = $this->model_communication_advanced_internal_communication->createConversation($conversation_data);

        // إرسال رسالة ترحيب
        $welcome_message = array(
            'conversation_id' => $conversation_id,
            'content' => 'تم إنشاء طلب موافقة جديد. يرجى مراجعة التفاصيل والرد بالموافقة أو الرفض.',
            'message_type' => 'system',
            'is_system_message' => 1,
            'metadata' => array(
                'request_id' => $request_id,
                'request_type' => $request_data['request_type']
            )
        );

        $this->model_communication_advanced_internal_communication->sendMessage($welcome_message);
    }

    /**
     * تحديث حالة الـ workflow عند الموافقة/الرفض
     */
    private function updateWorkflowStatus($request_id, $action) {
        $this->load->model('workflow/workflow');
        $this->load->model('communication/teams');

        // الحصول على workflow instance
        $workflow_instance = $this->model_workflow_workflow->getWorkflowInstanceByReference('approval_request', $request_id);

        if ($workflow_instance) {
            if ($action == 'approved') {
                // الانتقال للخطوة التالية
                $this->model_workflow_workflow->moveToNextStep($workflow_instance['instance_id']);
            } elseif ($action == 'rejected') {
                // إنهاء الـ workflow
                $this->model_workflow_workflow->completeWorkflow($workflow_instance['instance_id'], 'rejected');
            } elseif ($action == 'delegated') {
                // تحديث المعتمد للخطوة الحالية
                $this->model_workflow_workflow->delegateCurrentStep($workflow_instance['instance_id'], $this->request->post['delegated_to']);
            }
        }
    }

    /**
     * إرسال إشعارات تحديث الموافقة
     */
    private function sendApprovalUpdateNotifications($approval_data) {
        $this->load->model('notification/center');
        $this->load->model('communication/teams');

        // الحصول على تفاصيل الطلب
        $request_info = $this->model_communication_teams->getApprovalRequest($approval_data['request_id']);

        if ($request_info) {
            // إشعار لمقدم الطلب
            $notification_data = array(
                'type' => 'approval_update',
                'recipient_id' => $request_info['requester_id'],
                'title' => 'تحديث على طلب الموافقة: ' . $request_info['title'],
                'message' => 'تم ' . ($approval_data['action'] == 'approved' ? 'الموافقة على' : 'رفض') . ' طلبك',
                'priority' => 'high',
                'link' => 'communication/teams/view_request&request_id=' . $approval_data['request_id']
            );

            $this->model_notification_center->addNotification($notification_data);

            // إذا كان هناك خطوة تالية، إشعار المعتمدين الجدد
            if ($approval_data['action'] == 'approved') {
                $next_approvers = $this->model_communication_teams->getNextStepApprovers($approval_data['request_id']);

                foreach ($next_approvers as $approver) {
                    $notification_data = array(
                        'type' => 'approval_request',
                        'recipient_id' => $approver['user_id'],
                        'title' => 'طلب موافقة في انتظارك: ' . $request_info['title'],
                        'message' => 'تم تمرير طلب موافقة إليك للمراجعة',
                        'priority' => $request_info['priority'],
                        'link' => 'communication/teams/view_request&request_id=' . $approval_data['request_id']
                    );

                    $this->model_notification_center->addNotification($notification_data);
                }
            }
        }
    }

    /**
     * تسجيل إجراء الموافقة في نظام اللوج
     */
    private function logApprovalAction($approval_data) {
        $this->load->model('logging/system_logs');

        $log_data = array(
            'user_id' => $this->user->getId(),
            'action_type' => 'approval_' . $approval_data['action'],
            'module' => 'communication/teams',
            'description' => 'تم ' . $approval_data['action'] . ' طلب الموافقة رقم ' . $approval_data['request_id'],
            'reference_type' => 'approval_request',
            'reference_id' => $approval_data['request_id'],
            'ip_address' => $this->request->server['REMOTE_ADDR'],
            'user_agent' => $this->request->server['HTTP_USER_AGENT']
        );

        $this->model_logging_system_logs->addLog($log_data);
    }

    /**
     * التحقق من صحة طلب الموافقة
     */
    protected function validateRequest() {
        if (!$this->user->hasPermission('modify', 'communication/teams')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['request_type'])) {
            $this->error['request_type'] = $this->language->get('error_request_type_required');
        }

        if (empty($this->request->post['title']) || utf8_strlen($this->request->post['title']) < 3) {
            $this->error['title'] = $this->language->get('error_title_required');
        }

        if (empty($this->request->post['description'])) {
            $this->error['description'] = $this->language->get('error_description_required');
        }

        // التحقق من الحقول المطلوبة حسب نوع الطلب
        $request_type = $this->request->post['request_type'];

        switch ($request_type) {
            case 'purchase_approval':
                if (empty($this->request->post['total_amount']) || !is_numeric($this->request->post['total_amount'])) {
                    $this->error['total_amount'] = $this->language->get('error_amount_required');
                }
                break;
            case 'inventory_adjustment':
                if (empty($this->request->post['product_id'])) {
                    $this->error['product_id'] = $this->language->get('error_product_required');
                }
                if (empty($this->request->post['quantity']) || !is_numeric($this->request->post['quantity'])) {
                    $this->error['quantity'] = $this->language->get('error_quantity_required');
                }
                break;
            case 'catalog_update':
                if (empty($this->request->post['product_id'])) {
                    $this->error['product_id'] = $this->language->get('error_product_required');
                }
                break;
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة إجراء الموافقة
     */
    protected function validateApproval() {
        if (!$this->user->hasPermission('modify', 'communication/teams')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['request_id']) || !is_numeric($this->request->post['request_id'])) {
            $this->error['request_id'] = $this->language->get('error_request_id_required');
        }

        if (empty($this->request->post['action']) || !in_array($this->request->post['action'], array('approved', 'rejected', 'delegated'))) {
            $this->error['action'] = $this->language->get('error_action_required');
        }

        if ($this->request->post['action'] == 'delegated' && empty($this->request->post['delegated_to'])) {
            $this->error['delegated_to'] = $this->language->get('error_delegate_user_required');
        }

        return !$this->error;
    }
}
