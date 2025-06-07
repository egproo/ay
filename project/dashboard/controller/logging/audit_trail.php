<?php
/**
 * نظام مسار المراجعة المتقدم
 * Advanced Audit Trail Controller
 * 
 * نظام شامل لتتبع جميع التغييرات والعمليات الحساسة مع تكامل مع catalog/inventory
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

class ControllerLoggingAuditTrail extends Controller {
    
    /**
     * @var array خطأ في النظام
     */
    private $error = array();
    
    /**
     * عرض صفحة مسار المراجعة الرئيسية
     */
    public function index() {
        $this->load->language('logging/audit_trail');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'logging/audit_trail')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('logging/audit_trail', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // تحميل سجلات المراجعة
        $this->load->model('logging/audit_trail');
        
        $filter_data = array(
            'start' => 0,
            'limit' => 50
        );
        
        // تطبيق الفلاتر
        if (isset($this->request->get['filter_table'])) {
            $filter_data['filter_table'] = $this->request->get['filter_table'];
        }
        
        if (isset($this->request->get['filter_operation'])) {
            $filter_data['filter_operation'] = $this->request->get['filter_operation'];
        }
        
        if (isset($this->request->get['filter_user'])) {
            $filter_data['filter_user'] = $this->request->get['filter_user'];
        }
        
        if (isset($this->request->get['filter_date_from'])) {
            $filter_data['filter_date_from'] = $this->request->get['filter_date_from'];
        }
        
        if (isset($this->request->get['filter_date_to'])) {
            $filter_data['filter_date_to'] = $this->request->get['filter_date_to'];
        }
        
        $data['audit_logs'] = $this->model_logging_audit_trail->getAuditLogs($filter_data);
        $data['total'] = $this->model_logging_audit_trail->getTotalAuditLogs($filter_data);
        
        // إحصائيات المراجعة
        $data['audit_stats'] = array(
            'total_changes_today' => $this->model_logging_audit_trail->getTodayChanges(),
            'critical_changes_today' => $this->model_logging_audit_trail->getTodayCriticalChanges(),
            'most_modified_table' => $this->model_logging_audit_trail->getMostModifiedTable(),
            'most_active_user' => $this->model_logging_audit_trail->getMostActiveUser(),
            'data_integrity_score' => $this->model_logging_audit_trail->getDataIntegrityScore(),
            'compliance_score' => $this->model_logging_audit_trail->getComplianceScore()
        );
        
        // أنواع العمليات
        $data['operation_types'] = array(
            'INSERT' => array(
                'name' => $this->language->get('text_operation_insert'),
                'icon' => 'fa-plus',
                'color' => 'success'
            ),
            'UPDATE' => array(
                'name' => $this->language->get('text_operation_update'),
                'icon' => 'fa-edit',
                'color' => 'warning'
            ),
            'DELETE' => array(
                'name' => $this->language->get('text_operation_delete'),
                'icon' => 'fa-trash',
                'color' => 'danger'
            ),
            'SELECT' => array(
                'name' => $this->language->get('text_operation_select'),
                'icon' => 'fa-eye',
                'color' => 'info'
            )
        );
        
        // الجداول المراقبة (خاصة بـ catalog/inventory)
        $data['monitored_tables'] = array(
            'catalog' => array(
                'cod_product' => array(
                    'name' => $this->language->get('text_table_products'),
                    'description' => $this->language->get('text_table_products_desc'),
                    'criticality' => 'high',
                    'changes_today' => $this->model_logging_audit_trail->getTableChangesToday('cod_product')
                ),
                'cod_category' => array(
                    'name' => $this->language->get('text_table_categories'),
                    'description' => $this->language->get('text_table_categories_desc'),
                    'criticality' => 'medium',
                    'changes_today' => $this->model_logging_audit_trail->getTableChangesToday('cod_category')
                ),
                'cod_product_special' => array(
                    'name' => $this->language->get('text_table_special_prices'),
                    'description' => $this->language->get('text_table_special_prices_desc'),
                    'criticality' => 'high',
                    'changes_today' => $this->model_logging_audit_trail->getTableChangesToday('cod_product_special')
                )
            ),
            'inventory' => array(
                'cod_product_quantity' => array(
                    'name' => $this->language->get('text_table_inventory'),
                    'description' => $this->language->get('text_table_inventory_desc'),
                    'criticality' => 'critical',
                    'changes_today' => $this->model_logging_audit_trail->getTableChangesToday('cod_product_quantity')
                ),
                'cod_stock_movement' => array(
                    'name' => $this->language->get('text_table_stock_movements'),
                    'description' => $this->language->get('text_table_stock_movements_desc'),
                    'criticality' => 'high',
                    'changes_today' => $this->model_logging_audit_trail->getTableChangesToday('cod_stock_movement')
                ),
                'cod_warehouse' => array(
                    'name' => $this->language->get('text_table_warehouses'),
                    'description' => $this->language->get('text_table_warehouses_desc'),
                    'criticality' => 'medium',
                    'changes_today' => $this->model_logging_audit_trail->getTableChangesToday('cod_warehouse')
                )
            ),
            'financial' => array(
                'cod_order' => array(
                    'name' => $this->language->get('text_table_orders'),
                    'description' => $this->language->get('text_table_orders_desc'),
                    'criticality' => 'critical',
                    'changes_today' => $this->model_logging_audit_trail->getTableChangesToday('cod_order')
                ),
                'cod_order_product' => array(
                    'name' => $this->language->get('text_table_order_products'),
                    'description' => $this->language->get('text_table_order_products_desc'),
                    'criticality' => 'high',
                    'changes_today' => $this->model_logging_audit_trail->getTableChangesToday('cod_order_product')
                )
            ),
            'system' => array(
                'cod_user' => array(
                    'name' => $this->language->get('text_table_users'),
                    'description' => $this->language->get('text_table_users_desc'),
                    'criticality' => 'critical',
                    'changes_today' => $this->model_logging_audit_trail->getTableChangesToday('cod_user')
                ),
                'cod_user_group' => array(
                    'name' => $this->language->get('text_table_user_groups'),
                    'description' => $this->language->get('text_table_user_groups_desc'),
                    'criticality' => 'high',
                    'changes_today' => $this->model_logging_audit_trail->getTableChangesToday('cod_user_group')
                )
            )
        );
        
        // التغييرات الحرجة الأخيرة
        $data['critical_changes'] = $this->model_logging_audit_trail->getCriticalChanges(10);
        
        // تحليل الامتثال
        $data['compliance_analysis'] = array(
            'gdpr_compliance' => $this->model_logging_audit_trail->getGDPRCompliance(),
            'sox_compliance' => $this->model_logging_audit_trail->getSOXCompliance(),
            'iso_compliance' => $this->model_logging_audit_trail->getISOCompliance(),
            'data_retention' => $this->model_logging_audit_trail->getDataRetentionCompliance()
        );
        
        // تحليل المخاطر
        $data['risk_analysis'] = array(
            'unauthorized_changes' => $this->model_logging_audit_trail->getUnauthorizedChanges(),
            'bulk_operations' => $this->model_logging_audit_trail->getBulkOperations(),
            'after_hours_changes' => $this->model_logging_audit_trail->getAfterHoursChanges(),
            'privilege_escalations' => $this->model_logging_audit_trail->getPrivilegeEscalations()
        );
        
        // قائمة المستخدمين للفلترة
        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();
        
        // الروابط
        $data['export'] = $this->url->link('logging/audit_trail/export', 'user_token=' . $this->session->data['user_token'], true);
        $data['compliance_report'] = $this->url->link('logging/audit_trail/compliance_report', 'user_token=' . $this->session->data['user_token'], true);
        $data['integrity_check'] = $this->url->link('logging/audit_trail/integrity_check', 'user_token=' . $this->session->data['user_token'], true);
        $data['settings'] = $this->url->link('logging/audit_trail/settings', 'user_token=' . $this->session->data['user_token'], true);
        
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
        
        $this->response->setOutput($this->load->view('logging/audit_trail', $data));
    }
    
    /**
     * عرض تفاصيل تغيير محدد
     */
    public function view_change() {
        $this->load->language('logging/audit_trail');
        
        if (isset($this->request->get['audit_id'])) {
            $audit_id = (int)$this->request->get['audit_id'];
        } else {
            $audit_id = 0;
        }
        
        $this->load->model('logging/audit_trail');
        
        $audit_info = $this->model_logging_audit_trail->getAuditLog($audit_id);
        
        if ($audit_info) {
            $this->document->setTitle($this->language->get('text_change_details'));
            
            $data['breadcrumbs'] = array();
            
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('logging/audit_trail', 'user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_change_details'),
                'href' => $this->url->link('logging/audit_trail/view_change', 'audit_id=' . $audit_id . '&user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['audit_info'] = $audit_info;
            
            // تحليل التغيير
            $data['change_analysis'] = $this->analyzeChange($audit_info);
            
            // التغييرات ذات الصلة
            $data['related_changes'] = $this->model_logging_audit_trail->getRelatedChanges($audit_id, 5);
            
            // تأثير التغيير
            $data['change_impact'] = $this->assessChangeImpact($audit_info);
            
            // الروابط
            $data['back'] = $this->url->link('logging/audit_trail', 'user_token=' . $this->session->data['user_token'], true);
            $data['revert'] = $this->url->link('logging/audit_trail/revert_change', 'audit_id=' . $audit_id . '&user_token=' . $this->session->data['user_token'], true);
            
            // التوكن
            $data['user_token'] = $this->session->data['user_token'];
            
            // عرض الصفحة
            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');
            
            $this->response->setOutput($this->load->view('logging/audit_trail_change_detail', $data));
        } else {
            $this->response->redirect($this->url->link('logging/audit_trail', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    /**
     * فحص سلامة البيانات
     */
    public function integrity_check() {
        $this->load->language('logging/audit_trail');
        
        $this->document->setTitle($this->language->get('text_integrity_check'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'logging/audit_trail')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->load->model('logging/audit_trail');
        
        // تشغيل فحص سلامة البيانات
        $integrity_results = $this->model_logging_audit_trail->performIntegrityCheck();
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('logging/audit_trail', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_integrity_check'),
            'href' => $this->url->link('logging/audit_trail/integrity_check', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['integrity_results'] = $integrity_results;
        
        // فحص خاص بـ catalog/inventory
        $data['catalog_integrity'] = $this->model_logging_audit_trail->checkCatalogIntegrity();
        $data['inventory_integrity'] = $this->model_logging_audit_trail->checkInventoryIntegrity();
        
        // الروابط
        $data['back'] = $this->url->link('logging/audit_trail', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_report'] = $this->url->link('logging/audit_trail/export_integrity_report', 'user_token=' . $this->session->data['user_token'], true);
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('logging/audit_trail_integrity', $data));
    }
    
    /**
     * تقرير الامتثال
     */
    public function compliance_report() {
        $this->load->language('logging/audit_trail');
        
        $this->document->setTitle($this->language->get('text_compliance_report'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'logging/audit_trail')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->load->model('logging/audit_trail');
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('logging/audit_trail', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_compliance_report'),
            'href' => $this->url->link('logging/audit_trail/compliance_report', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // تقارير الامتثال الشاملة
        $data['compliance_reports'] = array(
            'gdpr' => $this->model_logging_audit_trail->generateGDPRReport(),
            'sox' => $this->model_logging_audit_trail->generateSOXReport(),
            'iso27001' => $this->model_logging_audit_trail->generateISO27001Report(),
            'pci_dss' => $this->model_logging_audit_trail->generatePCIDSSReport()
        );
        
        // تقييم المخاطر
        $data['risk_assessment'] = $this->model_logging_audit_trail->performRiskAssessment();
        
        // توصيات التحسين
        $data['improvement_recommendations'] = $this->model_logging_audit_trail->getImprovementRecommendations();
        
        // الروابط
        $data['back'] = $this->url->link('logging/audit_trail', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_compliance'] = $this->url->link('logging/audit_trail/export_compliance', 'user_token=' . $this->session->data['user_token'], true);
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('logging/audit_trail_compliance', $data));
    }
    
    /**
     * تسجيل تغيير في مسار المراجعة (يتم استدعاؤها من النظام)
     */
    public function logChange($table_name, $operation, $record_id, $old_values = null, $new_values = null) {
        $this->load->model('logging/audit_trail');
        
        $audit_data = array(
            'table_name' => $table_name,
            'operation' => $operation,
            'record_id' => $record_id,
            'old_values' => $old_values ? json_encode($old_values) : null,
            'new_values' => $new_values ? json_encode($new_values) : null,
            'user_id' => $this->user->getId(),
            'ip_address' => $this->request->server['REMOTE_ADDR'],
            'user_agent' => $this->request->server['HTTP_USER_AGENT'],
            'timestamp' => date('Y-m-d H:i:s')
        );
        
        return $this->model_logging_audit_trail->addAuditLog($audit_data);
    }
    
    /**
     * تحليل التغيير
     */
    private function analyzeChange($audit_info) {
        $analysis = array(
            'risk_level' => $this->calculateRiskLevel($audit_info),
            'business_impact' => $this->assessBusinessImpact($audit_info),
            'compliance_impact' => $this->assessComplianceImpact($audit_info),
            'data_sensitivity' => $this->assessDataSensitivity($audit_info['table_name'])
        );
        
        return $analysis;
    }
    
    /**
     * حساب مستوى المخاطر
     */
    private function calculateRiskLevel($audit_info) {
        $risk_score = 0;
        
        // مستوى المخاطر حسب الجدول
        $table_risks = array(
            'cod_user' => 90,
            'cod_product_quantity' => 80,
            'cod_order' => 85,
            'cod_product' => 70,
            'cod_category' => 50
        );
        
        $risk_score += $table_risks[$audit_info['table_name']] ?? 30;
        
        // مستوى المخاطر حسب العملية
        if ($audit_info['operation'] == 'DELETE') {
            $risk_score += 20;
        } elseif ($audit_info['operation'] == 'UPDATE') {
            $risk_score += 10;
        }
        
        // تحديد مستوى المخاطر
        if ($risk_score >= 80) {
            return 'critical';
        } elseif ($risk_score >= 60) {
            return 'high';
        } elseif ($risk_score >= 40) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * تقييم تأثير التغيير
     */
    private function assessChangeImpact($audit_info) {
        // منطق تقييم تأثير التغيير
        return array(
            'financial_impact' => 'medium',
            'operational_impact' => 'low',
            'security_impact' => 'high',
            'compliance_impact' => 'medium'
        );
    }
    
    /**
     * تقييم التأثير التجاري
     */
    private function assessBusinessImpact($audit_info) {
        // منطق تقييم التأثير التجاري
        return 'medium';
    }
    
    /**
     * تقييم تأثير الامتثال
     */
    private function assessComplianceImpact($audit_info) {
        // منطق تقييم تأثير الامتثال
        return 'low';
    }
    
    /**
     * تقييم حساسية البيانات
     */
    private function assessDataSensitivity($table_name) {
        $sensitive_tables = array(
            'cod_user' => 'high',
            'cod_customer' => 'high',
            'cod_order' => 'medium',
            'cod_product_quantity' => 'medium',
            'cod_product' => 'low'
        );
        
        return $sensitive_tables[$table_name] ?? 'low';
    }
}
