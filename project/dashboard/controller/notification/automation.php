<?php
/**
 * أتمتة نظام الإشعارات المتقدم
 * Notification Automation Controller
 * 
 * نظام أتمتة الإشعارات مع تكامل مع catalog/inventory وAI
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

class ControllerNotificationAutomation extends Controller {
    
    /**
     * @var array خطأ في النظام
     */
    private $error = array();
    
    /**
     * عرض صفحة أتمتة الإشعارات
     */
    public function index() {
        $this->load->language('notification/automation');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'notification/automation')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('notification/automation', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // تحميل قواعد الأتمتة
        $this->load->model('notification/automation');
        
        $filter_data = array(
            'start' => 0,
            'limit' => 20
        );
        
        $data['automation_rules'] = $this->model_notification_automation->getAutomationRules($filter_data);
        $data['total'] = $this->model_notification_automation->getTotalAutomationRules($filter_data);
        
        // قواعد الأتمتة المتخصصة للـ catalog/inventory
        $data['specialized_automations'] = array(
            'catalog' => array(
                'low_stock_alert' => array(
                    'name' => $this->language->get('text_low_stock_automation'),
                    'description' => $this->language->get('text_low_stock_automation_desc'),
                    'trigger' => 'inventory_level_change',
                    'condition' => 'quantity <= minimum_quantity',
                    'action' => 'send_notification',
                    'enabled' => true
                ),
                'price_change_alert' => array(
                    'name' => $this->language->get('text_price_change_automation'),
                    'description' => $this->language->get('text_price_change_automation_desc'),
                    'trigger' => 'product_price_update',
                    'condition' => 'price_change_percentage > 10',
                    'action' => 'send_notification',
                    'enabled' => true
                ),
                'new_product_alert' => array(
                    'name' => $this->language->get('text_new_product_automation'),
                    'description' => $this->language->get('text_new_product_automation_desc'),
                    'trigger' => 'product_created',
                    'condition' => 'status = active',
                    'action' => 'send_notification',
                    'enabled' => true
                ),
                'expiry_alert' => array(
                    'name' => $this->language->get('text_expiry_automation'),
                    'description' => $this->language->get('text_expiry_automation_desc'),
                    'trigger' => 'daily_check',
                    'condition' => 'expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY)',
                    'action' => 'send_notification',
                    'enabled' => true
                )
            ),
            'inventory' => array(
                'stock_movement_alert' => array(
                    'name' => $this->language->get('text_stock_movement_automation'),
                    'description' => $this->language->get('text_stock_movement_automation_desc'),
                    'trigger' => 'stock_movement',
                    'condition' => 'movement_type = adjustment AND quantity > 100',
                    'action' => 'send_notification',
                    'enabled' => false
                ),
                'reorder_point_alert' => array(
                    'name' => $this->language->get('text_reorder_point_automation'),
                    'description' => $this->language->get('text_reorder_point_automation_desc'),
                    'trigger' => 'inventory_level_change',
                    'condition' => 'quantity <= reorder_point',
                    'action' => 'create_purchase_requisition',
                    'enabled' => true
                ),
                'batch_expiry_alert' => array(
                    'name' => $this->language->get('text_batch_expiry_automation'),
                    'description' => $this->language->get('text_batch_expiry_automation_desc'),
                    'trigger' => 'daily_check',
                    'condition' => 'batch_expiry_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)',
                    'action' => 'send_urgent_notification',
                    'enabled' => true
                )
            ),
            'ai_powered' => array(
                'demand_forecast_alert' => array(
                    'name' => $this->language->get('text_ai_demand_forecast_automation'),
                    'description' => $this->language->get('text_ai_demand_forecast_automation_desc'),
                    'trigger' => 'ai_analysis_complete',
                    'condition' => 'predicted_stockout_risk > 80%',
                    'action' => 'send_ai_recommendation',
                    'enabled' => true
                ),
                'pricing_optimization_alert' => array(
                    'name' => $this->language->get('text_ai_pricing_automation'),
                    'description' => $this->language->get('text_ai_pricing_automation_desc'),
                    'trigger' => 'market_analysis_complete',
                    'condition' => 'pricing_opportunity_score > 75',
                    'action' => 'send_pricing_recommendation',
                    'enabled' => true
                )
            )
        );
        
        // إحصائيات الأتمتة
        $data['automation_stats'] = array(
            'total_rules' => $this->model_notification_automation->getTotalRules(),
            'active_rules' => $this->model_notification_automation->getActiveRules(),
            'triggered_today' => $this->model_notification_automation->getTriggeredToday(),
            'success_rate' => $this->model_notification_automation->getSuccessRate()
        );
        
        // أنواع المحفزات المتاحة
        $data['trigger_types'] = array(
            'inventory_level_change' => $this->language->get('text_trigger_inventory_level'),
            'product_created' => $this->language->get('text_trigger_product_created'),
            'product_updated' => $this->language->get('text_trigger_product_updated'),
            'price_changed' => $this->language->get('text_trigger_price_changed'),
            'stock_movement' => $this->language->get('text_trigger_stock_movement'),
            'purchase_order_created' => $this->language->get('text_trigger_purchase_order'),
            'sales_order_created' => $this->language->get('text_trigger_sales_order'),
            'daily_check' => $this->language->get('text_trigger_daily_check'),
            'weekly_check' => $this->language->get('text_trigger_weekly_check'),
            'ai_analysis_complete' => $this->language->get('text_trigger_ai_analysis')
        );
        
        // أنواع الإجراءات المتاحة
        $data['action_types'] = array(
            'send_notification' => $this->language->get('text_action_send_notification'),
            'send_email' => $this->language->get('text_action_send_email'),
            'send_sms' => $this->language->get('text_action_send_sms'),
            'create_task' => $this->language->get('text_action_create_task'),
            'create_purchase_requisition' => $this->language->get('text_action_create_requisition'),
            'update_product_status' => $this->language->get('text_action_update_product'),
            'trigger_workflow' => $this->language->get('text_action_trigger_workflow'),
            'send_ai_recommendation' => $this->language->get('text_action_ai_recommendation')
        );
        
        // الروابط
        $data['add'] = $this->url->link('notification/automation/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['test'] = $this->url->link('notification/automation/test', 'user_token=' . $this->session->data['user_token'], true);
        $data['logs'] = $this->url->link('notification/automation/logs', 'user_token=' . $this->session->data['user_token'], true);
        
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
        
        $this->response->setOutput($this->load->view('notification/automation', $data));
    }
    
    /**
     * تشغيل قواعد الأتمتة (يتم استدعاؤها من cron job)
     */
    public function execute() {
        $this->load->language('notification/automation');
        $this->load->model('notification/automation');
        
        // التحقق من التوكن الأمني للـ cron
        if (!isset($this->request->get['cron_token']) || $this->request->get['cron_token'] !== $this->config->get('config_cron_token')) {
            $this->response->setOutput('Unauthorized');
            return;
        }
        
        $executed_rules = 0;
        $successful_executions = 0;
        
        // تشغيل قواعد الفحص اليومي
        $daily_rules = $this->model_notification_automation->getDailyRules();
        
        foreach ($daily_rules as $rule) {
            $executed_rules++;
            
            try {
                $result = $this->executeRule($rule);
                if ($result) {
                    $successful_executions++;
                }
            } catch (Exception $e) {
                // تسجيل الخطأ
                $this->log->write('Automation Error: ' . $e->getMessage());
            }
        }
        
        // تشغيل قواعد الفحص المستمر (للمخزون والكتالوج)
        $this->executeCatalogInventoryRules();
        
        $this->response->setOutput("Executed: $executed_rules, Successful: $successful_executions");
    }
    
    /**
     * تشغيل قواعد catalog/inventory المستمرة
     */
    private function executeCatalogInventoryRules() {
        $this->load->model('catalog/product');
        $this->load->model('inventory/stock');
        
        // فحص المخزون المنخفض
        $low_stock_products = $this->model_inventory_stock->getLowStockProducts();
        
        foreach ($low_stock_products as $product) {
            $this->triggerAutomation('low_stock_alert', array(
                'product_id' => $product['product_id'],
                'product_name' => $product['name'],
                'current_quantity' => $product['quantity'],
                'minimum_quantity' => $product['minimum']
            ));
        }
        
        // فحص المنتجات منتهية الصلاحية
        $expiring_products = $this->model_catalog_product->getExpiringProducts(30);
        
        foreach ($expiring_products as $product) {
            $this->triggerAutomation('expiry_alert', array(
                'product_id' => $product['product_id'],
                'product_name' => $product['name'],
                'expiry_date' => $product['expiry_date'],
                'days_remaining' => $product['days_remaining']
            ));
        }
        
        // فحص نقاط إعادة الطلب
        $reorder_products = $this->model_inventory_stock->getReorderPointProducts();
        
        foreach ($reorder_products as $product) {
            $this->triggerAutomation('reorder_point_alert', array(
                'product_id' => $product['product_id'],
                'product_name' => $product['name'],
                'current_quantity' => $product['quantity'],
                'reorder_point' => $product['reorder_point']
            ));
        }
    }
    
    /**
     * تشغيل قاعدة أتمتة محددة
     */
    private function executeRule($rule) {
        $this->load->model('notification/automation');
        
        // تقييم الشرط
        if (!$this->evaluateCondition($rule['condition'], $rule['context_data'])) {
            return false;
        }
        
        // تنفيذ الإجراء
        $result = $this->executeAction($rule['action_type'], $rule['action_data'], $rule['context_data']);
        
        // تسجيل النتيجة
        $this->model_notification_automation->logExecution($rule['rule_id'], $result);
        
        return $result;
    }
    
    /**
     * تقييم شرط القاعدة
     */
    private function evaluateCondition($condition, $context_data) {
        // تنفيذ منطق تقييم الشروط
        // يمكن استخدام parser للشروط المعقدة
        
        // مثال بسيط:
        if (strpos($condition, 'quantity <=') !== false) {
            preg_match('/quantity <= (\d+)/', $condition, $matches);
            if (isset($matches[1]) && isset($context_data['current_quantity'])) {
                return $context_data['current_quantity'] <= (int)$matches[1];
            }
        }
        
        return true; // افتراضي
    }
    
    /**
     * تنفيذ إجراء القاعدة
     */
    private function executeAction($action_type, $action_data, $context_data) {
        switch ($action_type) {
            case 'send_notification':
                return $this->sendNotification($action_data, $context_data);
                
            case 'send_email':
                return $this->sendEmail($action_data, $context_data);
                
            case 'create_purchase_requisition':
                return $this->createPurchaseRequisition($action_data, $context_data);
                
            case 'trigger_workflow':
                return $this->triggerWorkflow($action_data, $context_data);
                
            case 'send_ai_recommendation':
                return $this->sendAIRecommendation($action_data, $context_data);
                
            default:
                return false;
        }
    }
    
    /**
     * إرسال إشعار
     */
    private function sendNotification($action_data, $context_data) {
        $this->load->model('notification/center');
        
        $notification_data = array(
            'type' => 'automation_alert',
            'title' => $this->replaceVariables($action_data['title'], $context_data),
            'message' => $this->replaceVariables($action_data['message'], $context_data),
            'priority' => isset($action_data['priority']) ? $action_data['priority'] : 'medium',
            'recipients' => $action_data['recipients']
        );
        
        return $this->model_notification_center->sendBulkNotification($notification_data);
    }
    
    /**
     * إنشاء طلب شراء تلقائي
     */
    private function createPurchaseRequisition($action_data, $context_data) {
        $this->load->model('purchase/requisition');
        
        $requisition_data = array(
            'product_id' => $context_data['product_id'],
            'quantity' => $this->calculateReorderQuantity($context_data),
            'reason' => 'تم إنشاؤه تلقائياً بواسطة نظام الأتمتة',
            'priority' => 'high',
            'requested_by' => 1, // النظام
            'auto_generated' => 1
        );
        
        return $this->model_purchase_requisition->addRequisition($requisition_data);
    }
    
    /**
     * تشغيل workflow
     */
    private function triggerWorkflow($action_data, $context_data) {
        $this->load->model('workflow/automation');
        
        return $this->model_workflow_automation->triggerWorkflow(
            $action_data['workflow_id'],
            $context_data
        );
    }
    
    /**
     * إرسال توصية AI
     */
    private function sendAIRecommendation($action_data, $context_data) {
        $this->load->model('ai/recommendation_engine');
        
        $recommendation = $this->model_ai_recommendation_engine->generateRecommendation(
            $action_data['recommendation_type'],
            $context_data
        );
        
        if ($recommendation) {
            return $this->sendNotification(array(
                'title' => 'توصية ذكية من نظام الـ AI',
                'message' => $recommendation['message'],
                'priority' => 'high',
                'recipients' => $action_data['recipients']
            ), $context_data);
        }
        
        return false;
    }
    
    /**
     * استبدال المتغيرات في النص
     */
    private function replaceVariables($text, $context_data) {
        foreach ($context_data as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }
    
    /**
     * حساب كمية إعادة الطلب
     */
    private function calculateReorderQuantity($context_data) {
        // منطق حساب كمية إعادة الطلب بناءً على البيانات التاريخية
        $base_quantity = isset($context_data['reorder_point']) ? $context_data['reorder_point'] : 100;
        $safety_stock = $base_quantity * 0.2; // 20% مخزون أمان
        
        return $base_quantity + $safety_stock;
    }
    
    /**
     * تشغيل أتمتة محددة
     */
    public function triggerAutomation($automation_type, $context_data) {
        $this->load->model('notification/automation');
        
        $rule = $this->model_notification_automation->getRuleByType($automation_type);
        
        if ($rule && $rule['status'] == 1) {
            $rule['context_data'] = $context_data;
            return $this->executeRule($rule);
        }
        
        return false;
    }
    
    /**
     * اختبار قاعدة أتمتة
     */
    public function test() {
        $this->load->language('notification/automation');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'notification/automation')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['rule_id'])) {
                $this->load->model('notification/automation');
                
                $rule = $this->model_notification_automation->getRule($this->request->post['rule_id']);
                
                if ($rule) {
                    // بيانات اختبار
                    $test_context = array(
                        'product_id' => 1,
                        'product_name' => 'منتج تجريبي',
                        'current_quantity' => 5,
                        'minimum_quantity' => 10
                    );
                    
                    $rule['context_data'] = $test_context;
                    $result = $this->executeRule($rule);
                    
                    if ($result) {
                        $json['success'] = $this->language->get('text_test_successful');
                    } else {
                        $json['error'] = $this->language->get('text_test_failed');
                    }
                } else {
                    $json['error'] = $this->language->get('error_rule_not_found');
                }
            } else {
                $json['error'] = $this->language->get('error_rule_id_required');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
