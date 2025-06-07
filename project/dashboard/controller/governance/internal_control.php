<?php
class ControllerGovernanceInternalControl extends Controller {
    private $error = [];

    public function index() {
        // تحقق من user_token
        if (!isset($this->request->get['user_token']) 
            || !isset($this->session->data['user_token'])
            || ($this->request->get['user_token'] != $this->session->data['user_token'])) {
            $this->response->redirect($this->url->link('common/login','',true));
        }

        // صلاحية الوصول
        if (!$this->user->hasPermission('access','governance/internal_control')) {
            $this->session->data['error'] = 'No permission to access Internal Control!';
            $this->response->redirect($this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true));
        }

        // تحميل اللغة
        $this->load->language('governance/internal_control');
        $this->document->setTitle($this->language->get('heading_title'));

        // تحميل user_group لعرض المجموعات في القالب
        $this->load->model('user/user_group');
        $data['user_groups'] = $this->model_user_user_group->getUserGroups();

        // صلاحيات
        $data['can_add']    = $this->user->hasKey('internal_control_add');
        $data['can_edit']   = $this->user->hasKey('internal_control_edit');
        $data['can_delete'] = $this->user->hasKey('internal_control_delete');

        // تحضير الواجهة
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');
        $data['user_token']  = $this->session->data['user_token'];

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list']     = $this->language->get('text_list');

        // رسائل الخطأ/النجاح
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
            'href' => $this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('governance/internal_control','user_token='.$this->session->data['user_token'],true)
        ];

        // عرض القالب
        $this->response->setOutput(
            $this->load->view('governance/internal_control_list', $data)
        );
    }

    /**
     * AJAX: جلب قائمة الضوابط
     */
    public function ajaxList() {
        if (!$this->user->hasPermission('access','governance/internal_control')) {
            return $this->sendJson(['error'=>'No Access']);
        }

        // فلاتر
        $filters = [];
        $filters['status']                 = $this->request->post['filter_status'] ?? '';
        $filters['control_name']           = $this->request->post['filter_control_name'] ?? '';
        $filters['responsible_group_id']   = $this->request->post['filter_responsible_group_id'] ?? '';
        $filters['effective_date_start']   = $this->request->post['filter_effective_date_start'] ?? '';
        $filters['effective_date_end']     = $this->request->post['filter_effective_date_end'] ?? '';

        $this->load->model('governance/internal_control');
        $results = $this->model_governance_internal_control->getControls($filters);

        $data_out = [];
        foreach ($results as $r) {
            $group_name = (!empty($r['responsible_group_name']) ? $r['responsible_group_name'] : '');
            $data_out[] = [
                'control_id'    => $r['control_id'],
                'control_name'  => $r['control_name'],
                'status'        => $r['status'],
                'effective_date'=> $r['effective_date'],
                'responsible'   => $group_name,
                'date_added'    => $r['date_added']
            ];
        }
        return $this->sendJson(['data'=>$data_out]);
    }

    /**
     * AJAX: إضافة ضابط
     */
    public function ajaxAdd() {
        if(!$this->user->hasKey('internal_control_add')) {
            return $this->sendJson(['error'=>'No permission to add']);
        }

        $this->load->model('governance/internal_control');

        $data_in = [
            'control_name'         => $this->request->post['control_name']         ?? '',
            'description'          => $this->request->post['description']          ?? '',
            'responsible_group_id' => (int)($this->request->post['responsible_group_id'] ?? 0),
            'effective_date'       => $this->request->post['effective_date']       ?? date('Y-m-d'),
            'review_date'          => $this->request->post['review_date']          ?? null,
            'status'               => $this->request->post['status']               ?? 'active'
        ];

        if(empty($data_in['control_name'])) {
            return $this->sendJson(['error'=>'Control name is required.']);
        }

        $cid = $this->model_governance_internal_control->addControl($data_in);
        if($cid) {
            return $this->sendJson(['success'=>'Internal Control added','control_id'=>$cid]);
        } else {
            return $this->sendJson(['error'=>'Insert failed']);
        }
    }

    /**
     * AJAX: تعديل
     */
    public function ajaxEdit() {
        if(!$this->user->hasKey('internal_control_edit')) {
            return $this->sendJson(['error'=>'No permission to edit']);
        }

        $control_id = (int)($this->request->post['control_id'] ?? 0);
        if($control_id<=0) {
            return $this->sendJson(['error'=>'Invalid control_id']);
        }

        $this->load->model('governance/internal_control');

        $data_in = [
            'control_name'         => $this->request->post['control_name']         ?? '',
            'description'          => $this->request->post['description']          ?? '',
            'responsible_group_id' => (int)($this->request->post['responsible_group_id'] ?? 0),
            'effective_date'       => $this->request->post['effective_date']       ?? date('Y-m-d'),
            'review_date'          => $this->request->post['review_date']          ?? null,
            'status'               => $this->request->post['status']               ?? 'active'
        ];

        if(empty($data_in['control_name'])) {
            return $this->sendJson(['error'=>'Control name is required.']);
        }

        $ok = $this->model_governance_internal_control->updateControl($control_id,$data_in);
        if($ok) {
            return $this->sendJson(['success'=>'Control updated']);
        } else {
            return $this->sendJson(['error'=>'Failed to update']);
        }
    }

    /**
     * AJAX: حذف
     */
    public function ajaxDelete() {
        if(!$this->user->hasKey('internal_control_delete')) {
            return $this->sendJson(['error'=>'No permission to delete']);
        }

        $control_id = (int)($this->request->post['control_id'] ?? 0);
        if($control_id<=0) {
            return $this->sendJson(['error'=>'Invalid ID']);
        }

        $this->load->model('governance/internal_control');
        $del = $this->model_governance_internal_control->deleteControl($control_id);
        if($del) {
            return $this->sendJson(['success'=>'Control deleted']);
        } else {
            return $this->sendJson(['error'=>'Delete failed']);
        }
    }

    /**
     * AJAX: جلب سجل واحد
     */
    public function getOne() {
        if(!$this->user->hasPermission('access','governance/internal_control')) {
            return $this->sendJson(['error'=>'No Access']);
        }

        $control_id = (int)($this->request->post['control_id'] ?? 0);
        if($control_id<=0) {
            return $this->sendJson(['error'=>'Invalid ID']);
        }

        $this->load->model('governance/internal_control');
        $info = $this->model_governance_internal_control->getControl($control_id);
        if($info) {
            return $this->sendJson(['success'=>true,'data'=>$info]);
        } else {
            return $this->sendJson(['error'=>'Not found']);
        }
    }

    private function sendJson($data) {
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE));
        return;
    }
}
