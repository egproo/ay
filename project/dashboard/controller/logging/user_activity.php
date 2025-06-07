<?php
/**
 * نظام تتبع نشاط المستخدمين المتقدم
 * Advanced User Activity Tracking Controller
 * 
 * نظام شامل لتتبع ومراقبة نشاط المستخدمين مع تكامل مع catalog/inventory
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

class ControllerLoggingUserActivity extends Controller {
    
    /**
     * @var array خطأ في النظام
     */
    private $error = array();
    
    /**
     * عرض صفحة نشاط المستخدمين الرئيسية
     */
    public function index() {
        $this->load->language('logging/user_activity');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'logging/user_activity')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('logging/user_activity', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // تحميل نشاط المستخدمين
        $this->load->model('logging/user_activity');
        
        $filter_data = array(
            'start' => 0,
            'limit' => 50
        );
        
        // تطبيق الفلاتر
        if (isset($this->request->get['filter_user'])) {
            $filter_data['filter_user'] = $this->request->get['filter_user'];
        }
        
        if (isset($this->request->get['filter_action'])) {
            $filter_data['filter_action'] = $this->request->get['filter_action'];
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
        
        $data['activities'] = $this->model_logging_user_activity->getActivities($filter_data);
        $data['total'] = $this->model_logging_user_activity->getTotalActivities($filter_data);
        
        // المستخدمين النشطين حالياً
        $data['online_users'] = $this->model_logging_user_activity->getOnlineUsers();
        
        // إحصائيات النشاط
        $data['activity_stats'] = array(
            'total_users_today' => $this->model_logging_user_activity->getTodayActiveUsers(),
            'total_actions_today' => $this->model_logging_user_activity->getTodayActions(),
            'peak_concurrent_users' => $this->model_logging_user_activity->getPeakConcurrentUsers(),
            'average_session_duration' => $this->model_logging_user_activity->getAverageSessionDuration(),
            'most_active_user' => $this->model_logging_user_activity->getMostActiveUser(),
            'most_used_module' => $this->model_logging_user_activity->getMostUsedModule()
        );
        
        // أنواع الأنشطة
        $data['activity_types'] = array(
            'login' => array(
                'name' => $this->language->get('text_activity_login'),
                'icon' => 'fa-sign-in',
                'color' => 'success'
            ),
            'logout' => array(
                'name' => $this->language->get('text_activity_logout'),
                'icon' => 'fa-sign-out',
                'color' => 'info'
            ),
            'create' => array(
                'name' => $this->language->get('text_activity_create'),
                'icon' => 'fa-plus',
                'color' => 'primary'
            ),
            'update' => array(
                'name' => $this->language->get('text_activity_update'),
                'icon' => 'fa-edit',
                'color' => 'warning'
            ),
            'delete' => array(
                'name' => $this->language->get('text_activity_delete'),
                'icon' => 'fa-trash',
                'color' => 'danger'
            ),
            'view' => array(
                'name' => $this->language->get('text_activity_view'),
                'icon' => 'fa-eye',
                'color' => 'info'
            ),
            'export' => array(
                'name' => $this->language->get('text_activity_export'),
                'icon' => 'fa-download',
                'color' => 'secondary'
            ),
            'import' => array(
                'name' => $this->language->get('text_activity_import'),
                'icon' => 'fa-upload',
                'color' => 'secondary'
            )
        );
        
        // أنشطة خاصة بـ catalog/inventory
        $data['specialized_activities'] = array(
            'catalog_activities' => $this->model_logging_user_activity->getCatalogActivities(10),
            'inventory_activities' => $this->model_logging_user_activity->getInventoryActivities(10),
            'workflow_activities' => $this->model_logging_user_activity->getWorkflowActivities(10),
            'approval_activities' => $this->model_logging_user_activity->getApprovalActivities(10)
        );
        
        // تحليل أنماط الاستخدام
        $data['usage_patterns'] = array(
            'hourly_distribution' => $this->model_logging_user_activity->getHourlyDistribution(),
            'daily_distribution' => $this->model_logging_user_activity->getDailyDistribution(),
            'module_usage' => $this->model_logging_user_activity->getModuleUsage(),
            'user_productivity' => $this->model_logging_user_activity->getUserProductivity()
        );
        
        // تحليل الأمان
        $data['security_analysis'] = array(
            'failed_logins' => $this->model_logging_user_activity->getFailedLogins(24), // آخر 24 ساعة
            'suspicious_activities' => $this->model_logging_user_activity->getSuspiciousActivities(),
            'multiple_sessions' => $this->model_logging_user_activity->getMultipleSessions(),
            'unusual_access_times' => $this->model_logging_user_activity->getUnusualAccessTimes()
        );
        
        // قائمة المستخدمين للفلترة
        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();
        
        // قائمة الوحدات
        $data['modules'] = array(
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
            'communication' => $this->language->get('text_module_communication'),
            'notification' => $this->language->get('text_module_notification'),
            'logging' => $this->language->get('text_module_logging')
        );
        
        // الروابط
        $data['export'] = $this->url->link('logging/user_activity/export', 'user_token=' . $this->session->data['user_token'], true);
        $data['real_time'] = $this->url->link('logging/user_activity/realtime', 'user_token=' . $this->session->data['user_token'], true);
        $data['reports'] = $this->url->link('logging/user_activity/reports', 'user_token=' . $this->session->data['user_token'], true);
        $data['settings'] = $this->url->link('logging/user_activity/settings', 'user_token=' . $this->session->data['user_token'], true);
        
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
        
        $this->response->setOutput($this->load->view('logging/user_activity', $data));
    }
    
    /**
     * عرض تفاصيل نشاط مستخدم محدد
     */
    public function user_detail() {
        $this->load->language('logging/user_activity');
        
        if (isset($this->request->get['user_id'])) {
            $user_id = (int)$this->request->get['user_id'];
        } else {
            $user_id = 0;
        }
        
        $this->load->model('logging/user_activity');
        $this->load->model('user/user');
        
        $user_info = $this->model_user_user->getUser($user_id);
        
        if ($user_info) {
            $this->document->setTitle($this->language->get('text_user_activity_detail') . ': ' . $user_info['username']);
            
            $data['breadcrumbs'] = array();
            
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('logging/user_activity', 'user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['breadcrumbs'][] = array(
                'text' => $user_info['username'],
                'href' => $this->url->link('logging/user_activity/user_detail', 'user_id=' . $user_id . '&user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['user_info'] = $user_info;
            
            // نشاط المستخدم التفصيلي
            $data['user_activities'] = $this->model_logging_user_activity->getUserActivities($user_id, 50);
            
            // إحصائيات المستخدم
            $data['user_stats'] = array(
                'total_sessions' => $this->model_logging_user_activity->getUserTotalSessions($user_id),
                'total_actions' => $this->model_logging_user_activity->getUserTotalActions($user_id),
                'last_login' => $this->model_logging_user_activity->getUserLastLogin($user_id),
                'average_session_duration' => $this->model_logging_user_activity->getUserAverageSessionDuration($user_id),
                'most_used_module' => $this->model_logging_user_activity->getUserMostUsedModule($user_id),
                'productivity_score' => $this->model_logging_user_activity->getUserProductivityScore($user_id)
            );
            
            // نشاط المستخدم في catalog/inventory
            $data['specialized_user_activity'] = array(
                'catalog_actions' => $this->model_logging_user_activity->getUserCatalogActions($user_id),
                'inventory_actions' => $this->model_logging_user_activity->getUserInventoryActions($user_id),
                'approval_actions' => $this->model_logging_user_activity->getUserApprovalActions($user_id),
                'workflow_actions' => $this->model_logging_user_activity->getUserWorkflowActions($user_id)
            );
            
            // تحليل أنماط المستخدم
            $data['user_patterns'] = array(
                'login_times' => $this->model_logging_user_activity->getUserLoginTimes($user_id),
                'module_preferences' => $this->model_logging_user_activity->getUserModulePreferences($user_id),
                'activity_trends' => $this->model_logging_user_activity->getUserActivityTrends($user_id),
                'peak_hours' => $this->model_logging_user_activity->getUserPeakHours($user_id)
            );
            
            // الروابط
            $data['back'] = $this->url->link('logging/user_activity', 'user_token=' . $this->session->data['user_token'], true);
            $data['export_user'] = $this->url->link('logging/user_activity/export_user', 'user_id=' . $user_id . '&user_token=' . $this->session->data['user_token'], true);
            
            // التوكن
            $data['user_token'] = $this->session->data['user_token'];
            
            // عرض الصفحة
            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');
            
            $this->response->setOutput($this->load->view('logging/user_activity_detail', $data));
        } else {
            $this->response->redirect($this->url->link('logging/user_activity', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    /**
     * مراقبة النشاط في الوقت الفعلي
     */
    public function realtime() {
        $this->load->language('logging/user_activity');
        
        $this->document->setTitle($this->language->get('text_realtime_activity'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'logging/user_activity')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('logging/user_activity', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_realtime_activity'),
            'href' => $this->url->link('logging/user_activity/realtime', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // إعدادات المراقبة المباشرة
        $data['realtime_config'] = array(
            'refresh_interval' => 3000, // 3 ثوان
            'max_activities_display' => 50,
            'auto_scroll' => true,
            'sound_alerts' => true,
            'filter_critical_actions' => true
        );
        
        // WebSocket configuration for real-time activity
        $data['websocket_config'] = array(
            'enabled' => true,
            'server' => 'ws://localhost:8082',
            'channel' => 'user_activity',
            'user_token' => $this->session->data['user_token']
        );
        
        // الروابط
        $data['get_latest'] = $this->url->link('logging/user_activity/getLatest', 'user_token=' . $this->session->data['user_token'], true);
        $data['back'] = $this->url->link('logging/user_activity', 'user_token=' . $this->session->data['user_token'], true);
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('logging/user_activity_realtime', $data));
    }
    
    /**
     * الحصول على أحدث الأنشطة (AJAX)
     */
    public function getLatest() {
        $json = array();
        
        if (!$this->user->hasPermission('access', 'logging/user_activity')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('logging/user_activity');
            
            $last_activity_id = isset($this->request->get['last_activity_id']) ? (int)$this->request->get['last_activity_id'] : 0;
            $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 20;
            
            $latest_activities = $this->model_logging_user_activity->getLatestActivities($last_activity_id, $limit);
            
            $json['success'] = true;
            $json['activities'] = $latest_activities;
            $json['count'] = count($latest_activities);
            $json['last_activity_id'] = !empty($latest_activities) ? $latest_activities[0]['activity_id'] : $last_activity_id;
            
            // إحصائيات مباشرة
            $json['live_stats'] = array(
                'online_users' => $this->model_logging_user_activity->getOnlineUsersCount(),
                'actions_last_minute' => $this->model_logging_user_activity->getActionsLastMinute(),
                'peak_concurrent' => $this->model_logging_user_activity->getCurrentConcurrentUsers()
            );
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * تسجيل نشاط المستخدم (يتم استدعاؤها من النظام)
     */
    public function logActivity($activity_data) {
        $this->load->model('logging/user_activity');
        
        $log_data = array(
            'user_id' => $this->user->getId(),
            'action_type' => $activity_data['action_type'],
            'module' => $activity_data['module'],
            'controller' => $activity_data['controller'] ?? '',
            'method' => $activity_data['method'] ?? '',
            'description' => $activity_data['description'] ?? '',
            'reference_type' => $activity_data['reference_type'] ?? '',
            'reference_id' => $activity_data['reference_id'] ?? 0,
            'ip_address' => $this->request->server['REMOTE_ADDR'],
            'user_agent' => $this->request->server['HTTP_USER_AGENT'],
            'session_id' => session_id(),
            'created_at' => date('Y-m-d H:i:s')
        );
        
        return $this->model_logging_user_activity->addActivity($log_data);
    }
    
    /**
     * تصدير نشاط المستخدمين
     */
    public function export() {
        $this->load->language('logging/user_activity');
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'logging/user_activity')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->load->model('logging/user_activity');
        
        $filter_data = array();
        
        // تطبيق الفلاتر من الطلب
        if (isset($this->request->get['filter_user'])) {
            $filter_data['filter_user'] = $this->request->get['filter_user'];
        }
        
        if (isset($this->request->get['filter_action'])) {
            $filter_data['filter_action'] = $this->request->get['filter_action'];
        }
        
        if (isset($this->request->get['filter_date_from'])) {
            $filter_data['filter_date_from'] = $this->request->get['filter_date_from'];
        }
        
        if (isset($this->request->get['filter_date_to'])) {
            $filter_data['filter_date_to'] = $this->request->get['filter_date_to'];
        }
        
        $activities = $this->model_logging_user_activity->getActivities($filter_data);
        
        $filename = 'user_activity_' . date('Y-m-d_H-i-s') . '.csv';
        
        $this->response->addheader('Pragma: public');
        $this->response->addheader('Expires: 0');
        $this->response->addheader('Content-Description: File Transfer');
        $this->response->addheader('Content-Type: application/csv');
        $this->response->addheader('Content-Disposition: attachment; filename="' . $filename . '"');
        $this->response->addheader('Content-Transfer-Encoding: binary');
        
        $output = '';
        
        // رأس الملف
        $output .= 'التاريخ,المستخدم,النشاط,الوحدة,الوصف,IP' . "\n";
        
        // البيانات
        foreach ($activities as $activity) {
            $output .= '"' . $activity['created_at'] . '",';
            $output .= '"' . $activity['username'] . '",';
            $output .= '"' . $activity['action_type'] . '",';
            $output .= '"' . $activity['module'] . '",';
            $output .= '"' . str_replace('"', '""', $activity['description']) . '",';
            $output .= '"' . $activity['ip_address'] . '"' . "\n";
        }
        
        $this->response->setOutput($output);
    }
}
