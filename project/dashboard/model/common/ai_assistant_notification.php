<?php
/**
 * نظام أيم ERP: نموذج تكامل المساعد الذكي مع مركز الإشعارات
 * هذا الملف يوفر وظائف التكامل بين المساعد الذكي ومركز الإشعارات في نظام أيم ERP
 */
class ModelCommonAiAssistantNotification extends Model {
    /**
     * إرسال إشعار عن نشاط المساعد الذكي
     * @param int $user_id معرف المستخدم
     * @param string $message الرسالة المرسلة
     * @param string $response الرد المستلم
     * @return int معرف الإشعار
     */
    public function sendActivityNotification($user_id, $message, $response) {
        $this->load->model('common/notification');
        
        // تحميل ملف اللغة
        $this->load->language('common/ai_assistant');
        
        // إنشاء إشعار بالتفاعل مع المساعد الذكي
        $notification_data = array(
            'user_id' => $user_id,
            'title' => $this->language->get('notification_title'),
            'message' => sprintf($this->language->get('notification_message'), substr($message, 0, 30)),
            'icon' => 'fa-robot',
            'color' => 'info',
            'url' => 'common/ai_assistant',
            'is_read' => 0
        );
        
        return $this->model_common_notification->addNotification($notification_data);
    }
    
    /**
     * إرسال إشعار عن اكتشاف المساعد الذكي لمشكلة محتملة
     * @param int $user_id معرف المستخدم
     * @param string $issue_type نوع المشكلة
     * @param string $description وصف المشكلة
     * @param string $module الوحدة المرتبطة بالمشكلة
     * @return int معرف الإشعار
     */
    public function sendIssueNotification($user_id, $issue_type, $description, $module = '') {
        $this->load->model('common/notification');
        
        // تحميل ملف اللغة
        $this->load->language('common/ai_assistant');
        
        // تحديد لون الإشعار بناءً على نوع المشكلة
        $color = 'warning';
        if ($issue_type == 'critical') {
            $color = 'danger';
        } elseif ($issue_type == 'info') {
            $color = 'info';
        }
        
        // إنشاء إشعار بالمشكلة المكتشفة
        $notification_data = array(
            'user_id' => $user_id,
            'title' => $this->language->get('notification_issue_title'),
            'message' => $description,
            'icon' => 'fa-exclamation-triangle',
            'color' => $color,
            'url' => !empty($module) ? $module : 'common/ai_assistant',
            'is_read' => 0
        );
        
        return $this->model_common_notification->addNotification($notification_data);
    }
    
    /**
     * إرسال إشعار عن اقتراح تحسين من المساعد الذكي
     * @param int $user_id معرف المستخدم
     * @param string $suggestion الاقتراح
     * @param string $module الوحدة المرتبطة بالاقتراح
     * @return int معرف الإشعار
     */
    public function sendSuggestionNotification($user_id, $suggestion, $module = '') {
        $this->load->model('common/notification');
        
        // تحميل ملف اللغة
        $this->load->language('common/ai_assistant');
        
        // إنشاء إشعار بالاقتراح
        $notification_data = array(
            'user_id' => $user_id,
            'title' => $this->language->get('notification_suggestion_title'),
            'message' => $suggestion,
            'icon' => 'fa-lightbulb',
            'color' => 'success',
            'url' => !empty($module) ? $module : 'common/ai_assistant',
            'is_read' => 0
        );
        
        return $this->model_common_notification->addNotification($notification_data);
    }
    
    /**
     * إرسال إشعار عن تحديث في إعدادات المساعد الذكي
     * @param int $user_id معرف المستخدم
     * @param array $old_settings الإعدادات القديمة
     * @param array $new_settings الإعدادات الجديدة
     * @return int معرف الإشعار
     */
    public function sendSettingsUpdateNotification($user_id, $old_settings, $new_settings) {
        $this->load->model('common/notification');
        
        // تحميل ملف اللغة
        $this->load->language('common/ai_assistant');
        
        // إنشاء وصف للتغييرات
        $changes = array();
        
        if ($old_settings['ai_model'] != $new_settings['ai_model']) {
            $changes[] = sprintf($this->language->get('notification_model_changed'), $old_settings['ai_model'], $new_settings['ai_model']);
        }
        
        if ($old_settings['ai_save_history'] != $new_settings['ai_save_history']) {
            $changes[] = $new_settings['ai_save_history'] ? 
                $this->language->get('notification_history_enabled') : 
                $this->language->get('notification_history_disabled');
        }
        
        // إنشاء إشعار بتحديث الإعدادات
        $notification_data = array(
            'user_id' => $user_id,
            'title' => $this->language->get('notification_settings_title'),
            'message' => !empty($changes) ? implode(', ', $changes) : $this->language->get('notification_settings_updated'),
            'icon' => 'fa-cog',
            'color' => 'primary',
            'url' => 'common/ai_assistant/settings',
            'is_read' => 0
        );
        
        return $this->model_common_notification->addNotification($notification_data);
    }
    
    /**
     * إرسال إشعار عن تحليل بيانات من المساعد الذكي
     * @param int $user_id معرف المستخدم
     * @param string $analysis_type نوع التحليل
     * @param string $summary ملخص التحليل
     * @param string $module الوحدة المرتبطة بالتحليل
     * @return int معرف الإشعار
     */
    public function sendAnalysisNotification($user_id, $analysis_type, $summary, $module = '') {
        $this->load->model('common/notification');
        
        // تحميل ملف اللغة
        $this->load->language('common/ai_assistant');
        
        // إنشاء إشعار بالتحليل
        $notification_data = array(
            'user_id' => $user_id,
            'title' => sprintf($this->language->get('notification_analysis_title'), $analysis_type),
            'message' => $summary,
            'icon' => 'fa-chart-line',
            'color' => 'info',
            'url' => !empty($module) ? $module : 'common/ai_assistant',
            'is_read' => 0
        );
        
        return $this->model_common_notification->addNotification($notification_data);
    }
    
    /**
     * إرسال إشعار عن مهمة تم إكمالها بواسطة المساعد الذكي
     * @param int $user_id معرف المستخدم
     * @param string $task_name اسم المهمة
     * @param string $result نتيجة المهمة
     * @param string $module الوحدة المرتبطة بالمهمة
     * @return int معرف الإشعار
     */
    public function sendTaskCompletionNotification($user_id, $task_name, $result, $module = '') {
        $this->load->model('common/notification');
        
        // تحميل ملف اللغة
        $this->load->language('common/ai_assistant');
        
        // إنشاء إشعار بإكمال المهمة
        $notification_data = array(
            'user_id' => $user_id,
            'title' => sprintf($this->language->get('notification_task_title'), $task_name),
            'message' => $result,
            'icon' => 'fa-check-circle',
            'color' => 'success',
            'url' => !empty($module) ? $module : 'common/ai_assistant',
            'is_read' => 0
        );
        
        return $this->model_common_notification->addNotification($notification_data);
    }
}