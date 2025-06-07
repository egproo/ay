<?php
class ControllerToolAudit extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('tool/audit');
        $this->document->setTitle($this->language->get('heading_title'));

        if (!$this->user->hasPermission('access','tool/audit')) {
            return new Action('error/permission');
        }

        $this->load->model('tool/audit');
        $this->load->model('user/user');

        // فلاتر
        $filter_user_id = isset($this->request->get['filter_user_id'])?$this->request->get['filter_user_id']:'';
        $filter_action = isset($this->request->get['filter_action'])?$this->request->get['filter_action']:'';
        $filter_reference_type = isset($this->request->get['filter_reference_type'])?$this->request->get['filter_reference_type']:'';
        $filter_date_start = isset($this->request->get['filter_date_start'])?$this->request->get['filter_date_start']:'';
        $filter_date_end = isset($this->request->get['filter_date_end'])?$this->request->get['filter_date_end']:'';

        $data['filter_user_id'] = $filter_user_id;
        $data['filter_action'] = $filter_action;
        $data['filter_reference_type'] = $filter_reference_type;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;

        $data['user_token'] = $this->session->data['user_token'];

        // Users for dropdown
        $data['users'] = $this->model_user_user->getUsers();

        // سيتم عرض النتائج عبر AJAX لهذا لن نجلب السجلات هنا، فقط سنعرض الصفحة والفورم

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // زر حذف (سيكون عبر AJAX)
        $this->response->setOutput($this->load->view('tool/audit', $data));
    }

    // AJAX: تحميل البيانات
    public function loadData() {
        $this->load->model('tool/audit');
        if (!$this->user->hasPermission('access','tool/audit')) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array('error'=>'No Permission')));
            return;
        }

        $filter = array(
            'filter_user_id' => isset($this->request->post['filter_user_id'])?$this->request->post['filter_user_id']:'',
            'filter_action' => isset($this->request->post['filter_action'])?$this->request->post['filter_action']:'',
            'filter_reference_type' => isset($this->request->post['filter_reference_type'])?$this->request->post['filter_reference_type']:'',
            'filter_date_start' => isset($this->request->post['filter_date_start'])?$this->request->post['filter_date_start']:'',
            'filter_date_end' => isset($this->request->post['filter_date_end'])?$this->request->post['filter_date_end']:'',

            'start' => isset($this->request->post['start'])?(int)$this->request->post['start']:0,
            'limit' => isset($this->request->post['limit'])?(int)$this->request->post['limit']:20,
        );

        $logs = $this->model_tool_audit->getLogs($filter);
        $total = $this->model_tool_audit->getTotalLogs($filter);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(array('total'=>$total,'logs'=>$logs)));
    }

    // AJAX: حذف سجل
    public function deleteLog() {
        $this->load->language('tool/audit');
        if (!$this->user->hasPermission('modify','tool/audit')) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array('error'=>$this->language->get('error_permission'))));
            return;
        }

        $log_id = (int)$this->request->post['log_id'];
        $this->load->model('tool/audit');
        $this->model_tool_audit->deleteLog($log_id);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(array('success'=>true)));
    }
}
