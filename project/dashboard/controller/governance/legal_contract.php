<?php
class ControllerGovernanceLegalContract extends Controller {
    private $error = array();

    /**
     * الصفحة الرئيسية للوحدة: عرض قائمة العقود + فلاتر + زر إضافة عقد
     */
    public function index() {
        // 1) تحقق من user_token
        if (!isset($this->request->get['user_token']) 
            || !isset($this->session->data['user_token'])
            || ($this->request->get['user_token'] != $this->session->data['user_token'])) {
            $this->response->redirect($this->url->link('common/login','',true));
        }

        // 2) أجزاء واجهة (header/column_left/footer)
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        // 3) صلاحية الوصول
        if (!$this->user->hasPermission('access','governance/legal_contract')) {
            $this->session->data['error'] = 'No permission for Legal Contract!';
            $this->response->redirect($this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true));
        }

        // 4) تحميل ملف اللغة
        $this->load->language('governance/legal_contract');

        // 5) تعيين العنوان
        $this->document->setTitle($this->language->get('heading_title'));

        // 6) إعداد بيانات الواجهة
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list']     = $this->language->get('text_list');
        $data['user_token']    = $this->session->data['user_token'];

        // الصلاحيات
        $data['can_add']    = $this->user->hasKey('legal_contract_add');
        $data['can_edit']   = $this->user->hasKey('legal_contract_edit');
        $data['can_delete'] = $this->user->hasKey('legal_contract_delete');

        // فلتر اختياري (مثلاً حسب الحالة)
        $data['filter_status'] = $this->request->get['filter_status'] ?? '';

        // رسائل الخطأ والنجاح
        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
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
            'href' => $this->url->link('governance/legal_contract','user_token='.$data['user_token'],true)
        ];

        // تحميل القالب (Twig)
        $this->response->setOutput(
            $this->load->view('governance/legal_contract_list', $data)
        );
    }

    /**
     * AJAX: جلب قائمة العقود
     */
    public function ajaxList() {
        // تحقق من صلاحية الوصول
        if (!$this->user->hasPermission('access','governance/legal_contract')) {
            return $this->sendJson(['error'=>'No Access']);
        }

        $filter_status = $this->request->post['filter_status'] ?? '';

        $this->load->model('governance/legal_contract');
        $results = $this->model_governance_legal_contract->getContracts(['status'=>$filter_status]);

        $data_out = [];
        foreach($results as $c) {
            $data_out[] = [
                'contract_id'   => $c['contract_id'],
                'contract_type' => $c['contract_type'],
                'title'         => $c['title'],
                'start_date'    => $c['start_date'],
                'end_date'      => $c['end_date'],
                'status'        => $c['status'],
                'value'         => $c['value'],
                'date_added'    => $c['date_added'],
            ];
        }

        return $this->sendJson(['data'=>$data_out]);
    }

    /**
     * AJAX: إضافة عقد
     */
    public function ajaxAdd() {
        // تحقق من صلاحية الإضافة
        if (!$this->user->hasKey('legal_contract_add')) {
            return $this->sendJson(['error'=>'No permission to add contract.']);
        }

        $this->load->model('governance/legal_contract');

        $data_in = [
            'contract_type' => $this->request->post['contract_type'] ?? '',
            'title'         => $this->request->post['title']         ?? '',
            'party_id'      => (int)($this->request->post['party_id'] ?? 0),
            'start_date'    => $this->request->post['start_date']    ?? '',
            'end_date'      => $this->request->post['end_date']      ?? null,
            'status'        => $this->request->post['status']        ?? 'draft',
            'value'         => (float)($this->request->post['value'] ?? 0),
            'description'   => $this->request->post['description']   ?? '',
        ];

        // Validation
        if (empty($data_in['title'])) {
            return $this->sendJson(['error'=>'Title is required.']);
        }
        if (empty($data_in['contract_type'])) {
            return $this->sendJson(['error'=>'Contract type is required.']);
        }
        if (empty($data_in['start_date'])) {
            return $this->sendJson(['error'=>'Start date is required.']);
        }

        $cid = $this->model_governance_legal_contract->addContract($data_in);
        if($cid) {
            return $this->sendJson(['success'=>'Legal contract added','contract_id'=>$cid]);
        } else {
            return $this->sendJson(['error'=>'Failed to insert contract.']);
        }
    }

    /**
     * AJAX: تعديل عقد
     */
    public function ajaxEdit() {
        if (!$this->user->hasKey('legal_contract_edit')) {
            return $this->sendJson(['error'=>'No permission to edit contract.']);
        }

        $contract_id = (int)($this->request->post['contract_id'] ?? 0);
        if($contract_id <= 0) {
            return $this->sendJson(['error'=>'Invalid contract_id']);
        }

        $this->load->model('governance/legal_contract');

        $data_in = [
            'contract_type' => $this->request->post['contract_type'] ?? '',
            'title'         => $this->request->post['title']         ?? '',
            'party_id'      => (int)($this->request->post['party_id'] ?? 0),
            'start_date'    => $this->request->post['start_date']    ?? '',
            'end_date'      => $this->request->post['end_date']      ?? null,
            'status'        => $this->request->post['status']        ?? 'draft',
            'value'         => (float)($this->request->post['value'] ?? 0),
            'description'   => $this->request->post['description']   ?? '',
        ];

        if (empty($data_in['title'])) {
            return $this->sendJson(['error'=>'Title is required.']);
        }
        if (empty($data_in['contract_type'])) {
            return $this->sendJson(['error'=>'Contract type is required.']);
        }
        if (empty($data_in['start_date'])) {
            return $this->sendJson(['error'=>'Start date is required.']);
        }

        $ok = $this->model_governance_legal_contract->updateContract($contract_id, $data_in);
        if($ok) {
            return $this->sendJson(['success'=>'Contract updated']);
        } else {
            return $this->sendJson(['error'=>'Error updating contract']);
        }
    }

    /**
     * AJAX: حذف عقد
     */
    public function ajaxDelete() {
        if(!$this->user->hasKey('legal_contract_delete')) {
            return $this->sendJson(['error'=>'No permission to delete contract.']);
        }

        $contract_id = (int)($this->request->post['contract_id'] ?? 0);
        if($contract_id <= 0) {
            return $this->sendJson(['error'=>'Invalid contract_id']);
        }

        $this->load->model('governance/legal_contract');
        $del = $this->model_governance_legal_contract->deleteContract($contract_id);
        if($del) {
            return $this->sendJson(['success'=>'Contract deleted']);
        } else {
            return $this->sendJson(['error'=>'Failed to delete contract']);
        }
    }

    /**
     * AJAX: جلب عقد واحد (لدعم زر التعديل)
     */
    public function getOne() {
        if(!$this->user->hasPermission('access','governance/legal_contract')) {
            return $this->sendJson(['error'=>'No Access']);
        }

        $contract_id = (int)($this->request->post['contract_id'] ?? 0);
        if($contract_id<=0) {
            return $this->sendJson(['error'=>'Invalid contract_id']);
        }

        $this->load->model('governance/legal_contract');
        $info = $this->model_governance_legal_contract->getContract($contract_id);
        if($info) {
            return $this->sendJson(['success'=>true,'data'=>$info]);
        } else {
            return $this->sendJson(['error'=>'Contract not found']);
        }
    }

    /**
     * Helper لإرجاع JSON
     */
    private function sendJson($data) {
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE));
        return;
    }
}
