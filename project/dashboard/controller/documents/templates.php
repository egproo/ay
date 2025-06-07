<?php
/**
 * نظام قوالب المستندات المتقدم
 * Advanced Document Templates Controller
 * 
 * نظام قوالب ذكي للمستندات مع تكامل مع catalog/inventory وworkflow
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

class ControllerDocumentsTemplates extends Controller {
    
    /**
     * @var array خطأ في النظام
     */
    private $error = array();
    
    /**
     * عرض صفحة قوالب المستندات الرئيسية
     */
    public function index() {
        $this->load->language('documents/templates');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'documents/templates')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('documents/templates', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // تحميل القوالب
        $this->load->model('documents/templates');
        
        $filter_data = array(
            'start' => 0,
            'limit' => 20
        );
        
        $data['templates'] = $this->model_documents_templates->getTemplates($filter_data);
        $data['total'] = $this->model_documents_templates->getTotalTemplates($filter_data);
        
        // إحصائيات القوالب
        $data['template_stats'] = array(
            'total_templates' => $this->model_documents_templates->getTotalTemplates(),
            'active_templates' => $this->model_documents_templates->getActiveTemplates(),
            'most_used_template' => $this->model_documents_templates->getMostUsedTemplate(),
            'templates_created_this_month' => $this->model_documents_templates->getTemplatesCreatedThisMonth(),
            'total_documents_generated' => $this->model_documents_templates->getTotalDocumentsGenerated(),
            'average_generation_time' => $this->model_documents_templates->getAverageGenerationTime()
        );
        
        // قوالب متخصصة للـ catalog/inventory
        $data['specialized_templates'] = array(
            'catalog' => array(
                'name' => $this->language->get('text_catalog_templates'),
                'description' => $this->language->get('text_catalog_templates_desc'),
                'icon' => 'fa-tags',
                'color' => 'primary',
                'templates' => array(
                    'product_specification' => array(
                        'name' => $this->language->get('text_product_specification_template'),
                        'description' => $this->language->get('text_product_specification_desc'),
                        'variables' => array('{product_name}', '{product_id}', '{category}', '{specifications}', '{images}'),
                        'usage_count' => $this->model_documents_templates->getTemplateUsage('product_specification')
                    ),
                    'price_list' => array(
                        'name' => $this->language->get('text_price_list_template'),
                        'description' => $this->language->get('text_price_list_desc'),
                        'variables' => array('{category_name}', '{products}', '{prices}', '{effective_date}', '{currency}'),
                        'usage_count' => $this->model_documents_templates->getTemplateUsage('price_list')
                    ),
                    'catalog_report' => array(
                        'name' => $this->language->get('text_catalog_report_template'),
                        'description' => $this->language->get('text_catalog_report_desc'),
                        'variables' => array('{report_period}', '{total_products}', '{new_products}', '{categories}'),
                        'usage_count' => $this->model_documents_templates->getTemplateUsage('catalog_report')
                    )
                )
            ),
            'inventory' => array(
                'name' => $this->language->get('text_inventory_templates'),
                'description' => $this->language->get('text_inventory_templates_desc'),
                'icon' => 'fa-cubes',
                'color' => 'warning',
                'templates' => array(
                    'stock_report' => array(
                        'name' => $this->language->get('text_stock_report_template'),
                        'description' => $this->language->get('text_stock_report_desc'),
                        'variables' => array('{warehouse_name}', '{report_date}', '{stock_items}', '{total_value}'),
                        'usage_count' => $this->model_documents_templates->getTemplateUsage('stock_report')
                    ),
                    'movement_document' => array(
                        'name' => $this->language->get('text_movement_document_template'),
                        'description' => $this->language->get('text_movement_document_desc'),
                        'variables' => array('{movement_type}', '{products}', '{quantities}', '{reference}', '{date}'),
                        'usage_count' => $this->model_documents_templates->getTemplateUsage('movement_document')
                    ),
                    'adjustment_form' => array(
                        'name' => $this->language->get('text_adjustment_form_template'),
                        'description' => $this->language->get('text_adjustment_form_desc'),
                        'variables' => array('{adjustment_reason}', '{products}', '{old_quantities}', '{new_quantities}'),
                        'usage_count' => $this->model_documents_templates->getTemplateUsage('adjustment_form')
                    )
                )
            ),
            'purchase' => array(
                'name' => $this->language->get('text_purchase_templates'),
                'description' => $this->language->get('text_purchase_templates_desc'),
                'icon' => 'fa-shopping-cart',
                'color' => 'success',
                'templates' => array(
                    'purchase_order' => array(
                        'name' => $this->language->get('text_purchase_order_template'),
                        'description' => $this->language->get('text_purchase_order_desc'),
                        'variables' => array('{po_number}', '{supplier_name}', '{items}', '{total_amount}', '{delivery_date}'),
                        'usage_count' => $this->model_documents_templates->getTemplateUsage('purchase_order')
                    ),
                    'goods_receipt' => array(
                        'name' => $this->language->get('text_goods_receipt_template'),
                        'description' => $this->language->get('text_goods_receipt_desc'),
                        'variables' => array('{receipt_number}', '{supplier}', '{received_items}', '{receipt_date}'),
                        'usage_count' => $this->model_documents_templates->getTemplateUsage('goods_receipt')
                    ),
                    'supplier_evaluation' => array(
                        'name' => $this->language->get('text_supplier_evaluation_template'),
                        'description' => $this->language->get('text_supplier_evaluation_desc'),
                        'variables' => array('{supplier_name}', '{evaluation_period}', '{performance_metrics}', '{rating}'),
                        'usage_count' => $this->model_documents_templates->getTemplateUsage('supplier_evaluation')
                    )
                )
            ),
            'workflow' => array(
                'name' => $this->language->get('text_workflow_templates'),
                'description' => $this->language->get('text_workflow_templates_desc'),
                'icon' => 'fa-sitemap',
                'color' => 'info',
                'templates' => array(
                    'approval_request' => array(
                        'name' => $this->language->get('text_approval_request_template'),
                        'description' => $this->language->get('text_approval_request_desc'),
                        'variables' => array('{request_type}', '{requester_name}', '{description}', '{approval_steps}'),
                        'usage_count' => $this->model_documents_templates->getTemplateUsage('approval_request')
                    ),
                    'process_documentation' => array(
                        'name' => $this->language->get('text_process_documentation_template'),
                        'description' => $this->language->get('text_process_documentation_desc'),
                        'variables' => array('{process_name}', '{steps}', '{responsible_parties}', '{requirements}'),
                        'usage_count' => $this->model_documents_templates->getTemplateUsage('process_documentation')
                    ),
                    'workflow_report' => array(
                        'name' => $this->language->get('text_workflow_report_template'),
                        'description' => $this->language->get('text_workflow_report_desc'),
                        'variables' => array('{report_period}', '{completed_workflows}', '{pending_workflows}', '{efficiency_metrics}'),
                        'usage_count' => $this->model_documents_templates->getTemplateUsage('workflow_report')
                    )
                )
            )
        );
        
        // أنواع القوالب
        $data['template_types'] = array(
            'document' => array(
                'name' => $this->language->get('text_type_document'),
                'description' => $this->language->get('text_type_document_desc'),
                'icon' => 'fa-file-text',
                'formats' => array('docx', 'pdf', 'html')
            ),
            'report' => array(
                'name' => $this->language->get('text_type_report'),
                'description' => $this->language->get('text_type_report_desc'),
                'icon' => 'fa-bar-chart',
                'formats' => array('xlsx', 'pdf', 'html')
            ),
            'form' => array(
                'name' => $this->language->get('text_type_form'),
                'description' => $this->language->get('text_type_form_desc'),
                'icon' => 'fa-wpforms',
                'formats' => array('html', 'pdf')
            ),
            'certificate' => array(
                'name' => $this->language->get('text_type_certificate'),
                'description' => $this->language->get('text_type_certificate_desc'),
                'icon' => 'fa-certificate',
                'formats' => array('pdf', 'docx')
            )
        );
        
        // محرك القوالب المتقدم
        $data['template_engine_features'] = array(
            'variable_substitution' => true,
            'conditional_logic' => true,
            'loops_and_iterations' => true,
            'mathematical_operations' => true,
            'date_formatting' => true,
            'image_insertion' => true,
            'table_generation' => true,
            'chart_integration' => true,
            'barcode_generation' => true,
            'digital_signatures' => true
        );
        
        // القوالب الأكثر استخداماً
        $data['popular_templates'] = $this->model_documents_templates->getPopularTemplates(5);
        
        // القوالب الحديثة
        $data['recent_templates'] = $this->model_documents_templates->getRecentTemplates(5);
        
        // الروابط
        $data['add'] = $this->url->link('documents/templates/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['import'] = $this->url->link('documents/templates/import', 'user_token=' . $this->session->data['user_token'], true);
        $data['export'] = $this->url->link('documents/templates/export', 'user_token=' . $this->session->data['user_token'], true);
        $data['template_builder'] = $this->url->link('documents/templates/builder', 'user_token=' . $this->session->data['user_token'], true);
        $data['analytics'] = $this->url->link('documents/templates/analytics', 'user_token=' . $this->session->data['user_token'], true);
        
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
        
        $this->response->setOutput($this->load->view('documents/templates', $data));
    }
    
    /**
     * إضافة قالب جديد
     */
    public function add() {
        $this->load->language('documents/templates');
        
        $this->document->setTitle($this->language->get('text_add_template'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'documents/templates')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // معالجة حفظ القالب
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('documents/templates');
            
            $template_id = $this->model_documents_templates->addTemplate($this->request->post);
            
            // تسجيل في نظام اللوج
            $this->logTemplateAction('create', $template_id, $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $this->response->redirect($this->url->link('documents/templates/edit', 'template_id=' . $template_id . '&user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->getForm();
    }
    
    /**
     * تعديل قالب موجود
     */
    public function edit() {
        $this->load->language('documents/templates');
        
        $this->document->setTitle($this->language->get('text_edit_template'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'documents/templates')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // معالجة حفظ التعديلات
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('documents/templates');
            
            $this->model_documents_templates->editTemplate($this->request->get['template_id'], $this->request->post);
            
            // تسجيل في نظام اللوج
            $this->logTemplateAction('update', $this->request->get['template_id'], $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $this->response->redirect($this->url->link('documents/templates', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->getForm();
    }
    
    /**
     * إنشاء مستند من قالب
     */
    public function generate() {
        $this->load->language('documents/templates');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'documents/templates')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateGeneration()) {
                $this->load->model('documents/templates');
                
                $generation_result = $this->model_documents_templates->generateDocument(
                    $this->request->post['template_id'],
                    $this->request->post['variables'],
                    $this->request->post['format'] ?? 'pdf'
                );
                
                if ($generation_result['success']) {
                    // تسجيل الإنشاء
                    $this->logTemplateAction('generate', $this->request->post['template_id'], $this->request->post);
                    
                    // إرسال إشعار إذا كان مطلوباً
                    if (isset($this->request->post['send_notification']) && $this->request->post['send_notification']) {
                        $this->sendGenerationNotification($generation_result['document_id'], $this->request->post);
                    }
                    
                    $json['success'] = true;
                    $json['document_id'] = $generation_result['document_id'];
                    $json['download_url'] = $this->url->link('documents/archive/download', 'document_id=' . $generation_result['document_id'] . '&user_token=' . $this->session->data['user_token'], true);
                    $json['message'] = $this->language->get('text_generation_success');
                } else {
                    $json['error'] = $generation_result['error'];
                }
            } else {
                $json['error'] = $this->language->get('error_generation_validation');
                if ($this->error) {
                    $json['errors'] = $this->error;
                }
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * منشئ القوالب المرئي
     */
    public function builder() {
        $this->load->language('documents/templates');
        
        $this->document->setTitle($this->language->get('text_template_builder'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'documents/templates')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('documents/templates', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_template_builder'),
            'href' => $this->url->link('documents/templates/builder', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // عناصر منشئ القوالب
        $data['builder_elements'] = array(
            'text' => array(
                'name' => $this->language->get('text_element_text'),
                'icon' => 'fa-font',
                'properties' => array('content', 'font_size', 'font_family', 'color', 'alignment')
            ),
            'variable' => array(
                'name' => $this->language->get('text_element_variable'),
                'icon' => 'fa-code',
                'properties' => array('variable_name', 'default_value', 'format')
            ),
            'table' => array(
                'name' => $this->language->get('text_element_table'),
                'icon' => 'fa-table',
                'properties' => array('columns', 'rows', 'data_source', 'styling')
            ),
            'image' => array(
                'name' => $this->language->get('text_element_image'),
                'icon' => 'fa-image',
                'properties' => array('source', 'width', 'height', 'alignment')
            ),
            'chart' => array(
                'name' => $this->language->get('text_element_chart'),
                'icon' => 'fa-bar-chart',
                'properties' => array('chart_type', 'data_source', 'styling')
            ),
            'barcode' => array(
                'name' => $this->language->get('text_element_barcode'),
                'icon' => 'fa-barcode',
                'properties' => array('barcode_type', 'data', 'size')
            )
        );
        
        // مصادر البيانات المتاحة
        $data['data_sources'] = array(
            'catalog' => array(
                'products' => $this->language->get('text_data_products'),
                'categories' => $this->language->get('text_data_categories'),
                'suppliers' => $this->language->get('text_data_suppliers')
            ),
            'inventory' => array(
                'stock_levels' => $this->language->get('text_data_stock_levels'),
                'movements' => $this->language->get('text_data_movements'),
                'warehouses' => $this->language->get('text_data_warehouses')
            ),
            'sales' => array(
                'orders' => $this->language->get('text_data_orders'),
                'customers' => $this->language->get('text_data_customers'),
                'invoices' => $this->language->get('text_data_invoices')
            )
        );
        
        // الروابط
        $data['save_template'] = $this->url->link('documents/templates/save_from_builder', 'user_token=' . $this->session->data['user_token'], true);
        $data['preview'] = $this->url->link('documents/templates/preview', 'user_token=' . $this->session->data['user_token'], true);
        $data['back'] = $this->url->link('documents/templates', 'user_token=' . $this->session->data['user_token'], true);
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('documents/templates_builder', $data));
    }
    
    /**
     * نموذج إضافة/تعديل القالب
     */
    protected function getForm() {
        // تحميل البيانات للنموذج
        // ... (كود النموذج)
    }
    
    /**
     * تسجيل إجراء القالب
     */
    private function logTemplateAction($action, $template_id, $data) {
        $this->load->model('logging/user_activity');
        
        $activity_data = array(
            'action_type' => 'template_' . $action,
            'module' => 'documents/templates',
            'description' => 'تم ' . $action . ' القالب رقم ' . $template_id,
            'reference_type' => 'template',
            'reference_id' => $template_id
        );
        
        $this->model_logging_user_activity->addActivity($activity_data);
    }
    
    /**
     * إرسال إشعار الإنشاء
     */
    private function sendGenerationNotification($document_id, $data) {
        $this->load->model('notification/center');
        
        if (isset($data['notification_recipients'])) {
            foreach ($data['notification_recipients'] as $user_id) {
                $notification_data = array(
                    'type' => 'document_generated',
                    'recipient_id' => $user_id,
                    'title' => 'مستند جديد تم إنشاؤه',
                    'message' => 'تم إنشاء مستند جديد من القالب',
                    'priority' => 'medium',
                    'link' => 'documents/archive/view&document_id=' . $document_id
                );
                
                $this->model_notification_center->addNotification($notification_data);
            }
        }
    }
    
    /**
     * التحقق من صحة النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'documents/templates')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (empty($this->request->post['name'])) {
            $this->error['name'] = $this->language->get('error_name_required');
        }
        
        if (empty($this->request->post['content'])) {
            $this->error['content'] = $this->language->get('error_content_required');
        }
        
        return !$this->error;
    }
    
    /**
     * التحقق من صحة الإنشاء
     */
    protected function validateGeneration() {
        if (!$this->user->hasPermission('modify', 'documents/templates')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (empty($this->request->post['template_id'])) {
            $this->error['template_id'] = $this->language->get('error_template_required');
        }
        
        return !$this->error;
    }
}
