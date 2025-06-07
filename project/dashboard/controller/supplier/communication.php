<?php
/**
 * AYM ERP - Supplier Communication Controller
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ControllerSupplierCommunication extends Controller {

    private $error = array();

    public function index() {
        $this->load->language('supplier/communication');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['add'] = $this->url->link('supplier/communication/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('supplier/communication/delete', 'user_token=' . $this->session->data['user_token'], true);

        $this->getList($data);
    }

    public function add() {
        $this->load->language('supplier/communication');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('supplier/communication');

            $communication_id = $this->model_supplier_communication->addCommunication($this->request->post);

            // Handle file attachments
            if (isset($this->request->files['attachments'])) {
                $this->uploadAttachments($communication_id, $this->request->files['attachments']);
            }

            // Send notification if required
            if (isset($this->request->post['send_notification']) && $this->request->post['send_notification']) {
                $this->sendNotification($communication_id);
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

            $this->response->redirect($this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('supplier/communication');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('supplier/communication');

            $this->model_supplier_communication->editCommunication($this->request->get['communication_id'], $this->request->post);

            // Handle file attachments
            if (isset($this->request->files['attachments'])) {
                $this->uploadAttachments($this->request->get['communication_id'], $this->request->files['attachments']);
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

            $this->response->redirect($this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('supplier/communication');

        $this->document->setTitle($this->language->get('heading_title'));

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            $this->load->model('supplier/communication');

            foreach ($this->request->post['selected'] as $communication_id) {
                $this->model_supplier_communication->deleteCommunication($communication_id);
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

            $this->response->redirect($this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function view() {
        $this->load->language('supplier/communication');
        $this->load->model('supplier/communication');

        if (isset($this->request->get['communication_id'])) {
            $communication_id = $this->request->get['communication_id'];
            $communication_info = $this->model_supplier_communication->getCommunication($communication_id);

            if ($communication_info) {
                $this->document->setTitle($this->language->get('heading_title') . ' - ' . $communication_info['subject']);

                $data['breadcrumbs'] = array();

                $data['breadcrumbs'][] = array(
                    'text' => $this->language->get('text_home'),
                    'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
                );

                $data['breadcrumbs'][] = array(
                    'text' => $this->language->get('heading_title'),
                    'href' => $this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'], true)
                );

                $data['breadcrumbs'][] = array(
                    'text' => $communication_info['subject'],
                    'href' => $this->url->link('supplier/communication/view', 'user_token=' . $this->session->data['user_token'] . '&communication_id=' . $communication_id, true)
                );

                $data['communication'] = $communication_info;
                $data['attachments'] = $this->model_supplier_communication->getCommunicationAttachments($communication_id);
                $data['replies'] = $this->model_supplier_communication->getCommunicationReplies($communication_id);

                // Mark as read
                $this->model_supplier_communication->markAsRead($communication_id, $this->user->getId());

                $data['reply'] = $this->url->link('supplier/communication/reply', 'user_token=' . $this->session->data['user_token'] . '&communication_id=' . $communication_id, true);
                $data['edit'] = $this->url->link('supplier/communication/edit', 'user_token=' . $this->session->data['user_token'] . '&communication_id=' . $communication_id, true);
                $data['back'] = $this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'], true);

                $data['user_token'] = $this->session->data['user_token'];

                $data['header'] = $this->load->controller('common/header');
                $data['column_left'] = $this->load->controller('common/column_left');
                $data['footer'] = $this->load->controller('common/footer');

                $this->response->setOutput($this->load->view('supplier/communication_view', $data));
            } else {
                $this->response->redirect($this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    public function reply() {
        $this->load->language('supplier/communication');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateReply()) {
            $this->load->model('supplier/communication');

            $reply_id = $this->model_supplier_communication->addReply($this->request->post);

            // Handle file attachments
            if (isset($this->request->files['attachments'])) {
                $this->uploadAttachments($reply_id, $this->request->files['attachments'], 'reply');
            }

            // Send notification
            $this->sendReplyNotification($reply_id);

            $this->session->data['success'] = $this->language->get('text_success_reply');

            $this->response->redirect($this->url->link('supplier/communication/view', 'user_token=' . $this->session->data['user_token'] . '&communication_id=' . $this->request->post['communication_id'], true));
        }

        $this->getReplyForm();
    }

    public function dashboard() {
        $this->load->language('supplier/communication');
        $this->load->model('supplier/communication');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Get communication statistics
        $data['communication_stats'] = $this->model_supplier_communication->getCommunicationStatistics();

        // Get recent communications
        $data['recent_communications'] = $this->model_supplier_communication->getRecentCommunications(10);

        // Get unread communications
        $data['unread_communications'] = $this->model_supplier_communication->getUnreadCommunications($this->user->getId());

        // Get communication trends
        $data['communication_trends'] = $this->model_supplier_communication->getCommunicationTrends();

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/communication_dashboard', $data));
    }

    protected function getList(&$data = array()) {
        if (isset($this->request->get['filter_subject'])) {
            $filter_subject = $this->request->get['filter_subject'];
        } else {
            $filter_subject = '';
        }

        if (isset($this->request->get['filter_supplier_id'])) {
            $filter_supplier_id = $this->request->get['filter_supplier_id'];
        } else {
            $filter_supplier_id = '';
        }

        if (isset($this->request->get['filter_communication_type'])) {
            $filter_communication_type = $this->request->get['filter_communication_type'];
        } else {
            $filter_communication_type = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['filter_priority'])) {
            $filter_priority = $this->request->get['filter_priority'];
        } else {
            $filter_priority = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'sc.date_added';
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

        $url = '';

        if (isset($this->request->get['filter_subject'])) {
            $url .= '&filter_subject=' . urlencode(html_entity_decode($this->request->get['filter_subject'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_supplier_id'])) {
            $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
        }

        if (isset($this->request->get['filter_communication_type'])) {
            $url .= '&filter_communication_type=' . $this->request->get['filter_communication_type'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_priority'])) {
            $url .= '&filter_priority=' . $this->request->get['filter_priority'];
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

        $data['communications'] = array();

        $filter_data = array(
            'filter_subject'            => $filter_subject,
            'filter_supplier_id'        => $filter_supplier_id,
            'filter_communication_type' => $filter_communication_type,
            'filter_status'             => $filter_status,
            'filter_priority'           => $filter_priority,
            'sort'                      => $sort,
            'order'                     => $order,
            'start'                     => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'                     => $this->config->get('config_limit_admin')
        );

        $this->load->model('supplier/communication');

        $communication_total = $this->model_supplier_communication->getTotalCommunications($filter_data);

        $results = $this->model_supplier_communication->getCommunications($filter_data);

        foreach ($results as $result) {
            $data['communications'][] = array(
                'communication_id'    => $result['communication_id'],
                'subject'             => $result['subject'],
                'supplier_name'       => $result['supplier_name'],
                'communication_type'  => $result['communication_type'],
                'priority'            => $result['priority'],
                'status'              => $result['status'],
                'created_by_name'     => $result['created_by_name'],
                'date_added'          => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'is_read'             => $result['is_read'],
                'reply_count'         => $result['reply_count'],
                'view'                => $this->url->link('supplier/communication/view', 'user_token=' . $this->session->data['user_token'] . '&communication_id=' . $result['communication_id'] . $url, true),
                'edit'                => $this->url->link('supplier/communication/edit', 'user_token=' . $this->session->data['user_token'] . '&communication_id=' . $result['communication_id'] . $url, true)
            );
        }

        $data['user_token'] = $this->session->data['user_token'];

        // Load suppliers for filter
        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        // Communication types
        $data['communication_types'] = $this->model_supplier_communication->getCommunicationTypes();

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

        if (isset($this->request->get['filter_subject'])) {
            $url .= '&filter_subject=' . urlencode(html_entity_decode($this->request->get['filter_subject'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_supplier_id'])) {
            $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
        }

        if (isset($this->request->get['filter_communication_type'])) {
            $url .= '&filter_communication_type=' . $this->request->get['filter_communication_type'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_priority'])) {
            $url .= '&filter_priority=' . $this->request->get['filter_priority'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_subject'] = $this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'] . '&sort=sc.subject' . $url, true);
        $data['sort_supplier'] = $this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'] . '&sort=s.name' . $url, true);
        $data['sort_type'] = $this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'] . '&sort=sc.communication_type' . $url, true);
        $data['sort_priority'] = $this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'] . '&sort=sc.priority' . $url, true);
        $data['sort_status'] = $this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'] . '&sort=sc.status' . $url, true);
        $data['sort_date_added'] = $this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'] . '&sort=sc.date_added' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_subject'])) {
            $url .= '&filter_subject=' . urlencode(html_entity_decode($this->request->get['filter_subject'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_supplier_id'])) {
            $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
        }

        if (isset($this->request->get['filter_communication_type'])) {
            $url .= '&filter_communication_type=' . $this->request->get['filter_communication_type'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_priority'])) {
            $url .= '&filter_priority=' . $this->request->get['filter_priority'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $communication_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($communication_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($communication_total - $this->config->get('config_limit_admin'))) ? $communication_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $communication_total, ceil($communication_total / $this->config->get('config_limit_admin')));

        $data['filter_subject'] = $filter_subject;
        $data['filter_supplier_id'] = $filter_supplier_id;
        $data['filter_communication_type'] = $filter_communication_type;
        $data['filter_status'] = $filter_status;
        $data['filter_priority'] = $filter_priority;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/communication_list', $data));
    }

    public function search() {
        $this->load->language('supplier/communication');
        $this->load->model('supplier/communication');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $filter_data = array(
                'search_text' => $this->request->post['search_text'] ?? '',
                'supplier_ids' => $this->request->post['supplier_ids'] ?? array(),
                'communication_types' => $this->request->post['communication_types'] ?? array(),
                'statuses' => $this->request->post['statuses'] ?? array(),
                'priorities' => $this->request->post['priorities'] ?? array(),
                'directions' => $this->request->post['directions'] ?? array(),
                'date_range' => $this->request->post['date_range'] ?? '',
                'date_from' => $this->request->post['date_from'] ?? '',
                'date_to' => $this->request->post['date_to'] ?? '',
                'has_attachments' => $this->request->post['has_attachments'] ?? '',
                'has_follow_up' => $this->request->post['has_follow_up'] ?? '',
                'is_overdue' => $this->request->post['is_overdue'] ?? '',
                'is_confidential' => $this->request->post['is_confidential'] ?? '',
                'sort' => $this->request->post['sort'] ?? 'communication_date',
                'order' => $this->request->post['order'] ?? 'DESC',
                'start' => $this->request->post['start'] ?? 0,
                'limit' => $this->request->post['limit'] ?? 25
            );

            try {
                $results = $this->model_supplier_communication->searchCommunications($filter_data);
                $total = $this->model_supplier_communication->getTotalSearchResults($filter_data);

                $data = array();
                foreach ($results as $result) {
                    $data[] = array(
                        'communication_id' => $result['communication_id'],
                        'subject' => $result['subject'],
                        'supplier_name' => $result['supplier_name'],
                        'communication_type' => $result['communication_type_name'],
                        'direction' => $result['direction_name'],
                        'priority' => $result['priority_name'],
                        'status' => $result['status_name'],
                        'communication_date' => date('Y-m-d', strtotime($result['communication_date'])),
                        'communication_time' => $result['communication_time'],
                        'created_by' => $result['created_by_name'],
                        'attachment_count' => $result['attachment_count'],
                        'participant_count' => $result['participant_count'],
                        'view' => $this->url->link('supplier/communication/view', 'user_token=' . $this->session->data['user_token'] . '&communication_id=' . $result['communication_id'], true),
                        'edit' => $this->url->link('supplier/communication/edit', 'user_token=' . $this->session->data['user_token'] . '&communication_id=' . $result['communication_id'], true)
                    );
                }

                $json['success'] = true;
                $json['data'] = $data;
                $json['total'] = $total;

            } catch (Exception $e) {
                $json['error'] = 'خطأ في البحث: ' . $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function export() {
        $this->load->language('supplier/communication');
        $this->load->model('supplier/communication');

        if (!$this->user->hasPermission('access', 'supplier/communication')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $filter_data = array(
            'search_text' => $this->request->get['search_text'] ?? '',
            'supplier_ids' => $this->request->get['supplier_ids'] ?? array(),
            'communication_types' => $this->request->get['communication_types'] ?? array(),
            'statuses' => $this->request->get['statuses'] ?? array(),
            'priorities' => $this->request->get['priorities'] ?? array(),
            'directions' => $this->request->get['directions'] ?? array(),
            'date_from' => $this->request->get['date_from'] ?? '',
            'date_to' => $this->request->get['date_to'] ?? ''
        );

        $format = $this->request->get['format'] ?? 'csv';

        try {
            $export_data = $this->model_supplier_communication->exportCommunications($filter_data, $format);

            $filename = 'supplier_communications_' . date('Y-m-d_H-i-s') . '.' . $format;

            if ($format == 'csv') {
                $this->response->addHeader('Content-Type: text/csv; charset=utf-8');
                $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
                $this->response->setOutput("\xEF\xBB\xBF" . $export_data); // UTF-8 BOM
            } else {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(array('error' => 'تنسيق التصدير غير مدعوم')));
            }

        } catch (Exception $e) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array('error' => 'خطأ في التصدير: ' . $e->getMessage())));
        }
    }

    public function reports() {
        $this->load->language('supplier/communication');
        $this->load->model('supplier/communication');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $filter_data = array(
                'date_from' => $this->request->post['date_from'] ?? '',
                'date_to' => $this->request->post['date_to'] ?? ''
            );

            try {
                $reports = $this->model_supplier_communication->getCommunicationReports($filter_data);
                $json['success'] = true;
                $json['reports'] = $reports;

            } catch (Exception $e) {
                $json['error'] = 'خطأ في إنشاء التقرير: ' . $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function statistics() {
        $this->load->language('supplier/communication');
        $this->load->model('supplier/communication');

        $json = array();

        try {
            $stats = $this->model_supplier_communication->getDashboardStatistics();
            $json['success'] = true;
            $json['statistics'] = $stats;

        } catch (Exception $e) {
            $json['error'] = 'خطأ في تحميل الإحصائيات: ' . $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function send() {
        $this->load->language('supplier/communication');
        $this->load->model('supplier/communication');

        $json = array();

        if (isset($this->request->post['communication_id'])) {
            $communication_id = $this->request->post['communication_id'];
            $communication_info = $this->model_supplier_communication->getCommunication($communication_id);

            if ($communication_info && $communication_info['communication_type'] == 'email') {
                try {
                    // إرسال الإيميل
                    $this->load->model('mail/mail');

                    $mail_data = array(
                        'to' => $communication_info['contact_email'] ?: $communication_info['supplier_email'],
                        'subject' => $communication_info['subject'],
                        'message' => $communication_info['content'],
                        'from_name' => $this->config->get('config_name'),
                        'from_email' => $this->config->get('config_email')
                    );

                    $result = $this->model_mail_mail->send($mail_data);

                    if ($result) {
                        // تحديث حالة التواصل
                        $this->model_supplier_communication->updateStatus($communication_id, 'completed');

                        $json['success'] = 'تم إرسال الرسالة بنجاح';
                    } else {
                        $json['error'] = 'فشل في إرسال الرسالة';
                    }

                } catch (Exception $e) {
                    $json['error'] = 'خطأ في إرسال الرسالة: ' . $e->getMessage();
                }
            } else {
                $json['error'] = 'التواصل غير موجود أو ليس من نوع البريد الإلكتروني';
            }
        } else {
            $json['error'] = 'معرف التواصل مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function addFollowUp() {
        $this->load->language('supplier/communication');
        $this->load->model('supplier/communication');

        $json = array();

        if (isset($this->request->post['communication_id'])) {
            $communication_id = $this->request->post['communication_id'];
            $notes = $this->request->post['notes'] ?? '';
            $follow_up_date = $this->request->post['follow_up_date'] ?? null;

            try {
                $this->model_supplier_communication->addFollowUp($communication_id, $notes, $follow_up_date);
                $json['success'] = 'تم إضافة المتابعة بنجاح';

            } catch (Exception $e) {
                $json['error'] = 'خطأ في إضافة المتابعة: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف التواصل مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function bulkAction() {
        $this->load->language('supplier/communication');
        $this->load->model('supplier/communication');

        $json = array();

        if (isset($this->request->post['communication_ids']) && is_array($this->request->post['communication_ids'])) {
            $communication_ids = $this->request->post['communication_ids'];
            $action = $this->request->post['action'] ?? '';
            $processed_count = 0;
            $errors = array();

            foreach ($communication_ids as $communication_id) {
                try {
                    switch ($action) {
                        case 'delete':
                            $result = $this->model_supplier_communication->deleteCommunication($communication_id);
                            break;
                        case 'mark_completed':
                            $result = $this->model_supplier_communication->updateStatus($communication_id, 'completed');
                            break;
                        case 'mark_pending':
                            $result = $this->model_supplier_communication->updateStatus($communication_id, 'pending');
                            break;
                        case 'archive':
                            $result = $this->model_supplier_communication->updateStatus($communication_id, 'archived');
                            break;
                        default:
                            $result = false;
                    }

                    if ($result) {
                        $processed_count++;
                    }
                } catch (Exception $e) {
                    $errors[] = 'التواصل رقم ' . $communication_id . ': ' . $e->getMessage();
                }
            }

            if ($processed_count > 0) {
                $json['success'] = 'تم معالجة ' . $processed_count . ' تواصل بنجاح';
            }

            if (!empty($errors)) {
                $json['warnings'] = $errors;
            }

            if ($processed_count == 0 && !empty($errors)) {
                $json['error'] = 'فشل في معالجة جميع التواصلات';
            }

        } else {
            $json['error'] = 'لم يتم تحديد أي تواصلات';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['dashboard'] = $this->url->link('supplier/communication/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/communication_list', $data));
    }
