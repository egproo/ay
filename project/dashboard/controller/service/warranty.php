<?php
class ControllerServiceWarranty extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('service/warranty');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('service/warranty');

        $data['user_token'] = $this->session->data['user_token'];

        // روابط Ajax
        $data['ajax_list_url']  = $this->url->link('service/warranty/list', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_save_url']  = $this->url->link('service/warranty/save', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_get_url']   = $this->url->link('service/warranty/getForm', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_delete_url']= $this->url->link('service/warranty/delete', 'user_token=' . $this->session->data['user_token'], true);

        // النصوص
        $data['heading_title']         = $this->language->get('heading_title');
        $data['text_filter']           = $this->language->get('text_filter');
        $data['text_enter_order_id']   = $this->language->get('text_enter_order_id');
        $data['text_all_statuses']     = $this->language->get('text_all_statuses');
        $data['text_active']           = $this->language->get('text_active');
        $data['text_expired']          = $this->language->get('text_expired');
        $data['text_claimed']          = $this->language->get('text_claimed');
        $data['text_void']             = $this->language->get('text_void');
        $data['button_filter']         = $this->language->get('button_filter');
        $data['button_reset']          = $this->language->get('button_reset');
        $data['button_add_warranty']   = $this->language->get('button_add_warranty');
        $data['text_warranty_list']    = $this->language->get('text_warranty_list');
        $data['text_add_warranty']     = $this->language->get('text_add_warranty');
        $data['text_edit_warranty']    = $this->language->get('text_edit_warranty');
        $data['text_ajax_error']       = $this->language->get('text_ajax_error');
        $data['text_confirm_delete']   = $this->language->get('text_confirm_delete');

        $data['text_order_id']         = $this->language->get('text_order_id');
        $data['text_product_id']       = $this->language->get('text_product_id');
        $data['text_customer_id']      = $this->language->get('text_customer_id');
        $data['text_start_date']       = $this->language->get('text_start_date');
        $data['text_end_date']         = $this->language->get('text_end_date');
        $data['text_warranty_status']  = $this->language->get('text_warranty_status');
        $data['text_notes']            = $this->language->get('text_notes');

        $data['button_close']          = $this->language->get('button_close');
        $data['button_save']           = $this->language->get('button_save');

        $data['column_order_id']       = $this->language->get('column_order_id');
        $data['column_customer']       = $this->language->get('column_customer');
        $data['column_product']        = $this->language->get('column_product');
        $data['column_start_date']     = $this->language->get('column_start_date');
        $data['column_end_date']       = $this->language->get('column_end_date');
        $data['column_warranty_status']= $this->language->get('column_warranty_status');
        $data['column_actions']        = $this->language->get('column_actions');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard','user_token=' . $this->session->data['user_token'],true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('service/warranty','user_token=' . $this->session->data['user_token'],true)
        );

        $data['header']     = $this->load->controller('common/header');
        $data['column_left']= $this->load->controller('common/column_left');
        $data['footer']     = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('service/warranty_list', $data));
    }

    public function list() {
        $this->load->language('service/warranty');
        $this->load->model('service/warranty');

        $filter_order_id = isset($this->request->post['filter_order_id']) ? $this->request->post['filter_order_id'] : '';
        $filter_status   = isset($this->request->post['filter_status']) ? $this->request->post['filter_status'] : '';

        $start  = isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0;
        $length = isset($this->request->post['length']) ? (int)$this->request->post['length'] : 10;
        $draw   = isset($this->request->post['draw']) ? (int)$this->request->post['draw'] : 1;
        $order_column = isset($this->request->post['order'][0]['column']) ? (int)$this->request->post['order'][0]['column'] : 0;
        $order_dir = isset($this->request->post['order'][0]['dir']) ? $this->request->post['order'][0]['dir'] : 'asc';

        $columns = array('order_id','customer_name','product_name','start_date','end_date','warranty_status');
        $sort = isset($columns[$order_column]) ? $columns[$order_column] : 'start_date';

        $filter_data = array(
            'filter_order_id' => $filter_order_id,
            'filter_status'   => $filter_status,
            'start'           => $start,
            'limit'           => $length,
            'sort'            => $sort,
            'order'           => $order_dir
        );

        $total = $this->model_service_warranty->getTotalWarranties($filter_data);
        $results = $this->model_service_warranty->getWarranties($filter_data);

        $data = array();
        foreach ($results as $result) {
            $actions = '';
            if ($this->user->hasPermission('modify', 'service/warranty')) {
                $actions .= '<button class="btn btn-primary btn-sm btn-edit" data-id="'. $result['warranty_id'] .'"><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm btn-delete" data-id="'. $result['warranty_id'] .'"><i class="fa fa-trash"></i></button>';
            } else {
                $actions .= '<button class="btn btn-primary btn-sm" disabled><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm" disabled><i class="fa fa-trash"></i></button>';
            }

            $data[] = array(
                'order_id'        => $result['order_id'],
                'customer_name'   => $result['customer_name'],
                'product_name'    => $result['product_name'],
                'start_date'      => $result['start_date'],
                'end_date'        => $result['end_date'],
                'warranty_status' => $this->language->get('text_'.$result['warranty_status']),
                'actions'         => $actions
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
        $this->load->language('service/warranty');
        $this->load->model('service/warranty');

        $json = array();
        if (isset($this->request->post['warranty_id'])) {
            $warranty_id = (int)$this->request->post['warranty_id'];
            $info = $this->model_service_warranty->getWarranty($warranty_id);

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
        $this->load->language('service/warranty');
        $this->load->model('service/warranty');

        $json = array();

        if (!$this->user->hasPermission('modify', 'service/warranty')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $warranty_id = isset($this->request->post['warranty_id']) ? (int)$this->request->post['warranty_id'] : 0;

            $data = array(
                'order_id'        => $this->request->post['order_id'],
                'product_id'      => $this->request->post['product_id'],
                'customer_id'     => $this->request->post['customer_id'],
                'start_date'      => $this->request->post['start_date'],
                'end_date'        => $this->request->post['end_date'],
                'warranty_status' => $this->request->post['warranty_status'],
                'notes'           => $this->request->post['notes']
            );

            if (empty($data['order_id']) || empty($data['product_id']) || empty($data['customer_id']) || empty($data['start_date']) || empty($data['end_date'])) {
                $json['error'] = $this->language->get('error_required');
            } else {
                if ($warranty_id) {
                    $this->model_service_warranty->editWarranty($warranty_id, $data);
                    $json['success'] = $this->language->get('text_success_edit');
                } else {
                    $this->model_service_warranty->addWarranty($data);
                    $json['success'] = $this->language->get('text_success_add');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function delete() {
        $this->load->language('service/warranty');
        $this->load->model('service/warranty');

        $json = array();

        if (!$this->user->hasPermission('modify', 'service/warranty')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['warranty_id'])) {
                $warranty_id = (int)$this->request->post['warranty_id'];
                $this->model_service_warranty->deleteWarranty($warranty_id);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
