<?php
/**
 * نظام الرسائل والتواصل الداخلي المتقدم
 * Internal Communication Messages Controller
 * 
 * نظام تواصل داخلي متقدم مع تكامل مع catalog/inventory
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

class ControllerCommunicationMessages extends Controller {
    
    /**
     * @var array خطأ في النظام
     */
    private $error = array();
    
    /**
     * عرض صفحة الرسائل الرئيسية
     */
    public function index() {
        $this->load->language('communication/messages');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'communication/messages')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('communication/messages', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // تحميل الرسائل
        $this->load->model('communication/messages');
        
        $filter_data = array(
            'start' => 0,
            'limit' => 20
        );
        
        // الرسائل الواردة
        $data['inbox_messages'] = $this->model_communication_messages->getInboxMessages($this->user->getId(), $filter_data);
        $data['inbox_total'] = $this->model_communication_messages->getTotalInboxMessages($this->user->getId());
        $data['inbox_unread'] = $this->model_communication_messages->getUnreadCount($this->user->getId());
        
        // الرسائل المرسلة
        $data['sent_messages'] = $this->model_communication_messages->getSentMessages($this->user->getId(), $filter_data);
        $data['sent_total'] = $this->model_communication_messages->getTotalSentMessages($this->user->getId());
        
        // الرسائل المحذوفة
        $data['deleted_messages'] = $this->model_communication_messages->getDeletedMessages($this->user->getId(), $filter_data);
        $data['deleted_total'] = $this->model_communication_messages->getTotalDeletedMessages($this->user->getId());
        
        // المستخدمين المتاحين للمراسلة
        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();
        
        // الفرق المتاحة
        $this->load->model('communication/teams');
        $data['teams'] = $this->model_communication_teams->getUserTeams($this->user->getId());
        
        // الروابط
        $data['compose'] = $this->url->link('communication/messages/compose', 'user_token=' . $this->session->data['user_token'], true);
        $data['refresh'] = $this->url->link('communication/messages', 'user_token=' . $this->session->data['user_token'], true);
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('communication/messages', $data));
    }
    
    /**
     * إنشاء رسالة جديدة
     */
    public function compose() {
        $this->load->language('communication/messages');
        
        $this->document->setTitle($this->language->get('text_compose'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'communication/messages')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // معالجة إرسال الرسالة
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('communication/messages');
            
            $message_id = $this->model_communication_messages->addMessage($this->request->post);
            
            // إرسال إشعار للمستقبلين
            $this->sendNotification($message_id, $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $this->response->redirect($this->url->link('communication/messages', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('communication/messages', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_compose'),
            'href' => $this->url->link('communication/messages/compose', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // المستخدمين المتاحين
        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();
        
        // الفرق المتاحة
        $this->load->model('communication/teams');
        $data['teams'] = $this->model_communication_teams->getTeams();
        
        // أولويات الرسائل
        $data['priorities'] = array(
            'low' => $this->language->get('text_priority_low'),
            'medium' => $this->language->get('text_priority_medium'),
            'high' => $this->language->get('text_priority_high'),
            'critical' => $this->language->get('text_priority_critical')
        );
        
        // أنواع الرسائل
        $data['message_types'] = array(
            'general' => $this->language->get('text_type_general'),
            'catalog' => $this->language->get('text_type_catalog'),
            'inventory' => $this->language->get('text_type_inventory'),
            'purchase' => $this->language->get('text_type_purchase'),
            'sales' => $this->language->get('text_type_sales'),
            'finance' => $this->language->get('text_type_finance')
        );
        
        // الروابط
        $data['action'] = $this->url->link('communication/messages/compose', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('communication/messages', 'user_token=' . $this->session->data['user_token'], true);
        
        // الرسائل
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('communication/compose', $data));
    }
    
    /**
     * عرض رسالة محددة
     */
    public function view() {
        $this->load->language('communication/messages');
        
        if (isset($this->request->get['message_id'])) {
            $message_id = (int)$this->request->get['message_id'];
        } else {
            $message_id = 0;
        }
        
        $this->load->model('communication/messages');
        
        $message_info = $this->model_communication_messages->getMessage($message_id);
        
        if ($message_info) {
            // التحقق من الصلاحية لقراءة الرسالة
            if (!$this->canReadMessage($message_info)) {
                $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
            }
            
            // تحديد الرسالة كمقروءة
            $this->model_communication_messages->markAsRead($message_id, $this->user->getId());
            
            $this->document->setTitle($message_info['subject']);
            
            $data['breadcrumbs'] = array();
            
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('communication/messages', 'user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['breadcrumbs'][] = array(
                'text' => $message_info['subject'],
                'href' => $this->url->link('communication/messages/view', 'message_id=' . $message_id . '&user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['message'] = $message_info;
            
            // المرفقات
            $data['attachments'] = $this->model_communication_messages->getMessageAttachments($message_id);
            
            // الردود
            $data['replies'] = $this->model_communication_messages->getMessageReplies($message_id);
            
            // الروابط
            $data['reply'] = $this->url->link('communication/messages/reply', 'message_id=' . $message_id . '&user_token=' . $this->session->data['user_token'], true);
            $data['forward'] = $this->url->link('communication/messages/forward', 'message_id=' . $message_id . '&user_token=' . $this->session->data['user_token'], true);
            $data['delete'] = $this->url->link('communication/messages/delete', 'message_id=' . $message_id . '&user_token=' . $this->session->data['user_token'], true);
            $data['back'] = $this->url->link('communication/messages', 'user_token=' . $this->session->data['user_token'], true);
            
            // التوكن
            $data['user_token'] = $this->session->data['user_token'];
            
            // عرض الصفحة
            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');
            
            $this->response->setOutput($this->load->view('communication/message_view', $data));
        } else {
            $this->response->redirect($this->url->link('communication/messages', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    /**
     * حذف رسالة
     */
    public function delete() {
        $this->load->language('communication/messages');
        
        if (!$this->user->hasPermission('modify', 'communication/messages')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        if (isset($this->request->get['message_id'])) {
            $message_id = (int)$this->request->get['message_id'];
            
            $this->load->model('communication/messages');
            $this->model_communication_messages->deleteMessage($message_id, $this->user->getId());
            
            $this->session->data['success'] = $this->language->get('text_delete_success');
        }
        
        $this->response->redirect($this->url->link('communication/messages', 'user_token=' . $this->session->data['user_token'], true));
    }
    
    /**
     * إرسال إشعار للمستقبلين
     */
    private function sendNotification($message_id, $data) {
        $this->load->model('notification/center');
        
        // إرسال إشعار لكل مستقبل
        if (isset($data['recipients']) && is_array($data['recipients'])) {
            foreach ($data['recipients'] as $recipient_id) {
                $this->model_notification_center->addNotification(array(
                    'type' => 'new_message',
                    'recipient_id' => $recipient_id,
                    'title' => 'رسالة جديدة: ' . $data['subject'],
                    'message' => 'لديك رسالة جديدة من ' . $this->user->getUserName(),
                    'priority' => isset($data['priority']) ? $data['priority'] : 'medium',
                    'link' => 'communication/messages/view&message_id=' . $message_id
                ));
            }
        }
    }
    
    /**
     * التحقق من إمكانية قراءة الرسالة
     */
    private function canReadMessage($message_info) {
        $user_id = $this->user->getId();
        
        // المرسل يمكنه قراءة رسائله
        if ($message_info['sender_id'] == $user_id) {
            return true;
        }
        
        // المستقبل يمكنه قراءة الرسائل المرسلة إليه
        $this->load->model('communication/messages');
        $recipients = $this->model_communication_messages->getMessageRecipients($message_info['message_id']);
        
        foreach ($recipients as $recipient) {
            if ($recipient['user_id'] == $user_id) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * التحقق من صحة النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'communication/messages')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if ((utf8_strlen($this->request->post['subject']) < 1) || (utf8_strlen($this->request->post['subject']) > 255)) {
            $this->error['subject'] = $this->language->get('error_subject');
        }
        
        if (utf8_strlen($this->request->post['message']) < 1) {
            $this->error['message'] = $this->language->get('error_message');
        }
        
        if (!isset($this->request->post['recipients']) || empty($this->request->post['recipients'])) {
            $this->error['recipients'] = $this->language->get('error_recipients');
        }
        
        return !$this->error;
    }
}
