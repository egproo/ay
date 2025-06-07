<?php
/**
 * نظام أيم ERP: المساعد الذكي
 * يوفر واجهة للتفاعل مع المساعد الذكي وإدارة المحادثات والإعدادات
 */
class ControllerCommonAiAssistant extends Controller {
    private $error = array();
    
    /**
     * عرض المساعد الذكي
     */
    public function index() {
        $this->load->language('common/ai_assistant');
        
        $data['user_token'] = $this->session->data['user_token'];
        
        // تحميل النموذج
        $this->load->model('common/ai_assistant');
        
        // تحميل إعدادات المساعد الذكي
        $data['ai_settings'] = $this->model_common_ai_assistant->getSettings($this->user->getId());
        
        // تعيين متغيرات اللغة
        $data['text_ai_assistant'] = $this->language->get('text_ai_assistant');
        $data['text_clear_conversation'] = $this->language->get('text_clear_conversation');
        $data['text_ai_welcome'] = $this->language->get('text_ai_welcome');
        $data['text_just_now'] = $this->language->get('text_just_now');
        $data['text_no_conversation'] = $this->language->get('text_no_conversation');
        $data['text_suggestions'] = $this->language->get('text_suggestions');
        $data['text_suggestion_sales'] = $this->language->get('text_suggestion_sales');
        $data['text_suggestion_inventory'] = $this->language->get('text_suggestion_inventory');
        $data['text_suggestion_reports'] = $this->language->get('text_suggestion_reports');
        $data['text_ask_ai'] = $this->language->get('text_ask_ai');
        $data['text_ai_settings'] = $this->language->get('text_ai_settings');
        $data['text_ai_model'] = $this->language->get('text_ai_model');
        $data['text_default_model'] = $this->language->get('text_default_model');
        $data['text_advanced_model'] = $this->language->get('text_advanced_model');
        $data['text_model_help'] = $this->language->get('text_model_help');
        $data['text_ai_preferences'] = $this->language->get('text_ai_preferences');
        $data['text_save_conversation'] = $this->language->get('text_save_conversation');
        $data['text_show_suggestions'] = $this->language->get('text_show_suggestions');
        $data['text_auto_complete'] = $this->language->get('text_auto_complete');
        $data['text_ai_data_access'] = $this->language->get('text_ai_data_access');
        $data['text_access_sales'] = $this->language->get('text_access_sales');
        $data['text_access_inventory'] = $this->language->get('text_access_inventory');
        $data['text_access_customers'] = $this->language->get('text_access_customers');
        $data['text_access_reports'] = $this->language->get('text_access_reports');
        $data['text_ai_thinking'] = $this->language->get('text_ai_thinking');
        $data['text_ai_error'] = $this->language->get('text_ai_error');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_save'] = $this->language->get('button_save');
        
        return $this->load->view('common/ai_assistant', $data);
    }
    
    /**
     * معالجة استعلام المساعد الذكي
     */
    public function query() {
        $this->load->language('common/ai_assistant');
        $this->load->model('common/ai_assistant');
        
        $json = array();
        
        if (isset($this->request->post['query'])) {
            $query = trim($this->request->post['query']);
            
            if (!empty($query)) {
                // حفظ استعلام المستخدم في المحادثة
                $this->model_common_ai_assistant->addConversationMessage($this->user->getId(), 'user', $query);
                
                // الحصول على رد المساعد الذكي
                $response = $this->model_common_ai_assistant->processQuery($query, $this->user->getId());
                
                // حفظ رد المساعد الذكي في المحادثة
                $this->model_common_ai_assistant->addConversationMessage($this->user->getId(), 'ai', $response);
                
                $json['success'] = true;
                $json['response'] = $response;
            } else {
                $json['success'] = false;
                $json['error'] = $this->language->get('error_empty_query');
            }
        } else {
            $json['success'] = false;
            $json['error'] = $this->language->get('error_invalid_request');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * الحصول على المحادثة السابقة
     */
    public function getConversation() {
        $this->load->language('common/ai_assistant');
        $this->load->model('common/ai_assistant');
        
        $json = array();
        
        // الحصول على المحادثة السابقة للمستخدم
        $conversation = $this->model_common_ai_assistant->getConversation($this->user->getId());
        
        $json['success'] = true;
        $json['conversation'] = $conversation;
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * مسح المحادثة
     */
    public function clearConversation() {
        $this->load->language('common/ai_assistant');
        $this->load->model('common/ai_assistant');
        
        $json = array();
        
        // مسح المحادثة للمستخدم الحالي
        $this->model_common_ai_assistant->clearConversation($this->user->getId());
        
        $json['success'] = true;
        $json['message'] = $this->language->get('text_conversation_cleared');
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * حفظ إعدادات المساعد الذكي
     */
    public function saveSettings() {
        $this->load->language('common/ai_assistant');
        $this->load->model('common/ai_assistant');
        
        $json = array();
        
        // تجميع الإعدادات من الطلب
        $settings = array(
            'ai_model' => isset($this->request->post['ai_model']) ? $this->request->post['ai_model'] : 'default',
            'ai_save_history' => isset($this->request->post['ai_save_history']) ? (int)$this->request->post['ai_save_history'] : 0,
            'ai_suggestions' => isset($this->request->post['ai_suggestions']) ? (int)$this->request->post['ai_suggestions'] : 0,
            'ai_auto_complete' => isset($this->request->post['ai_auto_complete']) ? (int)$this->request->post['ai_auto_complete'] : 0,
            'ai_access_sales' => isset($this->request->post['ai_access_sales']) ? (int)$this->request->post['ai_access_sales'] : 0,
            'ai_access_inventory' => isset($this->request->post['ai_access_inventory']) ? (int)$this->request->post['ai_access_inventory'] : 0,
            'ai_access_customers' => isset($this->request->post['ai_access_customers']) ? (int)$this->request->post['ai_access_customers'] : 0,
            'ai_access_reports' => isset($this->request->post['ai_access_reports']) ? (int)$this->request->post['ai_access_reports'] : 0
        );
        
        // حفظ الإعدادات
        $this->model_common_ai_assistant->saveSettings($this->user->getId(), $settings);
        
        $json['success'] = $this->language->get('text_settings_saved');
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}