<?php
class ControllerGovernanceCompliance extends Controller {
    private $error = array();

    /**
     * شاشة القائمة الرئيسة لسجل الالتزام
     */
    public function index() {
        // 1) تحقق من user_token
        if (!isset($this->request->get['user_token']) 
            || !isset($this->session->data['user_token'])
            || ($this->request->get['user_token'] != $this->session->data['user_token'])) {
            $this->response->redirect($this->url->link('common/login','',true));
        }

        // 2) أجزاء واجهة أوبن كارت
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        // 3) صلاحية الوصول
        if (!$this->user->hasPermission('access','governance/compliance')) {
            $this->session->data['error'] = 'No permission for Compliance!';
            $this->response->redirect($this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true));
        }

        // 4) تحميل ملف اللغة
        $this->load->language('governance/compliance');

        // 5) ضبط العنوان
        $this->document->setTitle($this->language->get('heading_title'));

        // 6) متغيرات الواجهة
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list']     = $this->language->get('text_list');
        $data['user_token']    = $this->session->data['user_token'];

        // صلاحيات
        $data['can_add']    = $this->user->hasKey('compliance_add');
        $data['can_edit']   = $this->user->hasKey('compliance_edit');
        $data['can_delete'] = $this->user->hasKey('compliance_delete');

        // فلاتر (مثال: فلتر حسب الحالة)
        $data['filter_status'] = $this->request->get['filter_status'] ?? '';

        // رسائل
        if(isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        } else {
            $data['error_warning'] = '';
        }

        if(isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        // Breadcrumbs
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard','user_token='.$data['user_token'],true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('governance/compliance','user_token='.$data['user_token'],true)
        ];

        // تحميل القالب
        $this->response->setOutput(
            $this->load->view('governance/compliance_list', $data)
        );
    }

    /**
     * AJAX: جلب قائمة سجل الالتزام
     */
    public function ajaxList() {
        if(!$this->user->hasPermission('access','governance/compliance')) {
            return $this->sendJson(['error'=>'No Access']);
        }

        $filter_status = $this->request->post['filter_status'] ?? '';

        $this->load->model('governance/compliance');
        $results = $this->model_governance_compliance->getRecords(['status'=>$filter_status]);

        $data_out = [];
        foreach($results as $rc) {
            $data_out[] = [
                'compliance_id'     => $rc['compliance_id'],
                'compliance_type'   => $rc['compliance_type'],
                'reference_code'    => $rc['reference_code'],
                'description'       => $rc['description'],
                'due_date'          => $rc['due_date'],
                'status'            => $rc['status'],
                'responsible_user'  => $rc['responsible_user_name'] ?? '',
                'date_added'        => $rc['date_added'],
            ];
        }

        return $this->sendJson(['data'=>$data_out]);
    }

    /**
     * AJAX: إضافة سجل جديد
     */
    public function ajaxAdd() {
        if(!$this->user->hasKey('compliance_add')) {
            return $this->sendJson(['error'=>'No permission to add']);
        }

        $this->load->model('governance/compliance');

        $data_in = [
            'compliance_type'   => $this->request->post['compliance_type'] ?? '',
            'reference_code'    => $this->request->post['reference_code'] ?? '',
            'description'       => $this->request->post['description'] ?? '',
            'due_date'          => $this->request->post['due_date'] ?? null,
            'status'            => $this->request->post['status'] ?? 'pending',
            'responsible_user_id'=>(int)($this->request->post['responsible_user_id'] ?? 0)
        ];

        // Validate
        if(empty($data_in['compliance_type'])) {
            return $this->sendJson(['error'=>'Compliance Type is required!']);
        }

        $cid = $this->model_governance_compliance->addRecord($data_in);
        if($cid) {
            return $this->sendJson(['success'=>'Compliance record added','compliance_id'=>$cid]);
        } else {
            return $this->sendJson(['error'=>'Failed to insert']);
        }
    }

    /**
     * AJAX: تعديل سجل قائم
     */
    public function ajaxEdit() {
        if(!$this->user->hasKey('compliance_edit')) {
            return $this->sendJson(['error'=>'No permission to edit']);
        }

        $compliance_id = (int)($this->request->post['compliance_id'] ?? 0);
        if($compliance_id <= 0) {
            return $this->sendJson(['error'=>'Invalid compliance_id']);
        }

        $this->load->model('governance/compliance');

        $data_in = [
            'compliance_type'   => $this->request->post['compliance_type'] ?? '',
            'reference_code'    => $this->request->post['reference_code'] ?? '',
            'description'       => $this->request->post['description'] ?? '',
            'due_date'          => $this->request->post['due_date'] ?? null,
            'status'            => $this->request->post['status'] ?? 'pending',
            'responsible_user_id'=>(int)($this->request->post['responsible_user_id'] ?? 0)
        ];

        if(empty($data_in['compliance_type'])) {
            return $this->sendJson(['error'=>'Compliance Type is required!']);
        }

        $ok = $this->model_governance_compliance->updateRecord($compliance_id, $data_in);
        if($ok) {
            return $this->sendJson(['success'=>'Compliance record updated']);
        } else {
            return $this->sendJson(['error'=>'Error updating record']);
        }
    }

    /**
     * AJAX: حذف سجل
     */
    public function ajaxDelete() {
        if(!$this->user->hasKey('compliance_delete')) {
            return $this->sendJson(['error'=>'No permission to delete']);
        }

        $compliance_id = (int)($this->request->post['compliance_id'] ?? 0);
        if($compliance_id <= 0) {
            return $this->sendJson(['error'=>'Invalid compliance_id']);
        }

        $this->load->model('governance/compliance');
        $del = $this->model_governance_compliance->deleteRecord($compliance_id);
        if($del) {
            return $this->sendJson(['success'=>'Record deleted']);
        } else {
            return $this->sendJson(['error'=>'Failed to delete record']);
        }
    }

    /**
     * AJAX: جلب سجل واحد (إن احتجنا لزر تعديل)
     */
    public function getOne() {
        if(!$this->user->hasPermission('access','governance/compliance')) {
            return $this->sendJson(['error'=>'No Access']);
        }

        $compliance_id = (int)($this->request->post['compliance_id'] ?? 0);
        if($compliance_id <= 0) {
            return $this->sendJson(['error'=>'Invalid compliance_id']);
        }

        $this->load->model('governance/compliance');
        $info = $this->model_governance_compliance->getRecord($compliance_id);
        if($info) {
            return $this->sendJson(['success'=>true,'record'=>$info]);
        } else {
            return $this->sendJson(['error'=>'Not found']);
        }
    }

    /**
     * Helper لإخراج JSON
     */
    private function sendJson($data) {
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE));
        return;
    }
}
