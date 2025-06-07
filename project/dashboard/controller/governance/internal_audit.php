<?php
class ControllerGovernanceInternalAudit extends Controller {
    private $error = [];

    public function index() {
        // التحقق من الـuser_token
        if (!isset($this->request->get['user_token']) 
            || !isset($this->session->data['user_token'])
            || ($this->request->get['user_token'] != $this->session->data['user_token'])) {
            $this->response->redirect($this->url->link('common/login','',true));
        }

        // صلاحية الوصول
        if (!$this->user->hasPermission('access','governance/internal_audit')) {
            $this->session->data['error'] = 'No permission for Internal Audit!';
            $this->response->redirect($this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true));
        }

        // تحميل اللغة
        $this->load->language('governance/internal_audit');
        $this->document->setTitle($this->language->get('heading_title'));

        // تحضير الواجهة
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');
        $data['user_token']  = $this->session->data['user_token'];

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list']     = $this->language->get('text_list');

        // صلاحيات الإضافة/التعديل/الحذف
        $data['can_add']    = $this->user->hasKey('internal_audit_add');
        $data['can_edit']   = $this->user->hasKey('internal_audit_edit');
        $data['can_delete'] = $this->user->hasKey('internal_audit_delete');

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
            'href' => $this->url->link('common/dashboard','user_token='.$data['user_token'],true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('governance/internal_audit','user_token='.$data['user_token'],true)
        ];

        // عرض القالب
        $this->response->setOutput(
            $this->load->view('governance/internal_audit_list', $data)
        );
    }

    /**
     * جلب قائمة التدقيق (AJAX)
     */
    public function ajaxList() {
        if (!$this->user->hasPermission('access','governance/internal_audit')) {
            return $this->sendJson(['error'=>'No Access']);
        }

        $filters = [];
        $filters['status']     = $this->request->post['filter_status']     ?? '';
        $filters['date_start'] = $this->request->post['filter_date_start'] ?? '';
        $filters['date_end']   = $this->request->post['filter_date_end']   ?? '';
        // لو أردت نوع التدقيق
        // $filters['audit_type'] = $this->request->post['filter_audit_type'] ?? '';

        $this->load->model('governance/internal_audit');
        $results = $this->model_governance_internal_audit->getAudits($filters);

        $out = [];
        foreach ($results as $r) {
            $out[] = [
                'audit_id'       => $r['audit_id'],
                'audit_subject'  => $r['audit_subject'],
                'scheduled_date' => $r['scheduled_date'],
                'status'         => $r['status'],
                'auditor_name'   => $r['auditor_name'] ?? ''
            ];
        }

        return $this->sendJson(['data'=>$out]);
    }

    /**
     * إضافة تدقيق (AJAX)
     * هنا نحدد المدقق = المستخدم الحالي
     */
    public function ajaxAdd() {
        if (!$this->user->hasKey('internal_audit_add')) {
            return $this->sendJson(['error'=>'No permission to add.']);
        }

        $this->load->model('governance/internal_audit');

        // تعيين المدقق تلقائياً من user->getId()
        $data_in = [
            'audit_subject'   => $this->request->post['audit_subject']   ?? '',
            'audit_type'      => $this->request->post['audit_type']      ?? '',
            'description'     => $this->request->post['description']     ?? '',
            'auditor_user_id' => $this->user->getId(), // <-- هنا التلقائية
            'scheduled_date'  => $this->request->post['scheduled_date']  ?? date('Y-m-d'),
            'completion_date' => $this->request->post['completion_date'] ?? null,
            'findings'        => $this->request->post['findings']        ?? '',
            'recommendations' => $this->request->post['recommendations'] ?? '',
            'status'          => $this->request->post['status']          ?? 'scheduled'
        ];

        if (empty($data_in['audit_subject'])) {
            return $this->sendJson(['error'=>'Audit subject is required.']);
        }

        $audit_id = $this->model_governance_internal_audit->addAudit($data_in);
        if ($audit_id) {
            return $this->sendJson(['success'=>'Internal Audit record added','audit_id'=>$audit_id]);
        } else {
            return $this->sendJson(['error'=>'Failed to insert record.']);
        }
    }

    /**
     * تعديل تدقيق (AJAX)
     * يمكننا أيضًا إعادة ضبط auditor_user_id لتكون المستخدم الحالي إن أحببنا
     */
    public function ajaxEdit() {
        if (!$this->user->hasKey('internal_audit_edit')) {
            return $this->sendJson(['error'=>'No permission to edit.']);
        }

        $audit_id = (int)($this->request->post['audit_id'] ?? 0);
        if ($audit_id <= 0) {
            return $this->sendJson(['error'=>'Invalid audit_id']);
        }

        $this->load->model('governance/internal_audit');

        // تعيين المدقق للمستخدم الحالي أثناء التعديل (حسب طلبك)
        $data_in = [
            'audit_subject'   => $this->request->post['audit_subject']   ?? '',
            'audit_type'      => $this->request->post['audit_type']      ?? '',
            'description'     => $this->request->post['description']     ?? '',
            'auditor_user_id' => $this->user->getId(), // <-- بشكل تلقائي أيضًا
            'scheduled_date'  => $this->request->post['scheduled_date']  ?? date('Y-m-d'),
            'completion_date' => $this->request->post['completion_date'] ?? null,
            'findings'        => $this->request->post['findings']        ?? '',
            'recommendations' => $this->request->post['recommendations'] ?? '',
            'status'          => $this->request->post['status']          ?? 'scheduled'
        ];

        if (empty($data_in['audit_subject'])) {
            return $this->sendJson(['error'=>'Audit subject is required.']);
        }

        $ok = $this->model_governance_internal_audit->updateAudit($audit_id, $data_in);
        if ($ok) {
            return $this->sendJson(['success'=>'Audit updated']);
        } else {
            return $this->sendJson(['error'=>'Error updating audit']);
        }
    }

    /**
     * حذف
     */
    public function ajaxDelete() {
        if (!$this->user->hasKey('internal_audit_delete')) {
            return $this->sendJson(['error'=>'No permission to delete.']);
        }
        $audit_id = (int)($this->request->post['audit_id'] ?? 0);
        if ($audit_id <= 0) {
            return $this->sendJson(['error'=>'Invalid ID']);
        }

        $this->load->model('governance/internal_audit');
        $del = $this->model_governance_internal_audit->deleteAudit($audit_id);
        if ($del) {
            return $this->sendJson(['success'=>'Audit deleted']);
        } else {
            return $this->sendJson(['error'=>'Failed to delete audit']);
        }
    }

    /**
     * جلب سجل واحد
     */
    public function getOne() {
        if (!$this->user->hasPermission('access','governance/internal_audit')) {
            return $this->sendJson(['error'=>'No Access']);
        }

        $audit_id = (int)($this->request->post['audit_id'] ?? 0);
        if ($audit_id <= 0) {
            return $this->sendJson(['error'=>'Invalid ID']);
        }

        $this->load->model('governance/internal_audit');
        $info = $this->model_governance_internal_audit->getAudit($audit_id);
        if ($info) {
            return $this->sendJson(['success'=>true,'data'=>$info]);
        } else {
            return $this->sendJson(['error'=>'Record not found']);
        }
    }

    private function sendJson($data) {
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE));
        return;
    }
}
