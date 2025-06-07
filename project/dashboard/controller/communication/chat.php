<?php
/**
 * نظام الدردشة المباشرة المتقدم
 * Real-time Chat System Controller
 * 
 * نظام دردشة مباشرة متقدم مع تكامل مع catalog/inventory
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

class ControllerCommunicationChat extends Controller {
    
    /**
     * @var array خطأ في النظام
     */
    private $error = array();
    
    /**
     * عرض صفحة الدردشة الرئيسية
     */
    public function index() {
        $this->load->language('communication/chat');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'communication/chat')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('communication/chat', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // تحميل المحادثات
        $this->load->model('communication/chat');
        
        // المحادثات الحديثة
        $data['recent_chats'] = $this->model_communication_chat->getRecentChats($this->user->getId());
        
        // المحادثات النشطة
        $data['active_chats'] = $this->model_communication_chat->getActiveChats($this->user->getId());
        
        // المحادثات الجماعية
        $data['group_chats'] = $this->model_communication_chat->getGroupChats($this->user->getId());
        
        // المستخدمين المتاحين للدردشة
        $this->load->model('user/user');
        $data['online_users'] = $this->model_communication_chat->getOnlineUsers();
        
        // إحصائيات الدردشة
        $data['chat_stats'] = array(
            'unread_messages' => $this->model_communication_chat->getUnreadCount($this->user->getId()),
            'active_conversations' => $this->model_communication_chat->getActiveConversationsCount($this->user->getId()),
            'total_messages_today' => $this->model_communication_chat->getTodayMessagesCount($this->user->getId())
        );
        
        // إعدادات الدردشة
        $data['chat_settings'] = array(
            'auto_scroll' => true,
            'sound_notifications' => true,
            'show_typing_indicator' => true,
            'message_preview' => true
        );
        
        // أنواع المحادثات المتخصصة للـ catalog/inventory
        $data['specialized_chats'] = array(
            'catalog_team' => array(
                'name' => $this->language->get('text_catalog_team_chat'),
                'description' => $this->language->get('text_catalog_team_description'),
                'icon' => 'fa-tags',
                'href' => $this->url->link('communication/chat/room', 'type=catalog&user_token=' . $this->session->data['user_token'], true)
            ),
            'inventory_team' => array(
                'name' => $this->language->get('text_inventory_team_chat'),
                'description' => $this->language->get('text_inventory_team_description'),
                'icon' => 'fa-cubes',
                'href' => $this->url->link('communication/chat/room', 'type=inventory&user_token=' . $this->session->data['user_token'], true)
            ),
            'warehouse_operations' => array(
                'name' => $this->language->get('text_warehouse_operations_chat'),
                'description' => $this->language->get('text_warehouse_operations_description'),
                'icon' => 'fa-warehouse',
                'href' => $this->url->link('communication/chat/room', 'type=warehouse&user_token=' . $this->session->data['user_token'], true)
            ),
            'urgent_alerts' => array(
                'name' => $this->language->get('text_urgent_alerts_chat'),
                'description' => $this->language->get('text_urgent_alerts_description'),
                'icon' => 'fa-exclamation-triangle',
                'href' => $this->url->link('communication/chat/room', 'type=alerts&user_token=' . $this->session->data['user_token'], true)
            )
        );
        
        // الروابط
        $data['new_chat'] = $this->url->link('communication/chat/new', 'user_token=' . $this->session->data['user_token'], true);
        $data['chat_settings_url'] = $this->url->link('communication/chat/settings', 'user_token=' . $this->session->data['user_token'], true);
        
        // WebSocket configuration for real-time chat
        $data['websocket_config'] = array(
            'enabled' => true,
            'server' => 'ws://localhost:8080',
            'user_id' => $this->user->getId(),
            'user_token' => $this->session->data['user_token']
        );
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        $data['current_user_id'] = $this->user->getId();
        $data['current_user_name'] = $this->user->getUserName();
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('communication/chat', $data));
    }
    
    /**
     * غرفة دردشة محددة
     */
    public function room() {
        $this->load->language('communication/chat');
        
        if (isset($this->request->get['chat_id'])) {
            $chat_id = (int)$this->request->get['chat_id'];
        } else {
            $chat_id = 0;
        }
        
        if (isset($this->request->get['type'])) {
            $chat_type = $this->request->get['type'];
        } else {
            $chat_type = 'general';
        }
        
        $this->load->model('communication/chat');
        
        // التحقق من صلاحية الوصول للدردشة
        if ($chat_id > 0) {
            $chat_info = $this->model_communication_chat->getChat($chat_id);
            
            if (!$chat_info || !$this->model_communication_chat->hasAccess($chat_id, $this->user->getId())) {
                $this->response->redirect($this->url->link('communication/chat', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            // إنشاء غرفة دردشة جديدة حسب النوع
            $chat_info = $this->model_communication_chat->createSpecializedRoom($chat_type, $this->user->getId());
            $chat_id = $chat_info['chat_id'];
        }
        
        $this->document->setTitle($chat_info['chat_name']);
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('communication/chat', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $chat_info['chat_name'],
            'href' => $this->url->link('communication/chat/room', 'chat_id=' . $chat_id . '&user_token=' . $this->session->data['user_token'], true)
        );
        
        // معلومات الدردشة
        $data['chat_info'] = $chat_info;
        $data['chat_id'] = $chat_id;
        
        // تحميل الرسائل
        $data['messages'] = $this->model_communication_chat->getChatMessages($chat_id, 50);
        
        // المشاركين في الدردشة
        $data['participants'] = $this->model_communication_chat->getChatParticipants($chat_id);
        
        // تحديد الرسائل كمقروءة
        $this->model_communication_chat->markMessagesAsRead($chat_id, $this->user->getId());
        
        // إعدادات خاصة بنوع الدردشة
        switch ($chat_type) {
            case 'catalog':
                $data['quick_actions'] = array(
                    'add_product' => $this->url->link('catalog/product/add', 'user_token=' . $this->session->data['user_token'], true),
                    'check_stock' => $this->url->link('inventory/stock_inquiry', 'user_token=' . $this->session->data['user_token'], true),
                    'price_update' => $this->url->link('catalog/dynamic_pricing', 'user_token=' . $this->session->data['user_token'], true)
                );
                break;
            case 'inventory':
                $data['quick_actions'] = array(
                    'stock_movement' => $this->url->link('inventory/movement_history', 'user_token=' . $this->session->data['user_token'], true),
                    'stock_adjustment' => $this->url->link('inventory/adjustment/add', 'user_token=' . $this->session->data['user_token'], true),
                    'low_stock_report' => $this->url->link('inventory/reports/low_stock', 'user_token=' . $this->session->data['user_token'], true)
                );
                break;
            case 'warehouse':
                $data['quick_actions'] = array(
                    'goods_receipt' => $this->url->link('purchase/goods_receipt/add', 'user_token=' . $this->session->data['user_token'], true),
                    'pick_list' => $this->url->link('shipping/prepare_orders', 'user_token=' . $this->session->data['user_token'], true),
                    'warehouse_transfer' => $this->url->link('inventory/transfer/add', 'user_token=' . $this->session->data['user_token'], true)
                );
                break;
        }
        
        // الروابط
        $data['send_message_url'] = $this->url->link('communication/chat/send', 'user_token=' . $this->session->data['user_token'], true);
        $data['upload_file_url'] = $this->url->link('communication/chat/upload', 'user_token=' . $this->session->data['user_token'], true);
        $data['back_to_chat'] = $this->url->link('communication/chat', 'user_token=' . $this->session->data['user_token'], true);
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        $data['current_user_id'] = $this->user->getId();
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('communication/chat_room', $data));
    }
    
    /**
     * إرسال رسالة
     */
    public function send() {
        $this->load->language('communication/chat');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'communication/chat')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateMessage()) {
                $this->load->model('communication/chat');
                
                $message_data = array(
                    'chat_id' => $this->request->post['chat_id'],
                    'sender_id' => $this->user->getId(),
                    'message' => $this->request->post['message'],
                    'message_type' => isset($this->request->post['message_type']) ? $this->request->post['message_type'] : 'text',
                    'reply_to' => isset($this->request->post['reply_to']) ? $this->request->post['reply_to'] : null
                );
                
                $message_id = $this->model_communication_chat->sendMessage($message_data);
                
                if ($message_id) {
                    // إرسال إشعار للمشاركين
                    $this->sendChatNotification($message_data['chat_id'], $message_id);
                    
                    // إرسال عبر WebSocket للتحديث المباشر
                    $this->broadcastMessage($message_data['chat_id'], $message_id);
                    
                    $json['success'] = true;
                    $json['message_id'] = $message_id;
                    $json['message'] = $this->language->get('text_message_sent');
                } else {
                    $json['error'] = $this->language->get('error_message_send_failed');
                }
            } else {
                $json['error'] = $this->language->get('error_message_validation');
                if ($this->error) {
                    $json['errors'] = $this->error;
                }
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * تحميل ملف
     */
    public function upload() {
        $this->load->language('communication/chat');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'communication/chat')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->files['file']) && is_uploaded_file($this->request->files['file']['tmp_name'])) {
                $this->load->model('communication/chat');
                
                $upload_result = $this->model_communication_chat->uploadFile($this->request->files['file'], $this->user->getId());
                
                if ($upload_result['success']) {
                    $json['success'] = true;
                    $json['file_info'] = $upload_result['file_info'];
                    $json['message'] = $this->language->get('text_file_uploaded');
                } else {
                    $json['error'] = $upload_result['error'];
                }
            } else {
                $json['error'] = $this->language->get('error_no_file');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * الحصول على الرسائل الجديدة
     */
    public function getNewMessages() {
        $json = array();
        
        if (!$this->user->hasPermission('access', 'communication/chat')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('communication/chat');
            
            $chat_id = isset($this->request->get['chat_id']) ? (int)$this->request->get['chat_id'] : 0;
            $last_message_id = isset($this->request->get['last_message_id']) ? (int)$this->request->get['last_message_id'] : 0;
            
            if ($chat_id > 0) {
                $new_messages = $this->model_communication_chat->getNewMessages($chat_id, $last_message_id);
                
                $json['success'] = true;
                $json['messages'] = $new_messages;
                $json['count'] = count($new_messages);
            } else {
                $json['error'] = $this->language->get('error_chat_id_required');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * إرسال إشعار للمشاركين في الدردشة
     */
    private function sendChatNotification($chat_id, $message_id) {
        $this->load->model('communication/chat');
        $this->load->model('notification/center');
        
        $participants = $this->model_communication_chat->getChatParticipants($chat_id);
        $chat_info = $this->model_communication_chat->getChat($chat_id);
        
        foreach ($participants as $participant) {
            if ($participant['user_id'] != $this->user->getId()) {
                $this->model_notification_center->addNotification(array(
                    'type' => 'new_chat_message',
                    'recipient_id' => $participant['user_id'],
                    'title' => 'رسالة جديدة في ' . $chat_info['chat_name'],
                    'message' => 'لديك رسالة جديدة من ' . $this->user->getUserName(),
                    'priority' => 'medium',
                    'link' => 'communication/chat/room&chat_id=' . $chat_id
                ));
            }
        }
    }
    
    /**
     * بث الرسالة عبر WebSocket
     */
    private function broadcastMessage($chat_id, $message_id) {
        // تنفيذ بث الرسالة عبر WebSocket للتحديث المباشر
        // يمكن تخصيصه حسب تقنية WebSocket المستخدمة
    }
    
    /**
     * التحقق من صحة الرسالة
     */
    protected function validateMessage() {
        if (!$this->user->hasPermission('modify', 'communication/chat')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (!isset($this->request->post['chat_id']) || (int)$this->request->post['chat_id'] <= 0) {
            $this->error['chat_id'] = $this->language->get('error_chat_id_required');
        }
        
        if (!isset($this->request->post['message']) || utf8_strlen(trim($this->request->post['message'])) < 1) {
            $this->error['message'] = $this->language->get('error_message_required');
        }
        
        if (isset($this->request->post['message']) && utf8_strlen($this->request->post['message']) > 1000) {
            $this->error['message'] = $this->language->get('error_message_too_long');
        }
        
        return !$this->error;
    }
}
