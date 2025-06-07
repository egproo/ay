<?php
/**
 * محرر سير العمل المرئي المتقدم (شبيه n8n)
 * 
 * يوفر واجهة مرئية متقدمة لإنشاء وتحرير سير العمل مع:
 * - محرر بالسحب والإفلات
 * - مكتبة عقد متنوعة
 * - معاينة مباشرة
 * - اختبار سير العمل
 * - قوالب جاهزة
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerWorkflowAdvancedVisualEditor extends Controller {
    
    private $error = [];
    
    /**
     * الصفحة الرئيسية للمحرر المرئي
     */
    public function index() {
        $this->load->language('workflow/advanced_visual_editor');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('workflow/visual_workflow_engine');
        
        if (isset($this->request->get['workflow_id'])) {
            $workflow_id = (int)$this->request->get['workflow_id'];
            $this->editWorkflow($workflow_id);
        } else {
            $this->createNewWorkflow();
        }
    }
    
    /**
     * إنشاء سير عمل جديد
     */
    private function createNewWorkflow() {
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('workflow/advanced_visual_editor', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        // إعداد البيانات الأساسية
        $data['workflow'] = [
            'workflow_id' => 0,
            'name' => '',
            'description' => '',
            'workflow_type' => 'manual',
            'trigger_type' => 'manual',
            'status' => 'draft',
            'nodes' => [],
            'connections' => []
        ];
        
        // مكتبة العقد المتاحة
        $data['node_library'] = $this->getNodeLibrary();
        
        // قوالب سير العمل
        $data['workflow_templates'] = $this->getWorkflowTemplates();
        
        // إعدادات المحرر
        $data['editor_config'] = $this->getEditorConfig();
        
        $data['save_url'] = $this->url->link('workflow/advanced_visual_editor/save', 'user_token=' . $this->session->data['user_token'], true);
        $data['test_url'] = $this->url->link('workflow/advanced_visual_editor/test', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel_url'] = $this->url->link('workflow/workflow_management', 'user_token=' . $this->session->data['user_token'], true);
        
        $data['user_token'] = $this->session->data['user_token'];
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('workflow/advanced_visual_editor', $data));
    }
    
    /**
     * تحرير سير عمل موجود
     */
    private function editWorkflow($workflow_id) {
        $workflow = $this->model_workflow_visual_workflow_engine->getWorkflow($workflow_id);
        
        if (!$workflow) {
            $this->session->data['error'] = $this->language->get('error_workflow_not_found');
            $this->response->redirect($this->url->link('workflow/workflow_management', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('workflow/advanced_visual_editor', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $workflow['name'],
            'href' => $this->url->link('workflow/advanced_visual_editor', 'user_token=' . $this->session->data['user_token'] . '&workflow_id=' . $workflow_id, true)
        ];
        
        // تحضير بيانات سير العمل
        $data['workflow'] = $workflow;
        $data['workflow']['nodes'] = $this->getWorkflowNodes($workflow_id);
        $data['workflow']['connections'] = $this->getWorkflowConnections($workflow_id);
        
        // مكتبة العقد المتاحة
        $data['node_library'] = $this->getNodeLibrary();
        
        // إعدادات المحرر
        $data['editor_config'] = $this->getEditorConfig();
        
        $data['save_url'] = $this->url->link('workflow/advanced_visual_editor/save', 'user_token=' . $this->session->data['user_token'], true);
        $data['test_url'] = $this->url->link('workflow/advanced_visual_editor/test', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel_url'] = $this->url->link('workflow/workflow_management', 'user_token=' . $this->session->data['user_token'], true);
        
        $data['user_token'] = $this->session->data['user_token'];
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('workflow/advanced_visual_editor', $data));
    }
    
    /**
     * حفظ سير العمل
     */
    public function save() {
        $this->load->language('workflow/advanced_visual_editor');
        
        $json = [];
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateWorkflow()) {
            $this->load->model('workflow/visual_workflow_engine');
            
            try {
                if (isset($this->request->post['workflow_id']) && $this->request->post['workflow_id']) {
                    // تحديث سير عمل موجود
                    $workflow_id = (int)$this->request->post['workflow_id'];
                    $this->model_workflow_visual_workflow_engine->updateWorkflow($workflow_id, $this->request->post);
                    $json['success'] = $this->language->get('text_workflow_updated');
                } else {
                    // إنشاء سير عمل جديد
                    $workflow_id = $this->model_workflow_visual_workflow_engine->createWorkflow($this->request->post);
                    $json['success'] = $this->language->get('text_workflow_created');
                }
                
                $json['workflow_id'] = $workflow_id;
                $json['redirect'] = $this->url->link('workflow/advanced_visual_editor', 'user_token=' . $this->session->data['user_token'] . '&workflow_id=' . $workflow_id, true);
                
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_validation');
            $json['errors'] = $this->error;
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * اختبار سير العمل
     */
    public function test() {
        $this->load->language('workflow/advanced_visual_editor');
        
        $json = [];
        
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model('workflow/visual_workflow_engine');
            
            try {
                $workflow_id = (int)$this->request->post['workflow_id'];
                $test_data = $this->request->post['test_data'] ?? [];
                
                // تنفيذ سير العمل في وضع الاختبار
                $execution_id = $this->model_workflow_visual_workflow_engine->executeWorkflow($workflow_id, $test_data);
                
                // الحصول على نتائج التنفيذ
                $execution_result = $this->getExecutionResult($execution_id);
                
                $json['success'] = $this->language->get('text_workflow_test_success');
                $json['execution_id'] = $execution_id;
                $json['result'] = $execution_result;
                
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * الحصول على مكتبة العقد
     */
    private function getNodeLibrary() {
        return [
            'triggers' => [
                [
                    'type' => 'manual',
                    'name' => 'تشغيل يدوي',
                    'icon' => 'fa-play',
                    'description' => 'بدء سير العمل يدوياً',
                    'color' => '#28a745',
                    'config_schema' => []
                ],
                [
                    'type' => 'schedule',
                    'name' => 'مجدول',
                    'icon' => 'fa-clock',
                    'description' => 'تشغيل سير العمل حسب جدول زمني',
                    'color' => '#17a2b8',
                    'config_schema' => [
                        'schedule_type' => ['type' => 'select', 'options' => ['daily', 'weekly', 'monthly']],
                        'schedule_time' => ['type' => 'time'],
                        'timezone' => ['type' => 'select', 'options' => ['UTC', 'Asia/Riyadh']]
                    ]
                ],
                [
                    'type' => 'webhook',
                    'name' => 'Webhook',
                    'icon' => 'fa-link',
                    'description' => 'تشغيل عند استلام طلب HTTP',
                    'color' => '#6f42c1',
                    'config_schema' => [
                        'method' => ['type' => 'select', 'options' => ['GET', 'POST', 'PUT', 'DELETE']],
                        'authentication' => ['type' => 'select', 'options' => ['none', 'api_key', 'basic']]
                    ]
                ],
                [
                    'type' => 'database_change',
                    'name' => 'تغيير في قاعدة البيانات',
                    'icon' => 'fa-database',
                    'description' => 'تشغيل عند تغيير البيانات',
                    'color' => '#fd7e14',
                    'config_schema' => [
                        'table_name' => ['type' => 'select'],
                        'operation' => ['type' => 'select', 'options' => ['insert', 'update', 'delete']],
                        'conditions' => ['type' => 'array']
                    ]
                ]
            ],
            'actions' => [
                [
                    'type' => 'create_order',
                    'name' => 'إنشاء طلب',
                    'icon' => 'fa-shopping-cart',
                    'description' => 'إنشاء طلب بيع جديد',
                    'color' => '#007bff',
                    'config_schema' => [
                        'customer_id' => ['type' => 'number', 'required' => true],
                        'products' => ['type' => 'array', 'required' => true],
                        'payment_method' => ['type' => 'select']
                    ]
                ],
                [
                    'type' => 'send_email',
                    'name' => 'إرسال بريد إلكتروني',
                    'icon' => 'fa-envelope',
                    'description' => 'إرسال رسالة بريد إلكتروني',
                    'color' => '#dc3545',
                    'config_schema' => [
                        'to' => ['type' => 'email', 'required' => true],
                        'subject' => ['type' => 'text', 'required' => true],
                        'body' => ['type' => 'textarea', 'required' => true],
                        'template' => ['type' => 'select']
                    ]
                ]
            ]
        ];
    }
    
    /**
     * الحصول على قوالب سير العمل
     */
    private function getWorkflowTemplates() {
        return [
            [
                'id' => 'order_approval',
                'name' => 'موافقة الطلبات',
                'description' => 'سير عمل لموافقة طلبات البيع',
                'category' => 'sales',
                'preview_image' => 'templates/order_approval.png'
            ]
        ];
    }
    
    /**
     * إعدادات المحرر
     */
    private function getEditorConfig() {
        return [
            'grid_size' => 20,
            'snap_to_grid' => true,
            'auto_save' => true,
            'auto_save_interval' => 30000,
            'zoom_levels' => [0.25, 0.5, 0.75, 1, 1.25, 1.5, 2],
            'default_zoom' => 1,
            'theme' => 'light',
            'minimap_enabled' => true
        ];
    }
    
    /**
     * التحقق من صحة سير العمل
     */
    private function validateWorkflow() {
        if (!$this->user->hasPermission('modify', 'workflow/advanced_visual_editor')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (empty($this->request->post['name'])) {
            $this->error['name'] = $this->language->get('error_name_required');
        }
        
        return !$this->error;
    }
    
    /**
     * الحصول على عقد سير العمل
     */
    private function getWorkflowNodes($workflow_id) {
        $query = $this->db->query("
            SELECT * FROM cod_workflow_node 
            WHERE workflow_id = '" . (int)$workflow_id . "'
            ORDER BY created_at
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على روابط سير العمل
     */
    private function getWorkflowConnections($workflow_id) {
        $query = $this->db->query("
            SELECT * FROM cod_workflow_connection 
            WHERE workflow_id = '" . (int)$workflow_id . "'
            ORDER BY created_at
        ");
        
        return $query->rows;
    }
}
