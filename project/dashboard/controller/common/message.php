<?php
class ControllerCommonMessage extends Controller {
    public function index() {
        $this->load->language('common/message');
        
        $this->load->model('common/message');
        
        $json = array();
        
        if (!$this->user->isLogged()) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        // Get messages
        $start = isset($this->request->get['start']) ? (int)$this->request->get['start'] : 0;
        $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 10;
        
        $messages = $this->model_common_message->getMessages($this->user->getId(), $start, $limit);
        
        $json['messages'] = array();
        foreach ($messages as $message) {
            $json['messages'][] = array(
                'message_id' => $message['message_id'],
                'subject' => $message['subject'],
                'message' => $message['message'],
                'sender_name' => $message['sender_name'],
                'sender_image' => $message['sender_image'],
                'other_recipients' => $message['other_recipients'],
                'is_read' => (bool)$message['is_read'],
                'read_at' => $message['read_at'] ? date($this->language->get('datetime_format'), strtotime($message['read_at'])) : '',
                'created_at' => date($this->language->get('datetime_format'), strtotime($message['created_at'])),
                'starred' => (bool)$message['starred'],
                'attachments' => json_decode($message['attachments'], true),
                'mentions' => json_decode($message['mentions'], true)
            );
        }
        
        $json['unread_count'] = $this->model_common_message->getUnreadCount($this->user->getId());
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function view() {
        $this->load->language('common/message');
        
        $this->load->model('common/message');
        
        $json = array();
        
        if (!$this->user->isLogged()) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (isset($this->request->get['message_id'])) {
            $message_id = (int)$this->request->get['message_id'];
            
            // Get message and thread
            $message = $this->model_common_message->getMessage($message_id, $this->user->getId());
            
            if ($message) {
                // Mark as read
                $this->model_common_message->markAsRead($message_id, $this->user->getId());
                
                // Get thread messages
                $thread = $this->model_common_message->getThread($message_id, $this->user->getId());
                
                // Get participants
                $participants = $this->model_common_message->getParticipants($message_id);
                
                $json['message'] = array(
                    'message_id' => $message['message_id'],
                    'subject' => $message['subject'],
                    'message' => $message['message'],
                    'sender_name' => $message['sender_name'],
                    'sender_image' => $message['sender_image'],
                    'created_at' => date($this->language->get('datetime_format'), strtotime($message['created_at'])),
                    'attachments' => json_decode($message['attachments'], true),
                    'mentions' => json_decode($message['mentions'], true),
                    'starred' => (bool)$message['starred']
                );
                
                $json['thread'] = array();
                foreach ($thread as $reply) {
                    $json['thread'][] = array(
                        'message_id' => $reply['message_id'],
                        'message' => $reply['message'],
                        'sender_name' => $reply['sender_name'],
                        'sender_image' => $reply['sender_image'],
                        'created_at' => date($this->language->get('datetime_format'), strtotime($reply['created_at'])),
                        'attachments' => json_decode($reply['attachments'], true),
                        'mentions' => json_decode($reply['mentions'], true)
                    );
                }
                
                $json['participants'] = $participants;
                
            } else {
                $json['error'] = $this->language->get('error_message_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_message_id');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function send() {
        $this->load->language('common/message');
        
        $this->load->model('common/message');
        
        $json = array();
        
        if (!$this->user->isLogged()) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (isset($this->request->post['subject']) && isset($this->request->post['message']) && isset($this->request->post['recipients'])) {
            $data = array(
                'sender_id' => $this->user->getId(),
                'subject' => $this->request->post['subject'],
                'message' => $this->request->post['message'],
                'recipients' => $this->request->post['recipients'],
                'is_private' => isset($this->request->post['is_private']) ? (bool)$this->request->post['is_private'] : false,
                'parent_id' => isset($this->request->post['parent_id']) ? (int)$this->request->post['parent_id'] : 0,
                'attachments' => isset($this->request->post['attachments']) ? $this->request->post['attachments'] : array(),
                'mentions' => isset($this->request->post['mentions']) ? $this->request->post['mentions'] : array()
            );
            
            if ($data['parent_id']) {
                $message_id = $this->model_common_message->replyToMessage($data);
            } else {
                $message_id = $this->model_common_message->sendMessage($data);
            }
            
            if ($message_id) {
                $json['success'] = $this->language->get('text_message_sent');
                $json['message_id'] = $message_id;
            } else {
                $json['error'] = $this->language->get('error_sending_message');
            }
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function star() {
        $this->load->language('common/message');
        
        $this->load->model('common/message');
        
        $json = array();
        
        if (!$this->user->isLogged()) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (isset($this->request->post['message_id'])) {
            $this->model_common_message->starMessage($this->request->post['message_id'], $this->user->getId());
            $json['success'] = $this->language->get('text_message_starred');
        } else {
            $json['error'] = $this->language->get('error_message_id');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function delete() {
        $this->load->language('common/message');
        
        $this->load->model('common/message');
        
        $json = array();
        
        if (!$this->user->isLogged()) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (isset($this->request->post['message_id'])) {
            $this->model_common_message->deleteMessage($this->request->post['message_id'], $this->user->getId());
            $json['success'] = $this->language->get('text_message_deleted');
        } else {
            $json['error'] = $this->language->get('error_message_id');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function addParticipant() {
        $this->load->language('common/message');
        
        $this->load->model('common/message');
        
        $json = array();
        
        if (!$this->user->isLogged()) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (isset($this->request->post['message_id']) && isset($this->request->post['user_id'])) {
            $this->model_common_message->addParticipant($this->request->post['message_id'], $this->request->post['user_id']);
            $json['success'] = $this->language->get('text_participant_added');
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function updateNotificationSettings() {
        $this->load->language('common/message');
        
        $this->load->model('common/message');
        
        $json = array();
        
        if (!$this->user->isLogged()) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (isset($this->request->post['message_id']) && isset($this->request->post['settings'])) {
            $this->model_common_message->updateNotificationSettings(
                $this->request->post['message_id'],
                $this->user->getId(),
                $this->request->post['settings']
            );
            $json['success'] = $this->language->get('text_settings_updated');
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}