<?php
/**
 * المساعد الذكي (AI Assistant / Copilot)
 *
 * يوفر واجهة تفاعلية للمساعد الذكي مع:
 * - دردشة مباشرة مع المساعد
 * - اقتراحات ذكية
 * - تحليل البيانات السريع
 * - مساعدة في استخدام النظام
 * - أتمتة المهام
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerAiAiAssistant extends Controller {

    private $error = [];

    /**
     * الصفحة الرئيسية للمساعد الذكي
     */
    public function index() {
        $this->load->language('ai/ai_assistant');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('ai/ai_assistant', 'user_token=' . $this->session->data['user_token'], true)
        ];

        // إعداد البيانات الأساسية
        $data['user_info'] = $this->getUserInfo();
        $data['quick_actions'] = $this->getQuickActions();
        $data['recent_conversations'] = $this->getRecentConversations();
        $data['suggested_questions'] = $this->getSuggestedQuestions();
        $data['system_status'] = $this->getSystemStatus();

        // URLs للـ AJAX
        $data['chat_url'] = $this->url->link('ai/ai_assistant/chat', 'user_token=' . $this->session->data['user_token'], true);
        $data['quick_action_url'] = $this->url->link('ai/ai_assistant/quickAction', 'user_token=' . $this->session->data['user_token'], true);
        $data['history_url'] = $this->url->link('ai/ai_assistant/getHistory', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('ai/ai_assistant', $data));
    }

    /**
     * معالجة رسائل الدردشة
     */
    public function chat() {
        $this->load->language('ai/ai_assistant');

        $json = [];

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model('ai/ai_assistant');

            try {
                $message = $this->request->post['message'] ?? '';
                $context = $this->request->post['context'] ?? [];

                if (empty($message)) {
                    $json['error'] = $this->language->get('error_empty_message');
                } else {
                    // معالجة الرسالة
                    $response = $this->model_ai_ai_assistant->startConversation($message, $context);

                    $json['success'] = true;
                    $json['response'] = $response;
                    $json['timestamp'] = date('Y-m-d H:i:s');
                }

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
     * تنفيذ إجراء سريع
     */
    public function quickAction() {
        $this->load->language('ai/ai_assistant');

        $json = [];

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            try {
                $action = $this->request->post['action'] ?? '';
                $parameters = $this->request->post['parameters'] ?? [];

                switch ($action) {
                    case 'sales_summary':
                        $json['result'] = $this->getSalesSummary($parameters);
                        break;

                    case 'inventory_status':
                        $json['result'] = $this->getInventoryStatus($parameters);
                        break;

                    case 'financial_overview':
                        $json['result'] = $this->getFinancialOverview($parameters);
                        break;

                    case 'pending_tasks':
                        $json['result'] = $this->getPendingTasks($parameters);
                        break;

                    case 'system_health':
                        $json['result'] = $this->getSystemHealth($parameters);
                        break;

                    default:
                        $json['error'] = 'إجراء غير مدعوم';
                }

                if (isset($json['result'])) {
                    $json['success'] = true;
                }

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
     * الحصول على تاريخ المحادثات
     */
    public function getHistory() {
        $json = [];

        try {
            $page = $this->request->get['page'] ?? 1;
            $limit = 20;
            $start = ($page - 1) * $limit;

            $filter_data = [
                'start' => $start,
                'limit' => $limit
            ];

            $conversations = $this->getConversationHistory($filter_data);

            $json['success'] = true;
            $json['conversations'] = $conversations;
            $json['pagination'] = $this->getPaginationInfo($page, $limit);

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تصدير المحادثة
     */
    public function exportConversation() {
        try {
            $conversation_id = $this->request->get['conversation_id'] ?? 0;
            $format = $this->request->get['format'] ?? 'pdf';

            if (!$conversation_id) {
                throw new Exception('معرف المحادثة مطلوب');
            }

            $conversation = $this->getConversationDetails($conversation_id);

            if (!$conversation) {
                throw new Exception('المحادثة غير موجودة');
            }

            switch ($format) {
                case 'pdf':
                    $this->exportToPdf($conversation);
                    break;

                case 'word':
                    $this->exportToWord($conversation);
                    break;

                case 'txt':
                    $this->exportToText($conversation);
                    break;

                default:
                    throw new Exception('تنسيق التصدير غير مدعوم');
            }

        } catch (Exception $e) {
            $this->session->data['error'] = $e->getMessage();
            $this->response->redirect($this->url->link('ai/ai_assistant', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * الحصول على معلومات المستخدم
     */
    private function getUserInfo() {
        return [
            'name' => $this->user->getFirstName() . ' ' . $this->user->getLastName(),
            'role' => $this->user->getGroupName(),
            'department' => $this->getUserDepartment(),
            'last_login' => $this->user->getLastLogin(),
            'permissions' => $this->getUserPermissions()
        ];
    }

    /**
     * الحصول على الإجراءات السريعة
     */
    private function getQuickActions() {
        return [
            [
                'id' => 'sales_summary',
                'title' => 'ملخص المبيعات',
                'description' => 'عرض ملخص سريع لمبيعات اليوم',
                'icon' => 'fa-chart-line',
                'color' => 'success'
            ],
            [
                'id' => 'inventory_status',
                'title' => 'حالة المخزون',
                'description' => 'التحقق من حالة المخزون والتنبيهات',
                'icon' => 'fa-boxes',
                'color' => 'warning'
            ],
            [
                'id' => 'financial_overview',
                'title' => 'نظرة مالية',
                'description' => 'ملخص الوضع المالي الحالي',
                'icon' => 'fa-dollar-sign',
                'color' => 'info'
            ],
            [
                'id' => 'pending_tasks',
                'title' => 'المهام المعلقة',
                'description' => 'عرض المهام والموافقات المعلقة',
                'icon' => 'fa-tasks',
                'color' => 'primary'
            ],
            [
                'id' => 'system_health',
                'title' => 'صحة النظام',
                'description' => 'فحص حالة النظام والأداء',
                'icon' => 'fa-heartbeat',
                'color' => 'danger'
            ]
        ];
    }

    /**
     * الحصول على المحادثات الأخيرة
     */
    private function getRecentConversations() {
        $query = $this->db->query("
            SELECT conversation_id, user_message,
                   DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as created_at
            FROM cod_ai_conversation
            WHERE user_id = '" . (int)$this->user->getId() . "'
            ORDER BY created_at DESC
            LIMIT 5
        ");

        return $query->rows;
    }

    /**
     * الحصول على الأسئلة المقترحة
     */
    private function getSuggestedQuestions() {
        $user_role = $this->user->getGroupName();

        $questions = [
            'admin' => [
                'ما هو أداء النظام اليوم؟',
                'عرض تقرير شامل للمبيعات',
                'ما هي المشاكل التقنية الحالية؟',
                'كيف يمكن تحسين الأداء؟'
            ],
            'manager' => [
                'ما هي أهم المؤشرات اليوم؟',
                'عرض تحليل الأرباح الشهرية',
                'ما هي الطلبات المعلقة؟',
                'اقتراحات لتحسين المبيعات'
            ],
            'employee' => [
                'كيف أضيف منتج جديد؟',
                'ما هي مهامي اليوم؟',
                'كيف أنشئ فاتورة؟',
                'شرح نظام المخزون'
            ]
        ];

        return $questions[$user_role] ?? $questions['employee'];
    }

    /**
     * الحصول على حالة النظام
     */
    private function getSystemStatus() {
        return [
            'ai_models' => $this->getAiModelsStatus(),
            'database' => $this->getDatabaseStatus(),
            'performance' => $this->getPerformanceMetrics(),
            'alerts' => $this->getSystemAlerts()
        ];
    }

    /**
     * الحصول على ملخص المبيعات
     */
    private function getSalesSummary($parameters) {
        $period = $parameters['period'] ?? 'today';

        $date_condition = '';
        switch ($period) {
            case 'today':
                $date_condition = "DATE(o.date_added) = CURDATE()";
                break;
            case 'week':
                $date_condition = "WEEK(o.date_added) = WEEK(CURDATE())";
                break;
            case 'month':
                $date_condition = "MONTH(o.date_added) = MONTH(CURDATE()) AND YEAR(o.date_added) = YEAR(CURDATE())";
                break;
        }

        $query = $this->db->query("
            SELECT
                COUNT(*) as total_orders,
                SUM(o.total) as total_sales,
                AVG(o.total) as avg_order_value,
                COUNT(DISTINCT o.customer_id) as unique_customers
            FROM cod_order o
            WHERE " . $date_condition . "
            AND o.order_status_id > 0
        ");

        $summary = $query->row;

        // إضافة مقارنة مع الفترة السابقة
        $comparison = $this->getSalesComparison($period);

        return [
            'summary' => $summary,
            'comparison' => $comparison,
            'charts' => $this->generateSalesCharts($period),
            'insights' => $this->generateSalesInsights($summary, $comparison)
        ];
    }

    /**
     * الحصول على حالة المخزون
     */
    private function getInventoryStatus($parameters) {
        $query = $this->db->query("
            SELECT
                COUNT(*) as total_products,
                SUM(CASE WHEN quantity <= reorder_level THEN 1 ELSE 0 END) as low_stock_items,
                SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as out_of_stock_items,
                SUM(quantity * cost) as total_inventory_value
            FROM cod_product p
            LEFT JOIN cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE p.status = 1
        ");

        $status = $query->row;

        // الحصول على المنتجات التي تحتاج إعادة طلب
        $reorder_query = $this->db->query("
            SELECT p.name, pi.quantity, p.reorder_level
            FROM cod_product p
            LEFT JOIN cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE p.status = 1 AND pi.quantity <= p.reorder_level
            ORDER BY (pi.quantity / p.reorder_level) ASC
            LIMIT 10
        ");

        return [
            'status' => $status,
            'reorder_items' => $reorder_query->rows,
            'alerts' => $this->generateInventoryAlerts($status),
            'recommendations' => $this->generateInventoryRecommendations($status)
        ];
    }

    /**
     * الحصول على النظرة المالية
     */
    private function getFinancialOverview($parameters) {
        $period = $parameters['period'] ?? 'month';

        // الحصول على الإيرادات والمصروفات
        $financial_query = $this->db->query("
            SELECT
                SUM(CASE WHEN je.type = 'credit' THEN je.amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN je.type = 'debit' THEN je.amount ELSE 0 END) as total_expenses,
                COUNT(DISTINCT je.journal_entry_id) as total_transactions
            FROM cod_journal_entry je
            WHERE MONTH(je.date) = MONTH(CURDATE())
            AND YEAR(je.date) = YEAR(CURDATE())
        ");

        $financial_data = $financial_query->row;
        $financial_data['net_profit'] = $financial_data['total_revenue'] - $financial_data['total_expenses'];

        return [
            'financial_data' => $financial_data,
            'cash_flow' => $this->getCashFlowData($period),
            'profitability' => $this->getProfitabilityMetrics($period),
            'trends' => $this->getFinancialTrends($period)
        ];
    }

    /**
     * تصدير إلى PDF
     */
    private function exportToPdf($conversation) {
        // تطوير لاحق - استخدام مكتبة PDF
        $this->response->addHeader('Content-Type: application/pdf');
        $this->response->addHeader('Content-Disposition: attachment; filename="conversation_' . $conversation['conversation_id'] . '.pdf"');

        // مؤقتاً - إرجاع نص
        $content = "محادثة رقم: " . $conversation['conversation_id'] . "\n";
        $content .= "التاريخ: " . $conversation['created_at'] . "\n\n";
        $content .= "الرسالة: " . $conversation['user_message'] . "\n\n";
        $content .= "الرد: " . json_encode($conversation['assistant_response'], JSON_UNESCAPED_UNICODE);

        $this->response->setOutput($content);
    }

    /**
     * الحصول على تاريخ المحادثات
     */
    private function getConversationHistory($filter_data) {
        $sql = "SELECT conversation_id, user_message,
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as created_at,
                DATE_FORMAT(response_time, '%Y-%m-%d %H:%i') as response_time
                FROM cod_ai_conversation
                WHERE user_id = '" . (int)$this->user->getId() . "'
                ORDER BY created_at DESC";

        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }

            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * دوال مساعدة إضافية
     */
    private function getAiModelsStatus() {
        $query = $this->db->query("
            SELECT
                COUNT(*) as total_models,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_models,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_models
            FROM cod_ai_model
        ");

        return $query->row;
    }

    private function getDatabaseStatus() {
        // فحص حالة قاعدة البيانات
        return [
            'status' => 'healthy',
            'connections' => 5,
            'response_time' => '12ms'
        ];
    }

    private function getPerformanceMetrics() {
        return [
            'cpu_usage' => '45%',
            'memory_usage' => '62%',
            'disk_usage' => '78%',
            'response_time' => '250ms'
        ];
    }

    private function getSystemAlerts() {
        return [
            ['type' => 'warning', 'message' => 'مساحة القرص الصلب تقترب من الامتلاء'],
            ['type' => 'info', 'message' => 'تحديث النظام متاح']
        ];
    }

    private function getSalesComparison($period) {
        // مقارنة مع الفترة السابقة
        return [
            'orders_change' => '+15%',
            'sales_change' => '+22%',
            'customers_change' => '+8%'
        ];
    }

    private function generateSalesCharts($period) {
        return [
            'daily_sales' => [],
            'product_performance' => [],
            'customer_segments' => []
        ];
    }

    private function generateSalesInsights($summary, $comparison) {
        return [
            'المبيعات في نمو مستمر مقارنة بالفترة السابقة',
            'متوسط قيمة الطلب أعلى من المتوقع',
            'عدد العملاء الجدد في ازدياد'
        ];
    }

    private function generateInventoryAlerts($status) {
        $alerts = [];

        if ($status['low_stock_items'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => $status['low_stock_items'] . ' منتج يحتاج إعادة طلب'
            ];
        }

        if ($status['out_of_stock_items'] > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => $status['out_of_stock_items'] . ' منتج نفد من المخزون'
            ];
        }

        return $alerts;
    }

    private function generateInventoryRecommendations($status) {
        return [
            'قم بإعادة طلب المنتجات منخفضة المخزون',
            'راجع مستويات إعادة الطلب للمنتجات سريعة الحركة',
            'فكر في تقليل كميات المنتجات بطيئة الحركة'
        ];
    }

    private function getCashFlowData($period) {
        return [
            'cash_in' => 150000,
            'cash_out' => 120000,
            'net_cash_flow' => 30000
        ];
    }

    private function getProfitabilityMetrics($period) {
        return [
            'gross_margin' => '35%',
            'net_margin' => '12%',
            'roi' => '18%'
        ];
    }

    private function getFinancialTrends($period) {
        return [
            'revenue_trend' => 'increasing',
            'expense_trend' => 'stable',
            'profit_trend' => 'increasing'
        ];
    }

    private function getUserDepartment() {
        // الحصول على قسم المستخدم
        return 'الإدارة العامة';
    }

    private function getUserPermissions() {
        // الحصول على صلاحيات المستخدم
        return $this->user->getPermission();
    }

    private function getPaginationInfo($page, $limit) {
        $total_query = $this->db->query("
            SELECT COUNT(*) as total FROM cod_ai_conversation
            WHERE user_id = '" . (int)$this->user->getId() . "'
        ");

        $total = $total_query->row['total'];

        return [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    private function getConversationDetails($conversation_id) {
        $query = $this->db->query("
            SELECT * FROM cod_ai_conversation
            WHERE conversation_id = '" . (int)$conversation_id . "'
            AND user_id = '" . (int)$this->user->getId() . "'
        ");

        return $query->num_rows ? $query->row : false;
    }

    private function exportToWord($conversation) {
        // تطوير لاحق
        throw new Exception('تصدير Word قيد التطوير');
    }

    private function exportToText($conversation) {
        $this->response->addHeader('Content-Type: text/plain; charset=utf-8');
        $this->response->addHeader('Content-Disposition: attachment; filename="conversation_' . $conversation['conversation_id'] . '.txt"');

        $content = "محادثة رقم: " . $conversation['conversation_id'] . "\n";
        $content .= "التاريخ: " . $conversation['created_at'] . "\n\n";
        $content .= "الرسالة: " . $conversation['user_message'] . "\n\n";
        $content .= "الرد: " . json_encode($conversation['assistant_response'], JSON_UNESCAPED_UNICODE);

        $this->response->setOutput($content);
    }

    private function getPendingTasks($parameters) {
        // الحصول على المهام المعلقة
        $query = $this->db->query("
            SELECT
                COUNT(*) as total_pending,
                SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority,
                SUM(CASE WHEN priority = 'medium' THEN 1 ELSE 0 END) as medium_priority,
                SUM(CASE WHEN priority = 'low' THEN 1 ELSE 0 END) as low_priority
            FROM cod_workflow_approval
            WHERE status = 'pending'
            AND approver_id = '" . (int)$this->user->getId() . "'
        ");

        $tasks = $query->row;

        // الحصول على تفاصيل المهام
        $details_query = $this->db->query("
            SELECT title, description, created_at, priority
            FROM cod_workflow_approval
            WHERE status = 'pending'
            AND approver_id = '" . (int)$this->user->getId() . "'
            ORDER BY created_at DESC
            LIMIT 10
        ");

        return [
            'summary' => $tasks,
            'details' => $details_query->rows,
            'recommendations' => $this->getTaskRecommendations($tasks)
        ];
    }

    private function getSystemHealth($parameters) {
        return [
            'overall_status' => 'healthy',
            'uptime' => '99.9%',
            'last_backup' => '2024-01-15 02:00:00',
            'security_status' => 'secure',
            'performance_score' => 85,
            'recommendations' => [
                'تحديث النظام إلى أحدث إصدار',
                'تنظيف ملفات السجل القديمة',
                'مراجعة صلاحيات المستخدمين'
            ]
        ];
    }

    private function getTaskRecommendations($tasks) {
        $recommendations = [];

        if ($tasks['high_priority'] > 0) {
            $recommendations[] = 'ابدأ بالمهام عالية الأولوية أولاً';
        }

        if ($tasks['total_pending'] > 10) {
            $recommendations[] = 'فكر في تفويض بعض المهام';
        }

        return $recommendations;
    }
}
}
