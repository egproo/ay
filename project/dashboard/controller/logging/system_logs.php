<?php
/**
 * نظام سجلات النظام المتقدم
 * Advanced System Logs Controller
 * 
 * نظام سجلات شامل مع تكامل مع catalog/inventory وAI
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

class ControllerLoggingSystemLogs extends Controller {
    
    /**
     * @var array خطأ في النظام
     */
    private $error = array();
    
    /**
     * عرض صفحة سجلات النظام الرئيسية
     */
    public function index() {
        $this->load->language('logging/system_logs');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'logging/system_logs')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('logging/system_logs', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // تحميل السجلات
        $this->load->model('logging/system_logs');
        
        $filter_data = array(
            'start' => 0,
            'limit' => 50
        );
        
        // تطبيق الفلاتر
        if (isset($this->request->get['filter_level'])) {
            $filter_data['filter_level'] = $this->request->get['filter_level'];
        }
        
        if (isset($this->request->get['filter_module'])) {
            $filter_data['filter_module'] = $this->request->get['filter_module'];
        }
        
        if (isset($this->request->get['filter_date_from'])) {
            $filter_data['filter_date_from'] = $this->request->get['filter_date_from'];
        }
        
        if (isset($this->request->get['filter_date_to'])) {
            $filter_data['filter_date_to'] = $this->request->get['filter_date_to'];
        }
        
        $data['logs'] = $this->model_logging_system_logs->getLogs($filter_data);
        $data['total'] = $this->model_logging_system_logs->getTotalLogs($filter_data);
        
        // مستويات السجلات
        $data['log_levels'] = array(
            'emergency' => array(
                'name' => $this->language->get('text_level_emergency'),
                'color' => 'danger',
                'icon' => 'fa-exclamation-circle'
            ),
            'alert' => array(
                'name' => $this->language->get('text_level_alert'),
                'color' => 'danger',
                'icon' => 'fa-bell'
            ),
            'critical' => array(
                'name' => $this->language->get('text_level_critical'),
                'color' => 'danger',
                'icon' => 'fa-times-circle'
            ),
            'error' => array(
                'name' => $this->language->get('text_level_error'),
                'color' => 'danger',
                'icon' => 'fa-exclamation-triangle'
            ),
            'warning' => array(
                'name' => $this->language->get('text_level_warning'),
                'color' => 'warning',
                'icon' => 'fa-warning'
            ),
            'notice' => array(
                'name' => $this->language->get('text_level_notice'),
                'color' => 'info',
                'icon' => 'fa-info-circle'
            ),
            'info' => array(
                'name' => $this->language->get('text_level_info'),
                'color' => 'info',
                'icon' => 'fa-info'
            ),
            'debug' => array(
                'name' => $this->language->get('text_level_debug'),
                'color' => 'secondary',
                'icon' => 'fa-bug'
            )
        );
        
        // وحدات النظام
        $data['system_modules'] = array(
            'catalog' => $this->language->get('text_module_catalog'),
            'inventory' => $this->language->get('text_module_inventory'),
            'purchase' => $this->language->get('text_module_purchase'),
            'sales' => $this->language->get('text_module_sales'),
            'accounts' => $this->language->get('text_module_accounts'),
            'finance' => $this->language->get('text_module_finance'),
            'hr' => $this->language->get('text_module_hr'),
            'crm' => $this->language->get('text_module_crm'),
            'pos' => $this->language->get('text_module_pos'),
            'shipping' => $this->language->get('text_module_shipping'),
            'workflow' => $this->language->get('text_module_workflow'),
            'ai' => $this->language->get('text_module_ai'),
            'notification' => $this->language->get('text_module_notification'),
            'communication' => $this->language->get('text_module_communication'),
            'system' => $this->language->get('text_module_system'),
            'security' => $this->language->get('text_module_security'),
            'database' => $this->language->get('text_module_database'),
            'api' => $this->language->get('text_module_api')
        );
        
        // إحصائيات السجلات
        $data['log_stats'] = array(
            'total_logs_today' => $this->model_logging_system_logs->getTodayLogsCount(),
            'error_logs_today' => $this->model_logging_system_logs->getTodayErrorsCount(),
            'warning_logs_today' => $this->model_logging_system_logs->getTodayWarningsCount(),
            'critical_logs_today' => $this->model_logging_system_logs->getTodayCriticalCount(),
            'most_active_module' => $this->model_logging_system_logs->getMostActiveModule(),
            'error_rate_percentage' => $this->model_logging_system_logs->getErrorRatePercentage()
        );
        
        // سجلات خاصة بـ catalog/inventory
        $data['specialized_logs'] = array(
            'catalog_logs' => $this->model_logging_system_logs->getCatalogLogs(10),
            'inventory_logs' => $this->model_logging_system_logs->getInventoryLogs(10),
            'ai_logs' => $this->model_logging_system_logs->getAILogs(10),
            'workflow_logs' => $this->model_logging_system_logs->getWorkflowLogs(10)
        );
        
        // تحليل الأخطاء الشائعة
        $data['common_errors'] = $this->model_logging_system_logs->getCommonErrors(5);
        
        // تحليل الأداء
        $data['performance_metrics'] = array(
            'average_response_time' => $this->model_logging_system_logs->getAverageResponseTime(),
            'slow_queries_count' => $this->model_logging_system_logs->getSlowQueriesCount(),
            'memory_usage_peak' => $this->model_logging_system_logs->getPeakMemoryUsage(),
            'cpu_usage_average' => $this->model_logging_system_logs->getAverageCpuUsage()
        );
        
        // الروابط
        $data['export'] = $this->url->link('logging/system_logs/export', 'user_token=' . $this->session->data['user_token'], true);
        $data['clear'] = $this->url->link('logging/system_logs/clear', 'user_token=' . $this->session->data['user_token'], true);
        $data['settings'] = $this->url->link('logging/system_logs/settings', 'user_token=' . $this->session->data['user_token'], true);
        $data['real_time'] = $this->url->link('logging/system_logs/realtime', 'user_token=' . $this->session->data['user_token'], true);
        
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
        
        $this->response->setOutput($this->load->view('logging/system_logs', $data));
    }
    
    /**
     * عرض تفاصيل سجل محدد
     */
    public function view() {
        $this->load->language('logging/system_logs');
        
        if (isset($this->request->get['log_id'])) {
            $log_id = (int)$this->request->get['log_id'];
        } else {
            $log_id = 0;
        }
        
        $this->load->model('logging/system_logs');
        
        $log_info = $this->model_logging_system_logs->getLog($log_id);
        
        if ($log_info) {
            $this->document->setTitle($this->language->get('text_view_log'));
            
            $data['breadcrumbs'] = array();
            
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('logging/system_logs', 'user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_view_log'),
                'href' => $this->url->link('logging/system_logs/view', 'log_id=' . $log_id . '&user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['log'] = $log_info;
            
            // تحليل السجل
            $data['log_analysis'] = $this->analyzeLog($log_info);
            
            // السجلات ذات الصلة
            $data['related_logs'] = $this->model_logging_system_logs->getRelatedLogs($log_id, 5);
            
            // معلومات إضافية حسب نوع السجل
            if ($log_info['module'] == 'catalog') {
                $data['catalog_context'] = $this->getCatalogContext($log_info);
            } elseif ($log_info['module'] == 'inventory') {
                $data['inventory_context'] = $this->getInventoryContext($log_info);
            } elseif ($log_info['module'] == 'ai') {
                $data['ai_context'] = $this->getAIContext($log_info);
            }
            
            // الروابط
            $data['back'] = $this->url->link('logging/system_logs', 'user_token=' . $this->session->data['user_token'], true);
            
            // التوكن
            $data['user_token'] = $this->session->data['user_token'];
            
            // عرض الصفحة
            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');
            
            $this->response->setOutput($this->load->view('logging/system_log_view', $data));
        } else {
            $this->response->redirect($this->url->link('logging/system_logs', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    /**
     * مراقبة السجلات في الوقت الفعلي
     */
    public function realtime() {
        $this->load->language('logging/system_logs');
        
        $this->document->setTitle($this->language->get('text_realtime_monitoring'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'logging/system_logs')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('logging/system_logs', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_realtime_monitoring'),
            'href' => $this->url->link('logging/system_logs/realtime', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // إعدادات المراقبة المباشرة
        $data['realtime_config'] = array(
            'refresh_interval' => 5000, // 5 ثوان
            'max_logs_display' => 100,
            'auto_scroll' => true,
            'sound_alerts' => true,
            'filter_critical' => true
        );
        
        // WebSocket configuration for real-time logs
        $data['websocket_config'] = array(
            'enabled' => true,
            'server' => 'ws://localhost:8081',
            'channel' => 'system_logs',
            'user_token' => $this->session->data['user_token']
        );
        
        // الروابط
        $data['get_latest'] = $this->url->link('logging/system_logs/getLatest', 'user_token=' . $this->session->data['user_token'], true);
        $data['back'] = $this->url->link('logging/system_logs', 'user_token=' . $this->session->data['user_token'], true);
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('logging/system_logs_realtime', $data));
    }
    
    /**
     * الحصول على أحدث السجلات (AJAX)
     */
    public function getLatest() {
        $json = array();
        
        if (!$this->user->hasPermission('access', 'logging/system_logs')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('logging/system_logs');
            
            $last_log_id = isset($this->request->get['last_log_id']) ? (int)$this->request->get['last_log_id'] : 0;
            $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 20;
            
            $latest_logs = $this->model_logging_system_logs->getLatestLogs($last_log_id, $limit);
            
            $json['success'] = true;
            $json['logs'] = $latest_logs;
            $json['count'] = count($latest_logs);
            $json['last_log_id'] = !empty($latest_logs) ? $latest_logs[0]['log_id'] : $last_log_id;
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * تصدير السجلات
     */
    public function export() {
        $this->load->language('logging/system_logs');
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'logging/system_logs')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->load->model('logging/system_logs');
        
        $filter_data = array();
        
        // تطبيق الفلاتر من الطلب
        if (isset($this->request->get['filter_level'])) {
            $filter_data['filter_level'] = $this->request->get['filter_level'];
        }
        
        if (isset($this->request->get['filter_module'])) {
            $filter_data['filter_module'] = $this->request->get['filter_module'];
        }
        
        if (isset($this->request->get['filter_date_from'])) {
            $filter_data['filter_date_from'] = $this->request->get['filter_date_from'];
        }
        
        if (isset($this->request->get['filter_date_to'])) {
            $filter_data['filter_date_to'] = $this->request->get['filter_date_to'];
        }
        
        $logs = $this->model_logging_system_logs->getLogs($filter_data);
        
        $filename = 'system_logs_' . date('Y-m-d_H-i-s') . '.csv';
        
        $this->response->addheader('Pragma: public');
        $this->response->addheader('Expires: 0');
        $this->response->addheader('Content-Description: File Transfer');
        $this->response->addheader('Content-Type: application/csv');
        $this->response->addheader('Content-Disposition: attachment; filename="' . $filename . '"');
        $this->response->addheader('Content-Transfer-Encoding: binary');
        
        $output = '';
        
        // رأس الملف
        $output .= 'التاريخ,المستوى,الوحدة,الرسالة,المستخدم,IP' . "\n";
        
        // البيانات
        foreach ($logs as $log) {
            $output .= '"' . $log['date_added'] . '",';
            $output .= '"' . $log['level'] . '",';
            $output .= '"' . $log['module'] . '",';
            $output .= '"' . str_replace('"', '""', $log['message']) . '",';
            $output .= '"' . $log['username'] . '",';
            $output .= '"' . $log['ip_address'] . '"' . "\n";
        }
        
        $this->response->setOutput($output);
    }
    
    /**
     * تحليل السجل
     */
    private function analyzeLog($log_info) {
        $analysis = array(
            'severity_score' => $this->calculateSeverityScore($log_info['level']),
            'frequency' => $this->getLogFrequency($log_info['message']),
            'impact_assessment' => $this->assessImpact($log_info),
            'recommendations' => $this->getRecommendations($log_info)
        );
        
        return $analysis;
    }
    
    /**
     * حساب درجة الخطورة
     */
    private function calculateSeverityScore($level) {
        $scores = array(
            'emergency' => 100,
            'alert' => 90,
            'critical' => 80,
            'error' => 70,
            'warning' => 50,
            'notice' => 30,
            'info' => 20,
            'debug' => 10
        );
        
        return isset($scores[$level]) ? $scores[$level] : 0;
    }
    
    /**
     * الحصول على تكرار السجل
     */
    private function getLogFrequency($message) {
        $this->load->model('logging/system_logs');
        return $this->model_logging_system_logs->getMessageFrequency($message);
    }
    
    /**
     * تقييم التأثير
     */
    private function assessImpact($log_info) {
        // منطق تقييم التأثير بناءً على نوع السجل والوحدة
        $impact = 'منخفض';
        
        if (in_array($log_info['level'], array('emergency', 'alert', 'critical'))) {
            $impact = 'عالي';
        } elseif ($log_info['level'] == 'error') {
            $impact = 'متوسط';
        }
        
        // تأثير خاص بوحدات catalog/inventory
        if (in_array($log_info['module'], array('catalog', 'inventory')) && $log_info['level'] == 'error') {
            $impact = 'عالي'; // أخطاء المخزون والكتالوج لها تأثير عالي
        }
        
        return $impact;
    }
    
    /**
     * الحصول على التوصيات
     */
    private function getRecommendations($log_info) {
        $recommendations = array();
        
        switch ($log_info['level']) {
            case 'critical':
            case 'emergency':
                $recommendations[] = 'تدخل فوري مطلوب';
                $recommendations[] = 'إشعار فريق الدعم الفني';
                break;
            case 'error':
                $recommendations[] = 'مراجعة السبب وإصلاحه';
                $recommendations[] = 'مراقبة التكرار';
                break;
            case 'warning':
                $recommendations[] = 'مراجعة دورية';
                $recommendations[] = 'تحسين الأداء';
                break;
        }
        
        return $recommendations;
    }
    
    /**
     * الحصول على سياق الكتالوج
     */
    private function getCatalogContext($log_info) {
        // استخراج معلومات إضافية خاصة بالكتالوج
        return array();
    }
    
    /**
     * الحصول على سياق المخزون
     */
    private function getInventoryContext($log_info) {
        // استخراج معلومات إضافية خاصة بالمخزون
        return array();
    }
    
    /**
     * الحصول على سياق الـ AI
     */
    private function getAIContext($log_info) {
        // استخراج معلومات إضافية خاصة بالـ AI
        return array();
    }
}
