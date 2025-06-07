<?php
class ControllerCrmContact extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('crm/contact');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('crm/contact');
        $this->load->model('user/user');

        $data['user_token'] = $this->session->data['user_token'];

        // روابط Ajax
        $data['ajax_list_url'] = $this->url->link('crm/contact/list', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_save_url'] = $this->url->link('crm/contact/save', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_get_url']  = $this->url->link('crm/contact/getForm', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_delete_url'] = $this->url->link('crm/contact/delete', 'user_token=' . $this->session->data['user_token'], true);


        // جلب المستخدمين
        $users = $this->model_user_user->getUsers();
        $data['users'] = array();
        foreach ($users as $u) {
            $data['users'][] = array(
                'user_id'   => $u['user_id'],
                'firstname' => $u['firstname'],
                'lastname'  => $u['lastname']
            );
        }

        // النصوص
        $data['heading_title']       = $this->language->get('heading_title');
        $data['text_filter']         = $this->language->get('text_filter');
        $data['text_contact_name']   = $this->language->get('text_contact_name');
        $data['text_enter_contact_name']= $this->language->get('text_enter_contact_name');
        $data['text_status']         = $this->language->get('text_status');
        $data['text_all_statuses']   = $this->language->get('text_all_statuses');
        $data['text_status_active']  = $this->language->get('text_status_active');
        $data['text_status_inactive']= $this->language->get('text_status_inactive');
        $data['button_filter']       = $this->language->get('button_filter');
        $data['button_reset']        = $this->language->get('button_reset');
        $data['button_add_contact']  = $this->language->get('button_add_contact');
        $data['text_contact_list']   = $this->language->get('text_contact_list');
        $data['text_add_contact']    = $this->language->get('text_add_contact');
        $data['text_edit_contact']   = $this->language->get('text_edit_contact');
        $data['text_ajax_error']     = $this->language->get('text_ajax_error');
        $data['text_confirm_delete'] = $this->language->get('text_confirm_delete');
        $data['text_firstname']      = $this->language->get('text_firstname');
        $data['text_lastname']       = $this->language->get('text_lastname');
        $data['text_email']          = $this->language->get('text_email');
        $data['text_phone']          = $this->language->get('text_phone');
        $data['text_position']       = $this->language->get('text_position');
        $data['text_assigned_to']    = $this->language->get('text_assigned_to');
        $data['text_select_user']    = $this->language->get('text_select_user');
        $data['text_notes']          = $this->language->get('text_notes');
        $data['button_close']        = $this->language->get('button_close');
        $data['button_save']         = $this->language->get('button_save');

        $data['column_name']         = $this->language->get('column_name');
        $data['column_email']        = $this->language->get('column_email');
        $data['column_phone']        = $this->language->get('column_phone');
        $data['column_position']     = $this->language->get('column_position');
        $data['column_status']       = $this->language->get('column_status');
        $data['column_actions']      = $this->language->get('column_actions');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard','user_token=' . $this->session->data['user_token'],true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_crm'),
            'href' => ''
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('crm/contact','user_token=' . $this->session->data['user_token'],true)
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/contact_list', $data));
    }

    public function list() {
        $this->load->language('crm/contact');
        $this->load->model('crm/contact');

        $filter_name = isset($this->request->post['filter_name']) ? $this->request->post['filter_name'] : '';
        $filter_status = isset($this->request->post['filter_status']) ? $this->request->post['filter_status'] : '';

        $start  = isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0;
        $length = isset($this->request->post['length']) ? (int)$this->request->post['length'] : 10;
        $draw   = isset($this->request->post['draw']) ? (int)$this->request->post['draw'] : 1;
        $order_column = isset($this->request->post['order'][0]['column']) ? (int)$this->request->post['order'][0]['column'] : 0;
        $order_dir = isset($this->request->post['order'][0]['dir']) ? $this->request->post['order'][0]['dir'] : 'asc';

        $columns = array('firstname','email','phone','position','status');
        $sort = isset($columns[$order_column]) ? $columns[$order_column] : 'firstname';

        $filter_data = array(
            'filter_name'   => $filter_name,
            'filter_status' => $filter_status,
            'start'         => $start,
            'limit'         => $length,
            'sort'          => $sort,
            'order'         => $order_dir
        );

        $total = $this->model_crm_contact->getTotalContacts($filter_data);
        $results = $this->model_crm_contact->getContacts($filter_data);

        $data = array();
        foreach ($results as $result) {
            $actions = '';
            if ($this->user->hasPermission('modify', 'crm/contact')) {
                $actions .= '<button class="btn btn-primary btn-sm btn-edit" data-id="'. $result['contact_id'] .'"><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm btn-delete" data-id="'. $result['contact_id'] .'"><i class="fa fa-trash"></i></button>';
            } else {
                $actions .= '<button class="btn btn-primary btn-sm" disabled><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm" disabled><i class="fa fa-trash"></i></button>';
            }

            $fullname = $result['firstname'];
            if ($result['lastname']) {
                $fullname .= ' ' . $result['lastname'];
            }

            $data[] = array(
                'name'     => $fullname,
                'email'    => $result['email'],
                'phone'    => $result['phone'],
                'position' => $result['position'],
                'status'   => $this->language->get('text_status_'.$result['status']),
                'actions'  => $actions
            );
        }

        $json = array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $data
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getForm() {
        $this->load->language('crm/contact');
        $this->load->model('crm/contact');

        $json = array();
        if (isset($this->request->post['contact_id'])) {
            $contact_id = (int)$this->request->post['contact_id'];
            $info = $this->model_crm_contact->getContact($contact_id);

            if ($info) {
                $json['data'] = $info;
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function save() {
        $this->load->language('crm/contact');
        $this->load->model('crm/contact');

        $json = array();

        if (!$this->user->hasPermission('modify', 'crm/contact')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $contact_id = isset($this->request->post['contact_id']) ? (int)$this->request->post['contact_id'] : 0;

            $data = array(
                'firstname'           => $this->request->post['firstname'],
                'lastname'            => $this->request->post['lastname'],
                'email'               => $this->request->post['email'],
                'phone'               => $this->request->post['phone'],
                'position'            => $this->request->post['position'],
                'assigned_to_user_id' => $this->request->post['assigned_to_user_id'],
                'status'              => $this->request->post['status'],
                'notes'               => $this->request->post['notes']
            );

            if (empty($data['firstname'])) {
                $json['error'] = $this->language->get('error_required');
            } else {
                if ($contact_id) {
                    $this->model_crm_contact->editContact($contact_id, $data);
                    $json['success'] = $this->language->get('text_success_edit');
                } else {
                    $this->model_crm_contact->addContact($data);
                    $json['success'] = $this->language->get('text_success_add');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function delete() {
        $this->load->language('crm/contact');
        $this->load->model('crm/contact');

        $json = array();

        if (!$this->user->hasPermission('modify', 'crm/contact')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['contact_id'])) {
                $contact_id = (int)$this->request->post['contact_id'];
                $this->model_crm_contact->deleteContact($contact_id);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
