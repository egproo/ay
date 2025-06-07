<?php
class ControllerGovernanceRiskRegister extends Controller {
    private $error = [];

    public function index() {
        // تحقق من user_token
        if (!isset($this->request->get['user_token']) ||
            !isset($this->session->data['user_token']) ||
            $this->request->get['user_token'] != $this->session->data['user_token']
        ) {
            $this->response->redirect($this->url->link('common/login', '', true));
        }

        // تحميل ملف اللغة
        $this->load->language('governance/risk_register');
        $this->document->setTitle($this->language->get('heading_title'));

        // التحقق من صلاحية الوصول
        if (!$this->user->hasPermission('access', 'governance/risk_register')) {
            $this->session->data['error'] = $this->language->get('error_no_permission') 
                                            ?: 'You do not have permission to access Risk Register!';
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
        }

        // (اختياري) جلب مجموعات المستخدم لعرضها بالواجهة
        $this->load->model('user/user_group');
        $data['user_groups'] = $this->model_user_user_group->getUserGroups();

        // العناصر المشتركة (header, column_left, footer)
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        // user_token
        $data['user_token']  = $this->session->data['user_token'];

        // النصوص
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list']     = $this->language->get('text_list');

        // صلاحيات الأزرار (تُستخدم في الـTwig)
        $data['can_add']    = $this->user->hasKey('risk_register_add');
        $data['can_edit']   = $this->user->hasKey('risk_register_edit');
        $data['can_delete'] = $this->user->hasKey('risk_register_delete');

        // فلاتر بسيطة (لو احتجناها في الـtwig)
        $data['risk_category']  = $this->request->get['risk_category']  ?? '';
        $data['status']         = $this->request->get['status']         ?? '';
        $data['nature_of_risk'] = $this->request->get['nature_of_risk'] ?? '';
        $data['owner_group_id'] = $this->request->get['owner_group_id'] ?? '';
        $data['owner_user_id']  = $this->request->get['owner_user_id']  ?? '';
        $data['date_start']     = $this->request->get['date_start']     ?? '';
        $data['date_end']       = $this->request->get['date_end']       ?? '';

        // Handling error/success messages
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

        // BreadCrumbs
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $data['user_token'], true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('governance/risk_register', 'user_token=' . $data['user_token'], true)
        ];

        // عرض القالب
        $this->response->setOutput($this->load->view('governance/risk_register_list', $data));
    }

    /**
     * AJAX: DataTable
     */
    public function ajaxList() {
        if (!$this->user->hasPermission('access', 'governance/risk_register')) {
            return $this->sendJson(['error' => $this->language->get('error_no_permission')]);
        }

        // استقبال الفلاتر
        $filters = [];
        $filters['risk_category']   = $this->request->post['risk_category']   ?? '';
        $filters['status']          = $this->request->post['status']          ?? '';
        $filters['nature_of_risk']  = $this->request->post['nature_of_risk']  ?? '';
        $filters['owner_group_id']  = $this->request->post['owner_group_id']  ?? '';
        $filters['owner_user_id']   = $this->request->post['owner_user_id']   ?? '';
        $filters['date_start']      = $this->request->post['date_start']      ?? '';
        $filters['date_end']        = $this->request->post['date_end']        ?? '';

        $this->load->model('governance/risk_register');
        $risks = $this->model_governance_risk_register->getRisks($filters);

        $data_out = [];
        foreach ($risks as $r) {
            // احسب درجة الخطر إن رغبت
            $risk_score = 0; 
            // $like_val = ($r['likelihood'] === 'low') ? 1 : (($r['likelihood'] === 'medium')?2:3);
            // $impact_val = ($r['impact'] === 'low') ? 1 : (($r['impact'] === 'medium')?2:3);
            // $risk_score = $like_val * $impact_val;

            // دمج اسم المجموعة + المستخدم (إن وجد)
            $owner_label = $r['owner_group_name'] ?: '';
            if (!empty($r['owner_user_name'])) {
                $owner_label .= ($owner_label ? ' - ' : '') . $r['owner_user_name'];
            }

            $data_out[] = [
                'risk_id'       => $r['risk_id'],
                'title'         => $r['title'],
                'risk_category' => $r['risk_category'],
                'likelihood'    => $r['likelihood'],
                'impact'        => $r['impact'],
                'risk_score'    => $risk_score,
                'owner'         => $owner_label,
                'status'        => $r['status'],
                'date_added'    => $r['date_added']
            ];
        }

        return $this->sendJson(['data' => $data_out]);
    }

    /**
     * AJAX: جلب سجل واحد
     */
    public function getOne() {
        if (!$this->user->hasPermission('access', 'governance/risk_register')) {
            return $this->sendJson(['error' => $this->language->get('error_no_permission')]);
        }

        $risk_id = (int)($this->request->post['risk_id'] ?? 0);
        if ($risk_id <= 0) {
            return $this->sendJson(['error' => 'Invalid risk ID']);
        }

        $this->load->model('governance/risk_register');
        $risk = $this->model_governance_risk_register->getRisk($risk_id);
        if (!$risk) {
            return $this->sendJson(['error' => 'Risk Not Found']);
        }

        return $this->sendJson(['success' => true, 'risk' => $risk]);
    }

    /**
     * AJAX: إضافة خطر
     */
    public function ajaxAdd() {
        if (!$this->user->hasKey('risk_register_add')) {
            return $this->sendJson(['error' => 'No permission to add risk.']);
        }

        $this->load->model('governance/risk_register');
        $data_in = [
            'title'          => $this->request->post['title']         ?? '',
            'description'    => $this->request->post['description']   ?? '',
            'risk_category'  => $this->request->post['risk_category'] ?? '',
            'likelihood'     => $this->request->post['likelihood']    ?? 'medium',
            'impact'         => $this->request->post['impact']        ?? 'medium',
            'owner_group_id' => (int)($this->request->post['owner_group_id'] ?? 0),
            'owner_user_id'  => !empty($this->request->post['owner_user_id']) 
                                ? (int)$this->request->post['owner_user_id'] 
                                : null,
            'nature_of_risk' => $this->request->post['nature_of_risk'] ?? 'ongoing',
            'status'         => $this->request->post['status']         ?? 'open',
            'mitigation_plan'=> $this->request->post['mitigation_plan'] ?? '',
            'risk_start_date'=> $this->request->post['risk_start_date'] ?? null,
            'risk_end_date'  => $this->request->post['risk_end_date']   ?? null
        ];

        if (empty($data_in['title'])) {
            return $this->sendJson(['error' => 'Risk title is required.']);
        }

        $risk_id = $this->model_governance_risk_register->addRisk($data_in);
        if ($risk_id) {
            return $this->sendJson(['success' => 'Risk successfully added.', 'risk_id' => $risk_id]);
        } else {
            return $this->sendJson(['error' => 'Failed to add risk.']);
        }
    }

    /**
     * AJAX: تعديل خطر
     */
    public function ajaxEdit() {
        if (!$this->user->hasKey('risk_register_edit')) {
            return $this->sendJson(['error' => 'No permission to edit risk.']);
        }

        $risk_id = (int)($this->request->post['risk_id'] ?? 0);
        if ($risk_id <= 0) {
            return $this->sendJson(['error' => 'Invalid risk ID.']);
        }

        $this->load->model('governance/risk_register');
        $data_in = [
            'title'          => $this->request->post['title']         ?? '',
            'description'    => $this->request->post['description']   ?? '',
            'risk_category'  => $this->request->post['risk_category'] ?? '',
            'likelihood'     => $this->request->post['likelihood']    ?? 'medium',
            'impact'         => $this->request->post['impact']        ?? 'medium',
            'owner_group_id' => (int)($this->request->post['owner_group_id'] ?? 0),
            'owner_user_id'  => !empty($this->request->post['owner_user_id'])
                                ? (int)$this->request->post['owner_user_id'] 
                                : null,
            'nature_of_risk' => $this->request->post['nature_of_risk'] ?? 'ongoing',
            'status'         => $this->request->post['status']         ?? 'open',
            'mitigation_plan'=> $this->request->post['mitigation_plan'] ?? '',
            'risk_start_date'=> $this->request->post['risk_start_date'] ?? null,
            'risk_end_date'  => $this->request->post['risk_end_date']   ?? null
        ];

        if (empty($data_in['title'])) {
            return $this->sendJson(['error' => 'Risk title is required.']);
        }

        $ok = $this->model_governance_risk_register->updateRisk($risk_id, $data_in);
        if ($ok) {
            return $this->sendJson(['success' => 'Risk updated successfully.']);
        } else {
            return $this->sendJson(['error' => 'Failed to update risk.']);
        }
    }

    /**
     * AJAX: حذف خطر
     */
    public function ajaxDelete() {
        if (!$this->user->hasKey('risk_register_delete')) {
            return $this->sendJson(['error' => 'No permission to delete risk.']);
        }

        $risk_id = (int)($this->request->post['risk_id'] ?? 0);
        if ($risk_id <= 0) {
            return $this->sendJson(['error' => 'Invalid risk ID.']);
        }

        $this->load->model('governance/risk_register');
        $deleted = $this->model_governance_risk_register->deleteRisk($risk_id);
        if ($deleted) {
            return $this->sendJson(['success' => 'Risk has been deleted.']);
        } else {
            return $this->sendJson(['error' => 'Failed to delete risk.']);
        }
    }

    /**
     * دالة مساعدة لإرسال JSON
     */
    private function sendJson($data) {
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE));
        return;
    }
}
