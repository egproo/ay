<?php
class ControllerCrmDeal extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('crm/deal');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('crm/deal');
        $this->load->model('user/user');

        $data['user_token'] = $this->session->data['user_token'];

        // روابط Ajax
        $data['ajax_list_url'] = $this->url->link('crm/deal/list', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_save_url'] = $this->url->link('crm/deal/save', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_get_url']  = $this->url->link('crm/deal/getForm', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_delete_url'] = $this->url->link('crm/deal/delete', 'user_token=' . $this->session->data['user_token'], true);

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
        $data['heading_title']         = $this->language->get('heading_title');
        $data['text_filter']           = $this->language->get('text_filter');
        $data['text_deal_name']        = $this->language->get('text_deal_name');
        $data['text_enter_deal_name']  = $this->language->get('text_enter_deal_name');
        $data['text_date_start']       = $this->language->get('text_date_start');
        $data['text_date_end']         = $this->language->get('text_date_end');
        $data['button_filter']         = $this->language->get('button_filter');
        $data['button_reset']          = $this->language->get('button_reset');
        $data['button_add_deal']       = $this->language->get('button_add_deal');
        $data['text_deal_list']        = $this->language->get('text_deal_list');
        $data['text_add_deal']         = $this->language->get('text_add_deal');
        $data['text_edit_deal']        = $this->language->get('text_edit_deal');
        $data['text_ajax_error']       = $this->language->get('text_ajax_error');
        $data['text_confirm_delete']   = $this->language->get('text_confirm_delete');
        $data['text_name']             = $this->language->get('text_name');
        $data['text_stage']            = $this->language->get('text_stage');
        $data['text_stage_qualification'] = $this->language->get('text_stage_qualification');
        $data['text_stage_proposal']   = $this->language->get('text_stage_proposal');
        $data['text_stage_negotiation']= $this->language->get('text_stage_negotiation');
        $data['text_stage_closed_won'] = $this->language->get('text_stage_closed_won');
        $data['text_stage_closed_lost']= $this->language->get('text_stage_closed_lost');
        $data['text_probability']      = $this->language->get('text_probability');
        $data['text_amount']           = $this->language->get('text_amount');
        $data['text_expected_close_date']= $this->language->get('text_expected_close_date');
        $data['text_assigned_to']      = $this->language->get('text_assigned_to');
        $data['text_select_user']      = $this->language->get('text_select_user');
        $data['text_status']           = $this->language->get('text_status');
        $data['text_status_open']      = $this->language->get('text_status_open');
        $data['text_status_closed']    = $this->language->get('text_status_closed');
        $data['text_status_on_hold']   = $this->language->get('text_status_on_hold');
        $data['text_notes']            = $this->language->get('text_notes');
        $data['button_close']          = $this->language->get('button_close');
        $data['button_save']           = $this->language->get('button_save');

        $data['column_name']           = $this->language->get('column_name');
        $data['column_stage']          = $this->language->get('column_stage');
        $data['column_amount']         = $this->language->get('column_amount');
        $data['column_probability']    = $this->language->get('column_probability');
        $data['column_status']         = $this->language->get('column_status');
        $data['column_actions']        = $this->language->get('column_actions');

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
            'href' => $this->url->link('crm/deal','user_token=' . $this->session->data['user_token'],true)
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/deal_list', $data));
    }

    public function list() {
        $this->load->language('crm/deal');
        $this->load->model('crm/deal');

        $filter_name = isset($this->request->post['filter_name']) ? $this->request->post['filter_name'] : '';
        $filter_date_start = isset($this->request->post['filter_date_start']) ? $this->request->post['filter_date_start'] : '';
        $filter_date_end = isset($this->request->post['filter_date_end']) ? $this->request->post['filter_date_end'] : '';

        $start  = isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0;
        $length = isset($this->request->post['length']) ? (int)$this->request->post['length'] : 10;
        $draw   = isset($this->request->post['draw']) ? (int)$this->request->post['draw'] : 1;
        $order_column = isset($this->request->post['order'][0]['column']) ? (int)$this->request->post['order'][0]['column'] : 0;
        $order_dir = isset($this->request->post['order'][0]['dir']) ? $this->request->post['order'][0]['dir'] : 'asc';

        $columns = array('name','stage','amount','probability','status');
        $sort = isset($columns[$order_column]) ? $columns[$order_column] : 'name';

        $filter_data = array(
            'filter_name'       => $filter_name,
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end,
            'start'             => $start,
            'limit'             => $length,
            'sort'              => $sort,
            'order'             => $order_dir
        );

        $total = $this->model_crm_deal->getTotalDeals($filter_data);
        $results = $this->model_crm_deal->getDeals($filter_data);

        $data = array();
        foreach ($results as $result) {
            $actions = '';
            if ($this->user->hasPermission('modify', 'crm/deal')) {
                $actions .= '<button class="btn btn-primary btn-sm btn-edit" data-id="'. $result['deal_id'] .'"><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm btn-delete" data-id="'. $result['deal_id'] .'"><i class="fa fa-trash"></i></button>';
            } else {
                $actions .= '<button class="btn btn-primary btn-sm" disabled><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm" disabled><i class="fa fa-trash"></i></button>';
            }

            $data[] = array(
                'name'       => $result['name'],
                'stage'      => $this->language->get('text_stage_'.$result['stage']),
                'amount'     => $result['amount'],
                'probability'=> $result['probability'].'%',
                'status'     => $this->language->get('text_status_'.$result['status']),
                'actions'    => $actions
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
        $this->load->language('crm/deal');
        $this->load->model('crm/deal');

        $json = array();
        if (isset($this->request->post['deal_id'])) {
            $deal_id = (int)$this->request->post['deal_id'];
            $info = $this->model_crm_deal->getDeal($deal_id);

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
        $this->load->language('crm/deal');
        $this->load->model('crm/deal');

        $json = array();

        if (!$this->user->hasPermission('modify', 'crm/deal')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $deal_id = isset($this->request->post['deal_id']) ? (int)$this->request->post['deal_id'] : 0;

            $data = array(
                'name'               => $this->request->post['name'],
                'stage'              => $this->request->post['stage'],
                'probability'        => $this->request->post['probability'],
                'amount'             => $this->request->post['amount'],
                'expected_close_date'=> $this->request->post['expected_close_date'],
                'assigned_to_user_id'=> $this->request->post['assigned_to_user_id'],
                'status'             => $this->request->post['status'],
                'notes'              => $this->request->post['notes']
            );

            if (empty($data['name'])) {
                $json['error'] = $this->language->get('error_required');
            } else {
                if ($deal_id) {
                    $this->model_crm_deal->editDeal($deal_id, $data);
                    $json['success'] = $this->language->get('text_success_edit');
                } else {
                    $this->model_crm_deal->addDeal($data);
                    $json['success'] = $this->language->get('text_success_add');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function delete() {
        $this->load->language('crm/deal');
        $this->load->model('crm/deal');

        $json = array();

        if (!$this->user->hasPermission('modify', 'crm/deal')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['deal_id'])) {
                $deal_id = (int)$this->request->post['deal_id'];
                $this->model_crm_deal->deleteDeal($deal_id);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
