<?php
class ControllerCrmCampaign extends Controller {
    private $error = array();

    // نفترض حساب مصروف إعلاني account_code=5010 وحساب النقدية 1110
    private $ad_expense_account = 5010;
    private $cash_account = 1110;

    public function index() {
        $this->load->language('crm/campaign');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('crm/campaign');
        $this->load->model('user/user');

        $data['user_token'] = $this->session->data['user_token'];

        // روابط Ajax
        $data['ajax_list_url'] = $this->url->link('crm/campaign/list', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_save_url'] = $this->url->link('crm/campaign/save', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_get_url']  = $this->url->link('crm/campaign/getForm', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_delete_url'] = $this->url->link('crm/campaign/delete', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_period_stats_url'] = $this->url->link('crm/campaign/periodStats', 'user_token=' . $this->session->data['user_token'], true);

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
        $data['text_campaign_name']    = $this->language->get('text_campaign_name');
        $data['text_enter_campaign_name'] = $this->language->get('text_enter_campaign_name');
        $data['text_date_start']       = $this->language->get('text_date_start');
        $data['text_date_end']         = $this->language->get('text_date_end');
        $data['button_filter']         = $this->language->get('button_filter');
        $data['button_reset']          = $this->language->get('button_reset');
        $data['button_add_campaign']   = $this->language->get('button_add_campaign');
        $data['text_campaign_list']    = $this->language->get('text_campaign_list');
        $data['text_add_campaign']     = $this->language->get('text_add_campaign');
        $data['text_edit_campaign']    = $this->language->get('text_edit_campaign');
        $data['text_ajax_error']       = $this->language->get('text_ajax_error');
        $data['text_confirm_delete']   = $this->language->get('text_confirm_delete');
        $data['text_name']             = $this->language->get('text_name');
        $data['text_type']             = $this->language->get('text_type');
        $data['text_type_other']       = $this->language->get('text_type_other');
        $data['text_start_date']       = $this->language->get('text_start_date');
        $data['text_end_date']         = $this->language->get('text_end_date');
        $data['text_budget']           = $this->language->get('text_budget');
        $data['text_code']             = $this->language->get('text_code');
        $data['text_code_help']        = $this->language->get('text_code_help');
        $data['text_status']           = $this->language->get('text_status');
        $data['text_all_statuses']     = $this->language->get('text_all_statuses');
        $data['text_status_active']    = $this->language->get('text_status_active');
        $data['text_status_inactive']  = $this->language->get('text_status_inactive');
        $data['text_status_completed'] = $this->language->get('text_status_completed');
        $data['text_assigned_to']      = $this->language->get('text_assigned_to');
        $data['text_select_user']      = $this->language->get('text_select_user');
        $data['text_actual_spend']     = $this->language->get('text_actual_spend');
        $data['text_invoice_reference']= $this->language->get('text_invoice_reference');
        $data['text_invoice_reference_help'] = $this->language->get('text_invoice_reference_help');
        $data['text_add_expense']      = $this->language->get('text_add_expense');
        $data['text_add_expense_help'] = $this->language->get('text_add_expense_help');
        $data['text_notes']            = $this->language->get('text_notes');
        $data['button_close']          = $this->language->get('button_close');
        $data['button_save']           = $this->language->get('button_save');

        $data['column_name']           = $this->language->get('column_name');
        $data['column_type']           = $this->language->get('column_type');
        $data['column_start_date']     = $this->language->get('column_start_date');
        $data['column_end_date']       = $this->language->get('column_end_date');
        $data['column_budget']         = $this->language->get('column_budget');
        $data['column_status']         = $this->language->get('column_status');
        $data['column_actions']        = $this->language->get('column_actions');

        $data['text_period_stats']     = $this->language->get('text_period_stats');
        $data['text_visits']           = $this->language->get('text_visits');
        $data['text_orders']           = $this->language->get('text_orders');

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
            'href' => $this->url->link('crm/campaign','user_token=' . $this->session->data['user_token'],true)
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/campaign_list', $data));
    }

    public function list() {
        $this->load->language('crm/campaign');
        $this->load->model('crm/campaign');

        $filter_name = isset($this->request->post['filter_name']) ? $this->request->post['filter_name'] : '';
        $filter_date_start = isset($this->request->post['filter_date_start']) ? $this->request->post['filter_date_start'] : '';
        $filter_date_end = isset($this->request->post['filter_date_end']) ? $this->request->post['filter_date_end'] : '';

        $start  = isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0;
        $length = isset($this->request->post['length']) ? (int)$this->request->post['length'] : 10;
        $draw   = isset($this->request->post['draw']) ? (int)$this->request->post['draw'] : 1;
        $order_column = isset($this->request->post['order'][0]['column']) ? (int)$this->request->post['order'][0]['column'] : 0;
        $order_dir = isset($this->request->post['order'][0]['dir']) ? $this->request->post['order'][0]['dir'] : 'asc';

        $columns = array('name','type','start_date','end_date','budget','status');
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

        $total = $this->model_crm_campaign->getTotalCampaigns($filter_data);
        $results = $this->model_crm_campaign->getCampaigns($filter_data);

        $data = array();
        foreach ($results as $result) {
            $actions = '';
            if ($this->user->hasPermission('modify', 'crm/campaign')) {
                $actions .= '<button class="btn btn-primary btn-sm btn-edit" data-id="'. $result['campaign_id'] .'"><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm btn-delete" data-id="'. $result['campaign_id'] .'"><i class="fa fa-trash"></i></button>';
            } else {
                $actions .= '<button class="btn btn-primary btn-sm" disabled><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm" disabled><i class="fa fa-trash"></i></button>';
            }

            $data[] = array(
                'name'       => $result['name'],
                'type'       => $result['type'],
                'start_date' => $result['start_date'],
                'end_date'   => $result['end_date'],
                'budget'     => $result['budget'],
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

    public function periodStats() {
        $this->load->language('crm/campaign');

        $filter_date_start = isset($this->request->post['filter_date_start']) ? $this->request->post['filter_date_start'] : '';
        $filter_date_end = isset($this->request->post['filter_date_end']) ? $this->request->post['filter_date_end'] : '';

        $json = array();
        try {
            // حساب الزيارات من cod_visitors_stats
            // نلخص الزيارات بين filter_date_start و filter_date_end
            $visits = $this->getVisitsCount($filter_date_start, $filter_date_end);

            // حساب الطلبات من cod_order
            // نلخص عدد الطلبات في الفترة المحددة
            $orders = $this->getOrdersCount($filter_date_start, $filter_date_end);

            $json['visits'] = $visits;
            $json['orders'] = $orders;
        } catch (Exception $e) {
            $json['error'] = $this->language->get('text_ajax_error') . ' : ' . $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function getVisitsCount($date_start, $date_end) {
        if (!$date_start || !$date_end) return 0;
        $query = $this->db->query("SELECT SUM(visits) as total FROM `cod_visitors_stats` WHERE visit_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
        return $query->row['total'] ? (int)$query->row['total'] : 0;
    }

    private function getOrdersCount($date_start, $date_end) {
        if (!$date_start || !$date_end) return 0;
        $query = $this->db->query("SELECT COUNT(*) as total FROM `cod_order` WHERE date_added BETWEEN '" . $this->db->escape($date_start) . " 00:00:00' AND '" . $this->db->escape($date_end) . " 23:59:59'");
        return $query->row['total'] ? (int)$query->row['total'] : 0;
    }

    public function getForm() {
        $this->load->language('crm/campaign');
        $this->load->model('crm/campaign');

        $json = array();
        if (isset($this->request->post['campaign_id'])) {
            $campaign_id = (int)$this->request->post['campaign_id'];
            $info = $this->model_crm_campaign->getCampaign($campaign_id);

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
        $this->load->language('crm/campaign');
        $this->load->model('crm/campaign');

        $json = array();

        if (!$this->user->hasPermission('modify', 'crm/campaign')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $campaign_id = isset($this->request->post['campaign_id']) ? (int)$this->request->post['campaign_id'] : 0;

            $data = array(
                'name'              => $this->request->post['name'],
                'type'              => $this->request->post['type'],
                'start_date'        => $this->request->post['start_date'],
                'end_date'          => $this->request->post['end_date'],
                'budget'            => $this->request->post['budget'],
                'code'              => $this->request->post['code'],
                'status'            => $this->request->post['status'],
                'assigned_to_user_id' => $this->request->post['assigned_to_user_id'],
                'actual_spend'      => $this->request->post['actual_spend'],
                'invoice_reference' => $this->request->post['invoice_reference'],
                'add_expense'       => isset($this->request->post['add_expense']) ? 1 : 0,
                'notes'             => $this->request->post['notes']
            );

            if (empty($data['name']) || empty($data['start_date']) || empty($data['end_date'])) {
                $json['error'] = $this->language->get('error_required');
            } else {
                if ($campaign_id) {
                    $this->model_crm_campaign->editCampaign($campaign_id, $data);
                    $json['success'] = $this->language->get('text_success_edit');
                } else {
                    $campaign_id = $this->model_crm_campaign->addCampaign($data);
                    $json['success'] = $this->language->get('text_success_add');
                }

                // إذا add_expense مفعّل و actual_spend > 0
                if ($data['add_expense'] && $data['actual_spend'] > 0) {
                    $this->addMarketingExpenseJournal($data['actual_spend'], $data['invoice_reference']);
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function addMarketingExpenseJournal($amount, $invoice_reference) {
        // إضافة قيد يومية لمصروف إعلاني
        // سند تسجيل:
        // من مذكورين:
        //   مصروف إعلاني (مدين) XXX
        // إلى:
        //   النقدية (دائن) XXX
        $description = "Advertising expense for invoice " . $invoice_reference;
        $this->db->query("INSERT INTO `cod_journals` SET refnum='CAMPAIGN_EXP', thedate=CURDATE(), description='".$this->db->escape($description)."', entrytype='1', created_at=NOW()");
        $journal_id = $this->db->getLastId();

        // مدين: مصروف إعلاني
        $this->db->query("INSERT INTO `cod_journal_entries` SET journal_id='".(int)$journal_id."', account_code='".(int)$this->ad_expense_account."', is_debit='1', amount='".(float)$amount."'");

        // دائن: نقدية
        $this->db->query("INSERT INTO `cod_journal_entries` SET journal_id='".(int)$journal_id."', account_code='".(int)$this->cash_account."', is_debit='0', amount='".(float)$amount."'");
    }

    public function delete() {
        $this->load->language('crm/campaign');
        $this->load->model('crm/campaign');

        $json = array();

        if (!$this->user->hasPermission('modify', 'crm/campaign')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['campaign_id'])) {
                $campaign_id = (int)$this->request->post['campaign_id'];
                $this->model_crm_campaign->deleteCampaign($campaign_id);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
