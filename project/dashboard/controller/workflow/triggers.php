<?php
/**
 * نظام المحفزات المتقدمة - Workflow Triggers
 * Advanced Workflow Triggers Controller (n8n-like)
 *
 * نظام محفزات ذكي لتشغيل العمليات التلقائية مع تكامل AI والأطراف الخارجية
 * مطور بمستوى عالمي لتفوق على Odoo وn8n
 *
 * @package    AYM ERP
 * @author     AYM ERP Development Team
 * @copyright  2024 AYM ERP
 * @license    Proprietary
 * @version    1.0.0
 * @link       https://aym-erp.com
 * @since      2024-12-19
 */

class ControllerWorkflowTriggers extends Controller {

    /**
     * @var array خطأ في النظام
     */
    private $error = array();

    /**
     * عرض صفحة المحفزات الرئيسية
     */
    public function index() {
        $this->load->language('workflow/triggers');

        $this->document->setTitle($this->language->get('heading_title'));

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'workflow/triggers')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('workflow/triggers', 'user_token=' . $this->session->data['user_token'], true)
        );

        // تحميل المحفزات
        $this->load->model('workflow/triggers');

        $filter_data = array(
            'start' => 0,
            'limit' => 20
        );

        $data['triggers'] = $this->model_workflow_triggers->getTriggers($filter_data);
        $data['total'] = $this->model_workflow_triggers->getTotalTriggers($filter_data);

        // إحصائيات المحفزات
        $data['trigger_stats'] = array(
            'total_triggers' => $this->model_workflow_triggers->getTotalTriggers(),
            'active_triggers' => $this->model_workflow_triggers->getActiveTriggers(),
            'executions_today' => $this->model_workflow_triggers->getExecutionsToday(),
            'success_rate' => $this->model_workflow_triggers->getSuccessRate(),
            'average_execution_time' => $this->model_workflow_triggers->getAverageExecutionTime(),
            'most_used_trigger' => $this->model_workflow_triggers->getMostUsedTrigger()
        );

        // أنواع المحفزات المتقدمة (شبيه بـ n8n)
        $data['trigger_types'] = array(
            'time_based' => array(
                'name' => $this->language->get('text_time_based_triggers'),
                'description' => $this->language->get('text_time_based_desc'),
                'icon' => 'fa-clock-o',
                'color' => 'primary',
                'triggers' => array(
                    'cron' => array(
                        'name' => $this->language->get('text_cron_trigger'),
                        'description' => 'تشغيل في أوقات محددة (يومي، أسبوعي، شهري)',
                        'config' => array('cron_expression', 'timezone', 'start_date', 'end_date'),
                        'examples' => array('0 9 * * 1-5', '0 0 1 * *', '*/15 * * * *')
                    ),
                    'interval' => array(
                        'name' => $this->language->get('text_interval_trigger'),
                        'description' => 'تشغيل كل فترة زمنية محددة',
                        'config' => array('interval_value', 'interval_unit', 'start_immediately'),
                        'examples' => array('كل 5 دقائق', 'كل ساعة', 'كل يوم')
                    ),
                    'delay' => array(
                        'name' => $this->language->get('text_delay_trigger'),
                        'description' => 'تأخير التنفيذ لفترة محددة',
                        'config' => array('delay_value', 'delay_unit'),
                        'examples' => array('بعد 10 دقائق', 'بعد ساعة', 'بعد يوم')
                    )
                )
            ),
            'event_based' => array(
                'name' => $this->language->get('text_event_based_triggers'),
                'description' => $this->language->get('text_event_based_desc'),
                'icon' => 'fa-bolt',
                'color' => 'warning',
                'triggers' => array(
                    'database_change' => array(
                        'name' => $this->language->get('text_database_trigger'),
                        'description' => 'تشغيل عند تغيير في قاعدة البيانات',
                        'config' => array('table_name', 'operation_type', 'conditions'),
                        'examples' => array('إضافة منتج جديد', 'تحديث المخزون', 'حذف عميل')
                    ),
                    'user_action' => array(
                        'name' => $this->language->get('text_user_action_trigger'),
                        'description' => 'تشغيل عند إجراء مستخدم معين',
                        'config' => array('action_type', 'user_roles', 'conditions'),
                        'examples' => array('تسجيل دخول', 'إنشاء طلب', 'موافقة مستند')
                    ),
                    'system_event' => array(
                        'name' => $this->language->get('text_system_event_trigger'),
                        'description' => 'تشغيل عند حدث نظام',
                        'config' => array('event_type', 'severity_level', 'conditions'),
                        'examples' => array('خطأ في النظام', 'نفاد المخزون', 'تجاوز الحد المسموح')
                    )
                )
            ),
            'external_triggers' => array(
                'name' => $this->language->get('text_external_triggers'),
                'description' => $this->language->get('text_external_desc'),
                'icon' => 'fa-globe',
                'color' => 'success',
                'triggers' => array(
                    'webhook' => array(
                        'name' => $this->language->get('text_webhook_trigger'),
                        'description' => 'تشغيل عند استقبال webhook من نظام خارجي',
                        'config' => array('webhook_url', 'http_method', 'authentication', 'headers'),
                        'examples' => array('طلب من متجر إلكتروني', 'تحديث من CRM', 'إشعار من بنك')
                    ),
                    'api_call' => array(
                        'name' => $this->language->get('text_api_trigger'),
                        'description' => 'تشغيل عند استدعاء API معين',
                        'config' => array('api_endpoint', 'method', 'parameters', 'authentication'),
                        'examples' => array('REST API', 'GraphQL', 'SOAP')
                    ),
                    'email_received' => array(
                        'name' => $this->language->get('text_email_trigger'),
                        'description' => 'تشغيل عند استقبال بريد إلكتروني',
                        'config' => array('email_account', 'filters', 'attachments'),
                        'examples' => array('طلب دعم', 'فاتورة من مورد', 'تأكيد طلب')
                    ),
                    'file_upload' => array(
                        'name' => $this->language->get('text_file_trigger'),
                        'description' => 'تشغيل عند رفع ملف',
                        'config' => array('file_path', 'file_types', 'size_limits'),
                        'examples' => array('رفع فاتورة', 'استيراد بيانات', 'تحديث كتالوج')
                    )
                )
            ),
            'ai_triggers' => array(
                'name' => $this->language->get('text_ai_triggers'),
                'description' => $this->language->get('text_ai_desc'),
                'icon' => 'fa-brain',
                'color' => 'info',
                'triggers' => array(
                    'ai_prediction' => array(
                        'name' => $this->language->get('text_ai_prediction_trigger'),
                        'description' => 'تشغيل عند توقع AI معين',
                        'config' => array('prediction_type', 'confidence_threshold', 'model_name'),
                        'examples' => array('توقع نفاد مخزون', 'توقع زيادة طلب', 'كشف احتيال')
                    ),
                    'pattern_detected' => array(
                        'name' => $this->language->get('text_pattern_trigger'),
                        'description' => 'تشغيل عند اكتشاف نمط معين',
                        'config' => array('pattern_type', 'data_source', 'sensitivity'),
                        'examples' => array('نمط شراء غير عادي', 'تغيير في السلوك', 'اتجاه جديد')
                    ),
                    'anomaly_detected' => array(
                        'name' => $this->language->get('text_anomaly_trigger'),
                        'description' => 'تشغيل عند اكتشاف شذوذ',
                        'config' => array('anomaly_type', 'threshold', 'data_source'),
                        'examples' => array('معاملة مشبوهة', 'استهلاك غير طبيعي', 'أداء منخفض')
                    )
                )
            ),
            'business_triggers' => array(
                'name' => $this->language->get('text_business_triggers'),
                'description' => $this->language->get('text_business_desc'),
                'icon' => 'fa-briefcase',
                'color' => 'secondary',
                'triggers' => array(
                    'inventory_level' => array(
                        'name' => $this->language->get('text_inventory_trigger'),
                        'description' => 'تشغيل عند وصول المخزون لحد معين',
                        'config' => array('product_id', 'threshold_type', 'threshold_value', 'warehouse_id'),
                        'examples' => array('مخزون أقل من 10', 'مخزون صفر', 'مخزون زائد')
                    ),
                    'sales_target' => array(
                        'name' => $this->language->get('text_sales_trigger'),
                        'description' => 'تشغيل عند تحقيق هدف مبيعات',
                        'config' => array('target_type', 'target_value', 'period', 'team_id'),
                        'examples' => array('تحقيق هدف شهري', 'تجاوز المتوقع', 'انخفاض المبيعات')
                    ),
                    'customer_behavior' => array(
                        'name' => $this->language->get('text_customer_trigger'),
                        'description' => 'تشغيل عند سلوك عميل معين',
                        'config' => array('behavior_type', 'customer_segment', 'conditions'),
                        'examples' => array('عميل جديد', 'عدم شراء لفترة', 'شراء كبير')
                    )
                )
            ),
            'integration_triggers' => array(
                'name' => $this->language->get('text_integration_triggers'),
                'description' => $this->language->get('text_integration_desc'),
                'icon' => 'fa-plug',
                'color' => 'dark',
                'triggers' => array(
                    'google_sheets' => array(
                        'name' => $this->language->get('text_google_sheets_trigger'),
                        'description' => 'تشغيل عند تغيير في Google Sheets',
                        'config' => array('sheet_id', 'range', 'trigger_type'),
                        'examples' => array('إضافة صف جديد', 'تحديث خلية', 'حذف بيانات')
                    ),
                    'slack_message' => array(
                        'name' => $this->language->get('text_slack_trigger'),
                        'description' => 'تشغيل عند رسالة Slack',
                        'config' => array('channel', 'keywords', 'user_filter'),
                        'examples' => array('رسالة في قناة معينة', 'ذكر كلمة مفتاحية', 'رسالة من مستخدم')
                    ),
                    'whatsapp_message' => array(
                        'name' => $this->language->get('text_whatsapp_trigger'),
                        'description' => 'تشغيل عند رسالة WhatsApp',
                        'config' => array('phone_number', 'message_type', 'keywords'),
                        'examples' => array('رسالة من عميل', 'طلب دعم', 'استفسار منتج')
                    )
                )
            )
        );

        // المحفزات النشطة حالياً
        $data['active_triggers'] = $this->model_workflow_triggers->getActiveTriggers(10);

        // سجل التنفيذ الأخير
        $data['recent_executions'] = $this->model_workflow_triggers->getRecentExecutions(20);

        // تحليل الأداء
        $data['performance_metrics'] = array(
            'execution_trends' => $this->model_workflow_triggers->getExecutionTrends(30),
            'success_rate_by_type' => $this->model_workflow_triggers->getSuccessRateByType(),
            'average_response_time' => $this->model_workflow_triggers->getAverageResponseTime(),
            'error_analysis' => $this->model_workflow_triggers->getErrorAnalysis()
        );

        // الروابط
        $data['add'] = $this->url->link('workflow/triggers/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['visual_editor'] = $this->url->link('workflow/triggers/visual_editor', 'user_token=' . $this->session->data['user_token'], true);
        $data['test_trigger'] = $this->url->link('workflow/triggers/test', 'user_token=' . $this->session->data['user_token'], true);
        $data['import_export'] = $this->url->link('workflow/triggers/import_export', 'user_token=' . $this->session->data['user_token'], true);
        $data['monitoring'] = $this->url->link('workflow/triggers/monitoring', 'user_token=' . $this->session->data['user_token'], true);
        $data['settings'] = $this->url->link('workflow/triggers/settings', 'user_token=' . $this->session->data['user_token'], true);

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

        $this->response->setOutput($this->load->view('workflow/triggers', $data));
    }

    /**
     * المحرر المرئي للمحفزات (شبيه بـ n8n)
     */
    public function visual_editor() {
        $this->load->language('workflow/triggers');

        $this->document->setTitle($this->language->get('text_visual_editor'));

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'workflow/triggers')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('workflow/triggers', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_visual_editor'),
            'href' => $this->url->link('workflow/triggers/visual_editor', 'user_token=' . $this->session->data['user_token'], true)
        );

        // إعدادات المحرر المرئي
        $data['editor_config'] = array(
            'canvas_size' => array('width' => 1200, 'height' => 800),
            'grid_enabled' => true,
            'snap_to_grid' => true,
            'zoom_levels' => array(0.5, 0.75, 1.0, 1.25, 1.5, 2.0),
            'auto_save' => true,
            'auto_save_interval' => 30000, // 30 ثانية
            'undo_redo_enabled' => true,
            'max_undo_steps' => 50
        );

        // عقد المحفزات المتاحة للسحب والإفلات
        $data['trigger_nodes'] = array(
            'time_triggers' => array(
                'category' => 'Time-based',
                'color' => '#007bff',
                'nodes' => array(
                    array(
                        'id' => 'cron_trigger',
                        'name' => 'Cron Schedule',
                        'icon' => 'fa-clock-o',
                        'inputs' => 0,
                        'outputs' => 1,
                        'config_fields' => array('cron_expression', 'timezone')
                    ),
                    array(
                        'id' => 'interval_trigger',
                        'name' => 'Interval',
                        'icon' => 'fa-repeat',
                        'inputs' => 0,
                        'outputs' => 1,
                        'config_fields' => array('interval_value', 'interval_unit')
                    )
                )
            ),
            'event_triggers' => array(
                'category' => 'Event-based',
                'color' => '#ffc107',
                'nodes' => array(
                    array(
                        'id' => 'database_trigger',
                        'name' => 'Database Change',
                        'icon' => 'fa-database',
                        'inputs' => 0,
                        'outputs' => 1,
                        'config_fields' => array('table_name', 'operation', 'conditions')
                    ),
                    array(
                        'id' => 'webhook_trigger',
                        'name' => 'Webhook',
                        'icon' => 'fa-globe',
                        'inputs' => 0,
                        'outputs' => 1,
                        'config_fields' => array('webhook_url', 'method', 'authentication')
                    )
                )
            ),
            'ai_triggers' => array(
                'category' => 'AI-powered',
                'color' => '#17a2b8',
                'nodes' => array(
                    array(
                        'id' => 'ai_prediction_trigger',
                        'name' => 'AI Prediction',
                        'icon' => 'fa-brain',
                        'inputs' => 0,
                        'outputs' => 1,
                        'config_fields' => array('prediction_type', 'confidence_threshold')
                    ),
                    array(
                        'id' => 'pattern_detection_trigger',
                        'name' => 'Pattern Detection',
                        'icon' => 'fa-search',
                        'inputs' => 0,
                        'outputs' => 1,
                        'config_fields' => array('pattern_type', 'data_source')
                    )
                )
            )
        );

        // عقد الإجراءات المتاحة
        $data['action_nodes'] = array(
            'data_operations' => array(
                'category' => 'Data Operations',
                'color' => '#28a745',
                'nodes' => array(
                    array(
                        'id' => 'create_record',
                        'name' => 'Create Record',
                        'icon' => 'fa-plus',
                        'inputs' => 1,
                        'outputs' => 1,
                        'config_fields' => array('table_name', 'data_mapping')
                    ),
                    array(
                        'id' => 'update_record',
                        'name' => 'Update Record',
                        'icon' => 'fa-edit',
                        'inputs' => 1,
                        'outputs' => 1,
                        'config_fields' => array('table_name', 'conditions', 'data_mapping')
                    )
                )
            ),
            'notifications' => array(
                'category' => 'Notifications',
                'color' => '#dc3545',
                'nodes' => array(
                    array(
                        'id' => 'send_email',
                        'name' => 'Send Email',
                        'icon' => 'fa-envelope',
                        'inputs' => 1,
                        'outputs' => 1,
                        'config_fields' => array('to', 'subject', 'template')
                    ),
                    array(
                        'id' => 'send_notification',
                        'name' => 'Send Notification',
                        'icon' => 'fa-bell',
                        'inputs' => 1,
                        'outputs' => 1,
                        'config_fields' => array('recipient', 'message', 'priority')
                    )
                )
            )
        );

        // عقد الشروط والمنطق
        $data['logic_nodes'] = array(
            array(
                'id' => 'if_condition',
                'name' => 'IF Condition',
                'icon' => 'fa-code-fork',
                'inputs' => 1,
                'outputs' => 2,
                'config_fields' => array('condition_expression')
            ),
            array(
                'id' => 'switch',
                'name' => 'Switch',
                'icon' => 'fa-random',
                'inputs' => 1,
                'outputs' => 'variable',
                'config_fields' => array('switch_expression', 'cases')
            ),
            array(
                'id' => 'merge',
                'name' => 'Merge',
                'icon' => 'fa-compress',
                'inputs' => 'variable',
                'outputs' => 1,
                'config_fields' => array('merge_mode')
            )
        );

        // قوالب Workflow جاهزة
        $data['workflow_templates'] = array(
            'inventory_management' => array(
                'name' => 'إدارة المخزون التلقائية',
                'description' => 'تنبيه عند نفاد المخزون وإنشاء طلب شراء تلقائي',
                'category' => 'inventory',
                'nodes_count' => 5,
                'complexity' => 'medium'
            ),
            'customer_onboarding' => array(
                'name' => 'استقبال العملاء الجدد',
                'description' => 'سير عمل ترحيب بالعملاء الجدد مع إرسال رسائل ترحيب',
                'category' => 'crm',
                'nodes_count' => 7,
                'complexity' => 'easy'
            ),
            'ai_demand_forecasting' => array(
                'name' => 'توقع الطلب بالذكاء الاصطناعي',
                'description' => 'تحليل بيانات المبيعات وتوقع الطلب المستقبلي',
                'category' => 'ai',
                'nodes_count' => 8,
                'complexity' => 'advanced'
            ),
            'document_approval' => array(
                'name' => 'موافقة المستندات',
                'description' => 'سير عمل موافقة المستندات مع تصعيد تلقائي',
                'category' => 'documents',
                'nodes_count' => 6,
                'complexity' => 'medium'
            )
        );

        // الروابط
        $data['save_workflow'] = $this->url->link('workflow/triggers/save_workflow', 'user_token=' . $this->session->data['user_token'], true);
        $data['load_workflow'] = $this->url->link('workflow/triggers/load_workflow', 'user_token=' . $this->session->data['user_token'], true);
        $data['test_workflow'] = $this->url->link('workflow/triggers/test_workflow', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_workflow'] = $this->url->link('workflow/triggers/export_workflow', 'user_token=' . $this->session->data['user_token'], true);
        $data['back'] = $this->url->link('workflow/triggers', 'user_token=' . $this->session->data['user_token'], true);

        // التوكن
        $data['user_token'] = $this->session->data['user_token'];

        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('workflow/triggers_visual_editor', $data));
    }

    /**
     * إضافة محفز جديد
     */
    public function add() {
        $this->load->language('workflow/triggers');

        $this->document->setTitle($this->language->get('text_add_trigger'));

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'workflow/triggers')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        // معالجة حفظ المحفز
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('workflow/triggers');

            $trigger_data = $this->request->post;
            $trigger_data['created_by'] = $this->user->getId();
            $trigger_data['created_at'] = date('Y-m-d H:i:s');
            $trigger_data['status'] = 'active';

            $trigger_id = $this->model_workflow_triggers->addTrigger($trigger_data);

            if ($trigger_id) {
                // تسجيل في نظام اللوج
                $this->logTriggerAction('create', $trigger_id, $trigger_data);

                // إرسال إشعار
                $this->sendTriggerNotification('trigger_created', $trigger_id, $trigger_data);

                // تفعيل المحفز إذا كان مطلوباً
                if (isset($trigger_data['activate_immediately']) && $trigger_data['activate_immediately']) {
                    $this->activateTrigger($trigger_id);
                }

                $this->session->data['success'] = $this->language->get('text_success');

                $this->response->redirect($this->url->link('workflow/triggers/edit', 'trigger_id=' . $trigger_id . '&user_token=' . $this->session->data['user_token'], true));
            } else {
                $this->error['warning'] = $this->language->get('error_trigger_creation_failed');
            }
        }

        $this->getForm();
    }

    /**
     * تعديل محفز موجود
     */
    public function edit() {
        $this->load->language('workflow/triggers');

        $this->document->setTitle($this->language->get('text_edit_trigger'));

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'workflow/triggers')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        // معالجة حفظ التعديلات
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('workflow/triggers');

            $trigger_data = $this->request->post;
            $trigger_data['modified_by'] = $this->user->getId();
            $trigger_data['modified_at'] = date('Y-m-d H:i:s');

            $this->model_workflow_triggers->editTrigger($this->request->get['trigger_id'], $trigger_data);

            // تسجيل في نظام اللوج
            $this->logTriggerAction('update', $this->request->get['trigger_id'], $trigger_data);

            // إرسال إشعار
            $this->sendTriggerNotification('trigger_updated', $this->request->get['trigger_id'], $trigger_data);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('workflow/triggers', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    /**
     * اختبار محفز
     */
    public function test() {
        $this->load->language('workflow/triggers');

        $json = array();

        if (!$this->user->hasPermission('modify', 'workflow/triggers')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($this->request->post['trigger_id'])) {
                $this->load->model('workflow/triggers');

                $trigger_id = (int)$this->request->post['trigger_id'];
                $test_data = $this->request->post['test_data'] ?? array();

                $test_result = $this->model_workflow_triggers->testTrigger($trigger_id, $test_data);

                if ($test_result['success']) {
                    // تسجيل نتيجة الاختبار
                    $this->logTriggerAction('test', $trigger_id, array(
                        'test_data' => $test_data,
                        'result' => $test_result
                    ));

                    $json['success'] = true;
                    $json['result'] = $test_result;
                    $json['message'] = $this->language->get('text_test_successful');
                } else {
                    $json['error'] = $test_result['error'];
                    $json['details'] = $test_result['details'] ?? '';
                }
            } else {
                $json['error'] = $this->language->get('error_test_validation');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تفعيل/إلغاء تفعيل محفز
     */
    public function toggle() {
        $this->load->language('workflow/triggers');

        $json = array();

        if (!$this->user->hasPermission('modify', 'workflow/triggers')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($this->request->post['trigger_id'])) {
                $this->load->model('workflow/triggers');

                $trigger_id = (int)$this->request->post['trigger_id'];
                $action = $this->request->post['action']; // activate or deactivate

                $result = $this->model_workflow_triggers->toggleTrigger($trigger_id, $action);

                if ($result['success']) {
                    // تسجيل في نظام اللوج
                    $this->logTriggerAction($action, $trigger_id, array('action' => $action));

                    // إرسال إشعار
                    $this->sendTriggerNotification('trigger_' . $action . 'd', $trigger_id, array());

                    $json['success'] = true;
                    $json['message'] = $this->language->get('text_' . $action . '_successful');
                    $json['new_status'] = $action == 'activate' ? 'active' : 'inactive';
                } else {
                    $json['error'] = $result['error'];
                }
            } else {
                $json['error'] = $this->language->get('error_toggle_validation');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * حفظ workflow من المحرر المرئي
     */
    public function save_workflow() {
        $this->load->language('workflow/triggers');

        $json = array();

        if (!$this->user->hasPermission('modify', 'workflow/triggers')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($this->request->post['workflow_data'])) {
                $this->load->model('workflow/triggers');

                $workflow_data = array(
                    'name' => $this->request->post['workflow_name'],
                    'description' => $this->request->post['workflow_description'],
                    'nodes' => json_encode($this->request->post['workflow_data']['nodes']),
                    'connections' => json_encode($this->request->post['workflow_data']['connections']),
                    'settings' => json_encode($this->request->post['workflow_data']['settings']),
                    'created_by' => $this->user->getId(),
                    'created_at' => date('Y-m-d H:i:s'),
                    'status' => 'draft'
                );

                $workflow_id = $this->model_workflow_triggers->saveWorkflow($workflow_data);

                if ($workflow_id) {
                    // تسجيل في نظام اللوج
                    $this->logTriggerAction('save_workflow', $workflow_id, $workflow_data);

                    $json['success'] = true;
                    $json['workflow_id'] = $workflow_id;
                    $json['message'] = $this->language->get('text_workflow_saved');
                } else {
                    $json['error'] = $this->language->get('error_workflow_save_failed');
                }
            } else {
                $json['error'] = $this->language->get('error_workflow_data_required');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تحميل workflow للمحرر المرئي
     */
    public function load_workflow() {
        $this->load->language('workflow/triggers');

        $json = array();

        if (!$this->user->hasPermission('access', 'workflow/triggers')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->get['workflow_id'])) {
                $this->load->model('workflow/triggers');

                $workflow_id = (int)$this->request->get['workflow_id'];
                $workflow = $this->model_workflow_triggers->getWorkflow($workflow_id);

                if ($workflow) {
                    $json['success'] = true;
                    $json['workflow'] = array(
                        'id' => $workflow['workflow_id'],
                        'name' => $workflow['name'],
                        'description' => $workflow['description'],
                        'nodes' => json_decode($workflow['nodes'], true),
                        'connections' => json_decode($workflow['connections'], true),
                        'settings' => json_decode($workflow['settings'], true)
                    );
                } else {
                    $json['error'] = $this->language->get('error_workflow_not_found');
                }
            } else {
                $json['error'] = $this->language->get('error_workflow_id_required');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * مراقبة المحفزات في الوقت الفعلي
     */
    public function monitoring() {
        $this->load->language('workflow/triggers');

        $this->document->setTitle($this->language->get('text_trigger_monitoring'));

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'workflow/triggers')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('workflow/triggers', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_trigger_monitoring'),
            'href' => $this->url->link('workflow/triggers/monitoring', 'user_token=' . $this->session->data['user_token'], true)
        );

        $this->load->model('workflow/triggers');

        // إعدادات المراقبة المباشرة
        $data['monitoring_config'] = array(
            'refresh_interval' => 5000, // 5 ثواني
            'max_log_entries' => 100,
            'auto_scroll' => true,
            'sound_alerts' => true
        );

        // المحفزات النشطة
        $data['active_triggers'] = $this->model_workflow_triggers->getActiveTriggers();

        // إحصائيات المراقبة
        $data['monitoring_stats'] = array(
            'triggers_running' => $this->model_workflow_triggers->getRunningTriggersCount(),
            'executions_per_minute' => $this->model_workflow_triggers->getExecutionsPerMinute(),
            'success_rate_last_hour' => $this->model_workflow_triggers->getSuccessRateLastHour(),
            'average_execution_time' => $this->model_workflow_triggers->getAverageExecutionTime(),
            'failed_executions_today' => $this->model_workflow_triggers->getFailedExecutionsToday()
        );

        // WebSocket configuration for real-time monitoring
        $data['websocket_config'] = array(
            'enabled' => true,
            'server' => 'ws://localhost:8084',
            'channel' => 'workflow_triggers',
            'user_token' => $this->session->data['user_token']
        );

        // الروابط
        $data['get_live_data'] = $this->url->link('workflow/triggers/get_live_data', 'user_token=' . $this->session->data['user_token'], true);
        $data['back'] = $this->url->link('workflow/triggers', 'user_token=' . $this->session->data['user_token'], true);

        // التوكن
        $data['user_token'] = $this->session->data['user_token'];

        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('workflow/triggers_monitoring', $data));
    }

    /**
     * الحصول على بيانات المراقبة المباشرة (AJAX)
     */
    public function get_live_data() {
        $json = array();

        if (!$this->user->hasPermission('access', 'workflow/triggers')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('workflow/triggers');

            $json['success'] = true;
            $json['timestamp'] = time();

            // بيانات التنفيذ المباشرة
            $json['live_data'] = array(
                'recent_executions' => $this->model_workflow_triggers->getRecentExecutions(20),
                'active_triggers' => $this->model_workflow_triggers->getActiveTriggers(),
                'system_load' => $this->model_workflow_triggers->getSystemLoad(),
                'queue_status' => $this->model_workflow_triggers->getQueueStatus(),
                'error_alerts' => $this->model_workflow_triggers->getRecentErrors(10)
            );

            // إحصائيات محدثة
            $json['stats'] = array(
                'executions_last_minute' => $this->model_workflow_triggers->getExecutionsLastMinute(),
                'success_rate' => $this->model_workflow_triggers->getCurrentSuccessRate(),
                'average_response_time' => $this->model_workflow_triggers->getCurrentAverageResponseTime(),
                'triggers_count' => $this->model_workflow_triggers->getActiveTriggersCount()
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * دوال مساعدة
     */
    protected function getForm() {
        // تحميل البيانات للنموذج
        // ... (كود النموذج)
    }

    /**
     * تفعيل محفز
     */
    private function activateTrigger($trigger_id) {
        $this->load->model('workflow/triggers');
        return $this->model_workflow_triggers->activateTrigger($trigger_id);
    }

    /**
     * تسجيل إجراء المحفز
     */
    private function logTriggerAction($action, $trigger_id, $data) {
        $this->load->model('logging/user_activity');

        $activity_data = array(
            'action_type' => 'trigger_' . $action,
            'module' => 'workflow/triggers',
            'description' => 'تم ' . $action . ' المحفز رقم ' . $trigger_id,
            'reference_type' => 'workflow_trigger',
            'reference_id' => $trigger_id
        );

        $this->model_logging_user_activity->addActivity($activity_data);
    }

    /**
     * إرسال إشعار المحفز
     */
    private function sendTriggerNotification($type, $trigger_id, $data) {
        $this->load->model('notification/center');

        $notification_data = array(
            'type' => $type,
            'recipient_id' => $this->user->getId(),
            'title' => 'إشعار محفز: ' . $type,
            'message' => 'تم تنفيذ إجراء على المحفز رقم ' . $trigger_id,
            'priority' => 'medium',
            'link' => 'workflow/triggers/edit&trigger_id=' . $trigger_id,
            'reference_type' => 'workflow_trigger',
            'reference_id' => $trigger_id
        );

        $this->model_notification_center->addNotification($notification_data);
    }

    /**
     * التحقق من صحة النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'workflow/triggers')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['name'])) {
            $this->error['name'] = $this->language->get('error_name_required');
        }

        if (empty($this->request->post['trigger_type'])) {
            $this->error['trigger_type'] = $this->language->get('error_trigger_type_required');
        }

        if (empty($this->request->post['configuration'])) {
            $this->error['configuration'] = $this->language->get('error_configuration_required');
        }

        return !$this->error;
    }
}
