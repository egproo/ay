<?php
class ControllerToolMessaging extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('tool/messaging');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('tool/messaging');
        
        // Check for folder parameter
        if (isset($this->request->get['folder'])) {
            $folder = $this->request->get['folder'];
        } else {
            $folder = 'inbox';
        }
        
        // Get unread message counts
        $data['inbox_count'] = $this->model_tool_messaging->getTotalMessages(['filter_folder' => 'inbox', 'filter_read' => 0]);
        $data['sent_count'] = $this->model_tool_messaging->getTotalMessages(['filter_folder' => 'sent']);
        $data['draft_count'] = $this->model_tool_messaging->getTotalMessages(['filter_folder' => 'draft']);
        $data['archived_count'] = $this->model_tool_messaging->getTotalMessages(['filter_folder' => 'archived']);
        
        // Get unread notification count
        $data['notification_count'] = $this->model_tool_messaging->getUnreadNotificationCount($this->user->getId());
        
        // Set folder URLs
        $data['inbox'] = $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . '&folder=inbox', true);
        $data['sent'] = $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . '&folder=sent', true);
        $data['draft'] = $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . '&folder=draft', true);
        $data['archived'] = $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . '&folder=archived', true);
        $data['all'] = $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . '&folder=all', true);
        
        // Set folder
        $data['filter'] = $folder;
        
        $this->getList();
    }
    
    public function add() {
        $this->load->language('tool/messaging');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('tool/messaging');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $message_id = $this->model_tool_messaging->addMessage($this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $url = '';
            
            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }
            
            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }
            
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            
            $this->response->redirect($this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    public function view() {
        $this->load->language('tool/messaging');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('tool/messaging');
        
        if (isset($this->request->get['message_id'])) {
            $message_id = $this->request->get['message_id'];
        } else {
            $message_id = 0;
        }
        
        $message_info = $this->model_tool_messaging->getMessage($message_id);
        
        if (!$message_info) {
            $this->response->redirect($this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Handle reply
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateReplyForm()) {
            if (isset($this->request->post['action']) && $this->request->post['action'] == 'reply') {
                $this->model_tool_messaging->addReply($message_id, $this->request->post);
                
                $this->session->data['success'] = $this->language->get('text_success');
            } elseif (isset($this->request->post['action']) && $this->request->post['action'] == 'save_draft') {
                $this->model_tool_messaging->saveDraft($message_id, $this->request->post);
                
                $this->session->data['success'] = $this->language->get('text_draft_saved');
            }
            
            $this->response->redirect($this->url->link('tool/messaging/view', 'user_token=' . $this->session->data['user_token'] . '&message_id=' . $message_id, true));
        }
        
        $this->getViewForm();
    }
    
    public function delete() {
        $this->load->language('tool/messaging');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('tool/messaging');
        
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $message_id) {
                $this->model_tool_messaging->deleteMessage($message_id);
            }
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $url = '';
            
            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }
            
            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }
            
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            
            $this->response->redirect($this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getList();
    }
    
    public function upload() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'tool/messaging')) {
            $json['error'] = $this->language->get('error_permission');
        }
        
        if (!isset($json['error'])) {
            if (!empty($this->request->files['file']['name']) && is_file($this->request->files['file']['tmp_name'])) {
                // Sanitize the filename
                $filename = basename(html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8'));
                
                // Validate the filename length
                if ((utf8_strlen($filename) < 3) || (utf8_strlen($filename) > 128)) {
                    $json['error'] = $this->language->get('error_filename');
                }
                
                // Allowed file extension types
                $allowed = array(
                    'jpg',
                    'jpeg',
                    'gif',
                    'png',
                    'doc',
                    'docx',
                    'xls',
                    'xlsx',
                    'pdf',
                    'zip',
                    'txt'
                );
                
                if (!in_array(strtolower(substr(strrchr($filename, '.'), 1)), $allowed)) {
                    $json['error'] = $this->language->get('error_filetype');
                }
                
                // Check to see if any PHP files are trying to be uploaded
                $content = file_get_contents($this->request->files['file']['tmp_name']);
                
                if (preg_match('/\<\?php/i', $content)) {
                    $json['error'] = $this->language->get('error_filetype');
                }
                
                // Return any upload error
                if ($this->request->files['file']['error'] != UPLOAD_ERR_OK) {
                    $json['error'] = $this->language->get('error_upload');
                }
                
                // Check filesize
                if ($this->request->files['file']['size'] > 2097152) { // 2MB max
                    $json['error'] = $this->language->get('error_filesize');
                }
            } else {
                $json['error'] = $this->language->get('error_upload');
            }
        }
        
        if (!isset($json['error'])) {
            // Store file in a temporary directory
            $file = 'message_attachments/' . $this->user->getId() . '/' . token(32) . '/' . $filename;
            
            // Create directories if they don't exist
            $directory = dirname(DIR_UPLOAD . $file);
            
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            
            move_uploaded_file($this->request->files['file']['tmp_name'], DIR_UPLOAD . $file);
            
            $json['success'] = $this->language->get('text_upload');
            $json['filename'] = $filename;
            $json['file'] = $file;
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    protected function getList() {
        if (isset($this->request->get['folder'])) {
            $folder = $this->request->get['folder'];
        } else {
            $folder = 'inbox';
        }
        
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'date_added';
        }
        
        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }
        
        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }
        
        // Set filter parameters
        if (isset($this->request->get['filter_subject'])) {
            $filter_subject = $this->request->get['filter_subject'];
        } else {
            $filter_subject = '';
        }
        
        if (isset($this->request->get['filter_sender'])) {
            $filter_sender = $this->request->get['filter_sender'];
        } else {
            $filter_sender = '';
        }
        
        if (isset($this->request->get['filter_recipient'])) {
            $filter_recipient = $this->request->get['filter_recipient'];
        } else {
            $filter_recipient = '';
        }
        
        if (isset($this->request->get['filter_date_start'])) {
            $filter_date_start = $this->request->get['filter_date_start'];
        } else {
            $filter_date_start = '';
        }
        
        if (isset($this->request->get['filter_date_end'])) {
            $filter_date_end = $this->request->get['filter_date_end'];
        } else {
            $filter_date_end = '';
        }
        
        if (isset($this->request->get['filter_has_attachment'])) {
            $filter_has_attachment = $this->request->get['filter_has_attachment'];
        } else {
            $filter_has_attachment = '';
        }
        
        if (isset($this->request->get['filter_read'])) {
            $filter_read = $this->request->get['filter_read'];
        } else {
            $filter_read = '';
        }
        
        if (isset($this->request->get['filter_priority'])) {
            $filter_priority = $this->request->get['filter_priority'];
        } else {
            $filter_priority = '';
        }
        
        if (isset($this->request->get['filter_conversation_id'])) {
            $filter_conversation_id = $this->request->get['filter_conversation_id'];
        } else {
            $filter_conversation_id = '';
        }
        
        $url = '';
        
        if (isset($this->request->get['folder'])) {
            $url .= '&folder=' . $this->request->get['folder'];
        }
        
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }
        
        // Add filter parameters to URL
        if (isset($this->request->get['filter_subject'])) {
            $url .= '&filter_subject=' . urlencode(html_entity_decode($this->request->get['filter_subject'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_sender'])) {
            $url .= '&filter_sender=' . urlencode(html_entity_decode($this->request->get['filter_sender'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_recipient'])) {
            $url .= '&filter_recipient=' . urlencode(html_entity_decode($this->request->get['filter_recipient'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }
        
        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }
        
        if (isset($this->request->get['filter_has_attachment'])) {
            $url .= '&filter_has_attachment=' . $this->request->get['filter_has_attachment'];
        }
        
        if (isset($this->request->get['filter_read'])) {
            $url .= '&filter_read=' . $this->request->get['filter_read'];
        }
        
        if (isset($this->request->get['filter_priority'])) {
            $url .= '&filter_priority=' . $this->request->get['filter_priority'];
        }
        
        if (isset($this->request->get['filter_conversation_id'])) {
            $url .= '&filter_conversation_id=' . $this->request->get['filter_conversation_id'];
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        $data['add'] = $this->url->link('tool/messaging/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('tool/messaging/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        $data['messages'] = array();
        
        $filter_data = array(
            'filter_folder'          => $folder,
            'filter_subject'         => $filter_subject,
            'filter_sender'          => $filter_sender,
            'filter_recipient'       => $filter_recipient,
            'filter_date_start'      => $filter_date_start,
            'filter_date_end'        => $filter_date_end,
            'filter_has_attachment'  => $filter_has_attachment,
            'filter_read'            => $filter_read,
            'filter_priority'        => $filter_priority,
            'filter_conversation_id' => $filter_conversation_id,
            'sort'                   => $sort,
            'order'                  => $order,
            'start'                  => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'                  => $this->config->get('config_limit_admin')
        );
        
        $message_total = $this->model_tool_messaging->getTotalMessages($filter_data);
        
        $results = $this->model_tool_messaging->getMessages($filter_data);
        
        foreach ($results as $result) {
            // Different button actions based on folder and message status
            $view = $this->url->link('tool/messaging/view', 'user_token=' . $this->session->data['user_token'] . '&message_id=' . $result['message_id'] . $url, true);
            $edit = '';
            $archive = '';
            $unarchive = '';
            $delete = '';
            
            if ($result['is_draft']) {
                $edit = $this->url->link('tool/messaging/edit', 'user_token=' . $this->session->data['user_token'] . '&message_id=' . $result['message_id'] . $url, true);
            }
            
            if ($folder != 'archived') {
                $archive = "archive('" . $result['message_id'] . "');";
            } else {
                $unarchive = "unarchive('" . $result['message_id'] . "');";
            }
            
            $delete = "deleteMessage('" . $result['message_id'] . "');";
            
            // Format names based on folder
            $from = $result['sender'];
            $to = $result['recipient'];
            
            // Format subject
            $subject = $result['subject'];
            
            // Add group indicator
            if ($result['is_group']) {
                $subject = '<i class="fa fa-users"></i> ' . $subject;
            }
            
            // Add priority indicator
            if ($result['priority'] == 1) {
                $subject = '<i class="fa fa-flag text-warning"></i> ' . $subject;
            } elseif ($result['priority'] == 2) {
                $subject = '<i class="fa fa-flag text-danger"></i> ' . $subject;
            }
            
            $data['messages'][] = array(
                'message_id'         => $result['message_id'],
                'conversation_id'    => $result['conversation_id'],
                'subject'            => $subject,
                'from'               => $from,
                'to'                 => $to,
                'priority'           => $result['priority'],
                'is_read'            => $result['is_read'],
                'is_draft'           => $result['is_draft'],
                'is_archived'        => $result['is_archived'],
                'has_attachment'     => $result['has_attachment'],
                'date_added'         => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'date_modified'      => date($this->language->get('date_format_short'), strtotime($result['date_modified'])),
                'view'               => $view,
                'edit'               => $edit,
                'archive'            => $archive,
                'unarchive'          => $unarchive,
                'delete'             => $delete
            );
        }
        
        $data['user_token'] = $this->session->data['user_token'];
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } else {
            $data['selected'] = array();
        }
        
        $url = '';
        
        if (isset($this->request->get['folder'])) {
            $url .= '&folder=' . $this->request->get['folder'];
        }
        
        if (isset($this->request->get['filter_subject'])) {
            $url .= '&filter_subject=' . urlencode(html_entity_decode($this->request->get['filter_subject'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_sender'])) {
            $url .= '&filter_sender=' . urlencode(html_entity_decode($this->request->get['filter_sender'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_recipient'])) {
            $url .= '&filter_recipient=' . urlencode(html_entity_decode($this->request->get['filter_recipient'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }
        
        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }
        
        if (isset($this->request->get['filter_has_attachment'])) {
            $url .= '&filter_has_attachment=' . $this->request->get['filter_has_attachment'];
        }
        
        if (isset($this->request->get['filter_read'])) {
            $url .= '&filter_read=' . $this->request->get['filter_read'];
        }
        
        if (isset($this->request->get['filter_priority'])) {
            $url .= '&filter_priority=' . $this->request->get['filter_priority'];
        }
        
        if (isset($this->request->get['filter_conversation_id'])) {
            $url .= '&filter_conversation_id=' . $this->request->get['filter_conversation_id'];
        }
        
        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }
        
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }
        
        $data['sort_subject'] = $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . '&sort=subject' . $url, true);
        $data['sort_sender'] = $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . '&sort=sender' . $url, true);
        $data['sort_recipient'] = $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . '&sort=recipient' . $url, true);
        $data['sort_priority'] = $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . '&sort=priority' . $url, true);
        $data['sort_date_added'] = $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . '&sort=date_added' . $url, true);
        $data['sort_date_modified'] = $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . '&sort=date_modified' . $url, true);
        
        $url = '';
        
        if (isset($this->request->get['folder'])) {
            $url .= '&folder=' . $this->request->get['folder'];
        }
        
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        
        if (isset($this->request->get['filter_subject'])) {
            $url .= '&filter_subject=' . urlencode(html_entity_decode($this->request->get['filter_subject'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_sender'])) {
            $url .= '&filter_sender=' . urlencode(html_entity_decode($this->request->get['filter_sender'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_recipient'])) {
            $url .= '&filter_recipient=' . urlencode(html_entity_decode($this->request->get['filter_recipient'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }
        
        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }
        
        if (isset($this->request->get['filter_has_attachment'])) {
            $url .= '&filter_has_attachment=' . $this->request->get['filter_has_attachment'];
        }
        
        if (isset($this->request->get['filter_read'])) {
            $url .= '&filter_read=' . $this->request->get['filter_read'];
        }
        
        if (isset($this->request->get['filter_priority'])) {
            $url .= '&filter_priority=' . $this->request->get['filter_priority'];
        }
        
        if (isset($this->request->get['filter_conversation_id'])) {
            $url .= '&filter_conversation_id=' . $this->request->get['filter_conversation_id'];
        }
        
        $pagination = new Pagination();
        $pagination->total = $message_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
        
        $data['pagination'] = $pagination->render();
        
        $data['results'] = sprintf($this->language->get('text_pagination'), ($message_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($message_total - $this->config->get('config_limit_admin'))) ? $message_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $message_total, ceil($message_total / $this->config->get('config_limit_admin')));
        
        // Set filter data for the view
        $data['folder'] = $folder;
        $data['filter_subject'] = $filter_subject;
        $data['filter_sender'] = $filter_sender;
        $data['filter_recipient'] = $filter_recipient;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;
        $data['filter_has_attachment'] = $filter_has_attachment;
        $data['filter_read'] = $filter_read;
        $data['filter_priority'] = $filter_priority;
        $data['filter_conversation_id'] = $filter_conversation_id;
        
        // Get recent conversations
        $data['conversations'] = $this->model_tool_messaging->getConversations($this->user->getId());
        
        // Get notifications
        $data['notifications'] = $this->model_tool_messaging->getNotifications($this->user->getId(), 0, 5);
        
        $data['sort'] = $sort;
        $data['order'] = $order;
        
        // Load users for recipient dropdown
        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('tool/messaging', $data));
    }
    
    protected function getForm() {
        $data['text_form'] = $this->language->get('text_add');
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->error['subject'])) {
            $data['error_subject'] = $this->error['subject'];
        } else {
            $data['error_subject'] = '';
        }
        
        if (isset($this->error['message'])) {
            $data['error_message'] = $this->error['message'];
        } else {
            $data['error_message'] = '';
        }
        
        $url = '';
        
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_add'),
            'href' => $this->url->link('tool/messaging/add', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        $data['action'] = $this->url->link('tool/messaging/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['cancel'] = $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        if (isset($this->request->post['subject'])) {
            $data['subject'] = $this->request->post['subject'];
        } else {
            $data['subject'] = '';
        }
        
        if (isset($this->request->post['message'])) {
            $data['message'] = $this->request->post['message'];
        } else {
            $data['message'] = '';
        }
        
        if (isset($this->request->post['sender'])) {
            $data['sender'] = $this->request->post['sender'];
        } else {
            $data['sender'] = $this->user->getUserName();
        }
        
        if (isset($this->request->post['recipient'])) {
            $data['recipient'] = $this->request->post['recipient'];
        } else {
            $data['recipient'] = '';
        }
        
        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } else {
            $data['status'] = 1; // Default to sent
        }
        
        // Load users for recipient dropdown
        $this->load->model('user/user');
        
        $data['users'] = $this->model_user_user->getUsers();
        
        // For attachments
        $data['user_token'] = $this->session->data['user_token'];
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('tool/messaging_form', $data));
    }
    
    protected function getViewForm() {
        $message_id = $this->request->get['message_id'];
        
        $message_info = $this->model_tool_messaging->getMessage($message_id);
        
        $data['message_info'] = $message_info;
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->error['reply'])) {
            $data['error_reply'] = $this->error['reply'];
        } else {
            $data['error_reply'] = '';
        }
        
        $url = '';
        
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_view'),
            'href' => $this->url->link('tool/messaging/view', 'user_token=' . $this->session->data['user_token'] . '&message_id=' . $message_id . $url, true)
        );
        
        $data['action'] = $this->url->link('tool/messaging/view', 'user_token=' . $this->session->data['user_token'] . '&message_id=' . $message_id . $url, true);
        $data['cancel'] = $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        $data['user_token'] = $this->session->data['user_token'];
        
        // Check if user has a draft for this message
        $draft_info = $this->model_tool_messaging->getDraft($message_id);
        
        if ($draft_info) {
            $data['reply'] = $draft_info['draft'];
        } else {
            $data['reply'] = '';
        }
        
        // Get message history
        $data['history'] = $this->model_tool_messaging->getMessageHistory($message_id);
        
        $data['sender'] = $this->user->getUserName();
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('tool/messaging_view', $data));
    }
    
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'tool/messaging')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if ((utf8_strlen($this->request->post['subject']) < 3) || (utf8_strlen($this->request->post['subject']) > 255)) {
            $this->error['subject'] = $this->language->get('error_subject');
        }
        
        if (utf8_strlen($this->request->post['message']) < 10) {
            $this->error['message'] = $this->language->get('error_message');
        }
        
        return !$this->error;
    }
    
    protected function validateReplyForm() {
        if (!$this->user->hasPermission('modify', 'tool/messaging')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (isset($this->request->post['action']) && $this->request->post['action'] == 'reply') {
            if (utf8_strlen($this->request->post['reply']) < 10) {
                $this->error['reply'] = $this->language->get('error_reply');
            }
        }
        
        return !$this->error;
    }
    
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'tool/messaging')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }

    public function createConversation() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'tool/messaging')) {
            $json['error'] = $this->language->get('error_permission');
        }
        
        if (!isset($this->request->post['title']) || utf8_strlen($this->request->post['title']) < 3) {
            $json['error'] = $this->language->get('error_title');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('tool/messaging');
            
            $data = array(
                'title' => $this->request->post['title'],
                'creator_id' => $this->user->getId(),
                'is_group' => isset($this->request->post['is_group']) ? 1 : 0
            );
            
            $conversation_id = $this->model_tool_messaging->createConversation($data);
            
            // Add creator as member and admin
            $this->model_tool_messaging->addConversationMember(array(
                'conversation_id' => $conversation_id,
                'user_id' => $this->user->getId(),
                'is_admin' => 1
            ));
            
            // Add members if specified
            if (isset($this->request->post['members']) && is_array($this->request->post['members'])) {
                foreach ($this->request->post['members'] as $user_id) {
                    $this->model_tool_messaging->addConversationMember(array(
                        'conversation_id' => $conversation_id,
                        'user_id' => (int)$user_id,
                        'is_admin' => 0
                    ));
                }
            }
            
            $json['success'] = $this->language->get('text_conversation_created');
            $json['conversation_id'] = $conversation_id;
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function editConversation() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'tool/messaging')) {
            $json['error'] = $this->language->get('error_permission');
        }
        
        if (!isset($this->request->post['conversation_id']) || !$this->request->post['conversation_id']) {
            $json['error'] = $this->language->get('error_conversation');
        }
        
        if (!isset($this->request->post['title']) || utf8_strlen($this->request->post['title']) < 3) {
            $json['error'] = $this->language->get('error_title');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('tool/messaging');
            
            $conversation_id = (int)$this->request->post['conversation_id'];
            
            // Check if user is admin of this conversation
            if (!$this->model_tool_messaging->isConversationAdmin($conversation_id, $this->user->getId())) {
                $json['error'] = $this->language->get('error_permission');
            } else {
                $data = array(
                    'title' => $this->request->post['title'],
                    'is_group' => isset($this->request->post['is_group']) ? 1 : 0
                );
                
                $this->model_tool_messaging->updateConversation($conversation_id, $data);
                
                $json['success'] = $this->language->get('text_conversation_updated');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function deleteConversation() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'tool/messaging')) {
            $json['error'] = $this->language->get('error_permission');
        }
        
        if (!isset($this->request->post['conversation_id']) || !$this->request->post['conversation_id']) {
            $json['error'] = $this->language->get('error_conversation');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('tool/messaging');
            
            $conversation_id = (int)$this->request->post['conversation_id'];
            
            // Check if user is admin of this conversation
            if (!$this->model_tool_messaging->isConversationAdmin($conversation_id, $this->user->getId())) {
                $json['error'] = $this->language->get('error_permission');
            } else {
                $this->model_tool_messaging->deleteConversation($conversation_id);
                
                $json['success'] = $this->language->get('text_conversation_deleted');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getConversation() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        if (!isset($this->request->get['conversation_id']) || !$this->request->get['conversation_id']) {
            $json['error'] = $this->language->get('error_conversation');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('tool/messaging');
            
            $conversation_id = (int)$this->request->get['conversation_id'];
            
            // Check if user is member of this conversation
            if (!$this->model_tool_messaging->isConversationMember($conversation_id, $this->user->getId())) {
                $json['error'] = $this->language->get('error_permission');
            } else {
                $conversation = $this->model_tool_messaging->getConversation($conversation_id);
                $members = $this->model_tool_messaging->getConversationMembers($conversation_id);
                $message_count = $this->model_tool_messaging->getConversationMessageCount($conversation_id);
                
                $json['conversation'] = $conversation;
                $json['members'] = $members;
                $json['message_count'] = $message_count;
                $json['is_admin'] = $this->model_tool_messaging->isConversationAdmin($conversation_id, $this->user->getId());
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getConversationMessages() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        if (!isset($this->request->get['conversation_id']) || !$this->request->get['conversation_id']) {
            $json['error'] = $this->language->get('error_conversation');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('tool/messaging');
            
            $conversation_id = (int)$this->request->get['conversation_id'];
            
            // Check if user is member of this conversation
            if (!$this->model_tool_messaging->isConversationMember($conversation_id, $this->user->getId())) {
                $json['error'] = $this->language->get('error_permission');
            } else {
                $start = isset($this->request->get['start']) ? (int)$this->request->get['start'] : 0;
                $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 20;
                
                $messages = $this->model_tool_messaging->getConversationMessages($conversation_id, $start, $limit);
                $message_count = $this->model_tool_messaging->getConversationMessageCount($conversation_id);
                
                // Process messages to add attachment info and format dates
                foreach ($messages as &$message) {
                    $message['date_added_formatted'] = date($this->language->get('date_format_short'), strtotime($message['date_added']));
                    
                    if ($message['has_attachment']) {
                        $message_info = $this->model_tool_messaging->getMessage($message['message_id']);
                        $message['attachments'] = $message_info['attachments'];
                    } else {
                        $message['attachments'] = array();
                    }
                }
                
                $json['messages'] = $messages;
                $json['total'] = $message_count;
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function addMember() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'tool/messaging')) {
            $json['error'] = $this->language->get('error_permission');
        }
        
        if (!isset($this->request->post['conversation_id']) || !$this->request->post['conversation_id']) {
            $json['error'] = $this->language->get('error_conversation');
        }
        
        if (!isset($this->request->post['user_id']) || !$this->request->post['user_id']) {
            $json['error'] = $this->language->get('error_user');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('tool/messaging');
            
            $conversation_id = (int)$this->request->post['conversation_id'];
            $user_id = (int)$this->request->post['user_id'];
            
            // Check if user is admin of this conversation
            if (!$this->model_tool_messaging->isConversationAdmin($conversation_id, $this->user->getId())) {
                $json['error'] = $this->language->get('error_permission');
            } else {
                // Check if user exists
                $this->load->model('user/user');
                $user_info = $this->model_user_user->getUser($user_id);
                
                if (!$user_info) {
                    $json['error'] = $this->language->get('error_user_exists');
                } else {
                    $this->model_tool_messaging->addConversationMember(array(
                        'conversation_id' => $conversation_id,
                        'user_id' => $user_id,
                        'is_admin' => isset($this->request->post['is_admin']) ? 1 : 0
                    ));
                    
                    // Get updated members list
                    $members = $this->model_tool_messaging->getConversationMembers($conversation_id);
                    
                    $json['success'] = $this->language->get('text_member_added');
                    $json['members'] = $members;
                    
                    // Send notification to the user
                    $conversation = $this->model_tool_messaging->getConversation($conversation_id);
                    
                    $this->model_tool_messaging->addNotification(array(
                        'user_id' => $user_id,
                        'conversation_id' => $conversation_id,
                        'type' => 'conversation_added',
                        'title' => $this->language->get('text_added_to_conversation'),
                        'message' => sprintf($this->language->get('text_added_to_conversation_message'), $this->user->getUserName(), $conversation['title'])
                    ));
                }
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function removeMember() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'tool/messaging')) {
            $json['error'] = $this->language->get('error_permission');
        }
        
        if (!isset($this->request->post['conversation_id']) || !$this->request->post['conversation_id']) {
            $json['error'] = $this->language->get('error_conversation');
        }
        
        if (!isset($this->request->post['user_id']) || !$this->request->post['user_id']) {
            $json['error'] = $this->language->get('error_user');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('tool/messaging');
            
            $conversation_id = (int)$this->request->post['conversation_id'];
            $user_id = (int)$this->request->post['user_id'];
            
            // Check if user is admin of this conversation
            if (!$this->model_tool_messaging->isConversationAdmin($conversation_id, $this->user->getId())) {
                $json['error'] = $this->language->get('error_permission');
            } else {
                // Don't allow removing the last admin
                $members = $this->model_tool_messaging->getConversationMembers($conversation_id);
                $admin_count = 0;
                
                foreach ($members as $member) {
                    if ($member['is_admin']) {
                        $admin_count++;
                    }
                }
                
                // Check if trying to remove the last admin
                if ($admin_count == 1 && $user_id == $this->user->getId()) {
                    $json['error'] = $this->language->get('error_last_admin');
                } else {
                    $this->model_tool_messaging->removeConversationMember($conversation_id, $user_id);
                    
                    // Get updated members list
                    $members = $this->model_tool_messaging->getConversationMembers($conversation_id);
                    
                    $json['success'] = $this->language->get('text_member_removed');
                    $json['members'] = $members;
                    
                    // Send notification to the user
                    $conversation = $this->model_tool_messaging->getConversation($conversation_id);
                    
                    $this->model_tool_messaging->addNotification(array(
                        'user_id' => $user_id,
                        'conversation_id' => $conversation_id,
                        'type' => 'conversation_removed',
                        'title' => $this->language->get('text_removed_from_conversation'),
                        'message' => sprintf($this->language->get('text_removed_from_conversation_message'), $conversation['title'])
                    ));
                }
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getNotifications() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        $this->load->model('tool/messaging');
        
        $start = isset($this->request->get['start']) ? (int)$this->request->get['start'] : 0;
        $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 10;
        
        $notifications = $this->model_tool_messaging->getNotifications($this->user->getId(), $start, $limit);
        $total = $this->model_tool_messaging->getUnreadNotificationCount($this->user->getId());
        
        // Format notifications
        foreach ($notifications as &$notification) {
            $notification['date_added_formatted'] = date($this->language->get('date_format_short'), strtotime($notification['date_added']));
            
            // Add view links based on notification type
            if ($notification['message_id'] > 0) {
                $notification['view'] = $this->url->link('tool/messaging/view', 'user_token=' . $this->session->data['user_token'] . '&message_id=' . $notification['message_id'], true);
            } elseif ($notification['conversation_id'] > 0) {
                $notification['view'] = $this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'] . '&filter_conversation_id=' . $notification['conversation_id'], true);
            } else {
                $notification['view'] = '';
            }
        }
        
        $json['notifications'] = $notifications;
        $json['total'] = $total;
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function markNotificationRead() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        if (!isset($this->request->post['notification_id']) || !$this->request->post['notification_id']) {
            $json['error'] = $this->language->get('error_notification');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('tool/messaging');
            
            $notification_id = (int)$this->request->post['notification_id'];
            
            $this->model_tool_messaging->markNotificationRead($notification_id);
            
            $json['success'] = $this->language->get('text_notification_read');
            $json['total'] = $this->model_tool_messaging->getUnreadNotificationCount($this->user->getId());
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function markAllNotificationsRead() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        $this->load->model('tool/messaging');
        
        $this->model_tool_messaging->markAllNotificationsRead($this->user->getId());
        
        $json['success'] = $this->language->get('text_all_notifications_read');
        $json['total'] = 0;
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function deleteNotification() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        if (!isset($this->request->post['notification_id']) || !$this->request->post['notification_id']) {
            $json['error'] = $this->language->get('error_notification');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('tool/messaging');
            
            $notification_id = (int)$this->request->post['notification_id'];
            
            $this->model_tool_messaging->deleteNotification($notification_id);
            
            $json['success'] = $this->language->get('text_notification_deleted');
            $json['total'] = $this->model_tool_messaging->getUnreadNotificationCount($this->user->getId());
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function markAsRead() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        if (!isset($this->request->post['message_id']) || !$this->request->post['message_id']) {
            $json['error'] = $this->language->get('error_message');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('tool/messaging');
            
            $message_id = (int)$this->request->post['message_id'];
            
            $this->model_tool_messaging->markMessageAsRead($message_id);
            
            $json['success'] = $this->language->get('text_marked_read');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function markAsUnread() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        if (!isset($this->request->post['message_id']) || !$this->request->post['message_id']) {
            $json['error'] = $this->language->get('error_message');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('tool/messaging');
            
            $message_id = (int)$this->request->post['message_id'];
            
            $this->model_tool_messaging->markMessageAsUnread($message_id);
            
            $json['success'] = $this->language->get('text_marked_unread');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function archive() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        if (!isset($this->request->post['message_id']) || !$this->request->post['message_id']) {
            $json['error'] = $this->language->get('error_message');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('tool/messaging');
            
            $message_id = (int)$this->request->post['message_id'];
            
            $this->model_tool_messaging->archiveMessage($message_id);
            
            $json['success'] = $this->language->get('text_archived');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function unarchive() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        if (!isset($this->request->post['message_id']) || !$this->request->post['message_id']) {
            $json['error'] = $this->language->get('error_message');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('tool/messaging');
            
            $message_id = (int)$this->request->post['message_id'];
            
            $this->model_tool_messaging->unarchiveMessage($message_id);
            
            $json['success'] = $this->language->get('text_unarchived');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function send() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'tool/messaging')) {
            $json['error'] = $this->language->get('error_permission');
        }
        
        if (!isset($this->request->post['to_id']) || !$this->request->post['to_id']) {
            $json['error']['to_id'] = $this->language->get('error_recipient');
        }
        
        if (!isset($this->request->post['subject']) || utf8_strlen($this->request->post['subject']) < 3) {
            $json['error']['subject'] = $this->language->get('error_subject');
        }
        
        if (!isset($this->request->post['message']) || utf8_strlen($this->request->post['message']) < 10) {
            $json['error']['message'] = $this->language->get('error_message');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('tool/messaging');
            
            // Get recipient user info
            $this->load->model('user/user');
            $recipient_info = $this->model_user_user->getUser($this->request->post['to_id']);
            
            if (!$recipient_info) {
                $json['error']['to_id'] = $this->language->get('error_recipient_exists');
            } else {
                $data = array(
                    'subject' => $this->request->post['subject'],
                    'message' => $this->request->post['message'],
                    'sender' => $this->user->getUserName(),
                    'recipient' => $recipient_info['username'],
                    'recipient_id' => $recipient_info['user_id'],
                    'status' => 1, // Sent
                    'priority' => isset($this->request->post['priority']) ? (int)$this->request->post['priority'] : 0
                );
                
                // Process attachments
                if (isset($this->request->post['attachment']) && is_array($this->request->post['attachment'])) {
                    $data['attachment'] = $this->request->post['attachment'];
                }
                
                // Check if this is a reply
                if (isset($this->request->post['parent_id']) && $this->request->post['parent_id']) {
                    $data['parent_id'] = (int)$this->request->post['parent_id'];
                    $this->model_tool_messaging->addReply($data['parent_id'], $data);
                    $json['success'] = $this->language->get('text_reply_sent');
                } else {
                    // New message
                    $message_id = $this->model_tool_messaging->addMessage($data);
                    $json['success'] = $this->language->get('text_message_sent');
                    $json['message_id'] = $message_id;
                }
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function saveDraft() {
        $this->load->language('tool/messaging');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'tool/messaging')) {
            $json['error'] = $this->language->get('error_permission');
        }
        
        if (!isset($this->request->post['subject']) || utf8_strlen($this->request->post['subject']) < 3) {
            $json['error']['subject'] = $this->language->get('error_subject');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('tool/messaging');
            
            $data = array(
                'subject' => $this->request->post['subject'],
                'message' => isset($this->request->post['message']) ? $this->request->post['message'] : '',
                'sender' => $this->user->getUserName(),
                'recipient' => isset($this->request->post['to_id']) ? $this->model_user_user->getUser($this->request->post['to_id'])['username'] : '',
                'recipient_id' => isset($this->request->post['to_id']) ? (int)$this->request->post['to_id'] : 0,
                'status' => 0, // Draft
                'is_draft' => 1,
                'priority' => isset($this->request->post['priority']) ? (int)$this->request->post['priority'] : 0
            );
            
            // Process attachments
            if (isset($this->request->post['attachment']) && is_array($this->request->post['attachment'])) {
                $data['attachment'] = $this->request->post['attachment'];
            }
            
            // Check if editing an existing draft
            if (isset($this->request->post['message_id']) && $this->request->post['message_id']) {
                $message_id = (int)$this->request->post['message_id'];
                $this->model_tool_messaging->updateMessage($message_id, $data);
                $json['success'] = $this->language->get('text_draft_updated');
            } else {
                // New draft
                $message_id = $this->model_tool_messaging->addMessage($data);
                $json['success'] = $this->language->get('text_draft_saved');
                $json['message_id'] = $message_id;
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function download() {
        $this->load->language('tool/messaging');
        
        if (isset($this->request->get['attachment_id'])) {
            $attachment_id = (int)$this->request->get['attachment_id'];
        } else {
            $attachment_id = 0;
        }
        
        $this->load->model('tool/messaging');
        
        $attachment_info = $this->model_tool_messaging->getAttachment($attachment_id);
        
        if ($attachment_info) {
            $file = DIR_UPLOAD . $attachment_info['filepath'];
            
            if (file_exists($file)) {
                header('Content-Type: application/octet-stream');
                header('Content-Description: File Transfer');
                header('Content-Disposition: attachment; filename="' . $attachment_info['mask'] . '"');
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));
                
                readfile($file, 'rb');
                exit;
            } else {
                $this->session->data['error'] = $this->language->get('error_file');
                
                $this->response->redirect($this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->session->data['error'] = $this->language->get('error_attachment');
            
            $this->response->redirect($this->url->link('tool/messaging', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
} 