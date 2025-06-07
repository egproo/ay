<?php
class ControllerGovernanceMeetings extends Controller {
    private $error = array();

    /**
     * شاشة القائمة الرئيسة للاجتماعات
     */
    public function index() {
        // 1) تحقق من user_token
        if (!isset($this->request->get['user_token']) 
            || !isset($this->session->data['user_token'])
            || $this->request->get['user_token'] != $this->session->data['user_token']) {
            $this->response->redirect($this->url->link('common/login', '', true));
        }

        // 2) أجزاء واجهة أوبن كارت (هيدر/فوتر/عمود جانبي)
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        // 3) فحص صلاحية الوصول
        if (!$this->user->hasPermission('access','governance/meetings')) {
            $this->session->data['error'] = 'No permission for Meetings!';
            $this->response->redirect($this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true));
        }

        // 4) تحميل اللغة
        $this->load->language('governance/meetings');

        // 5) ضبط العنوان
        $this->document->setTitle($this->language->get('heading_title'));

        // 6) متغيرات الواجهة
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list']     = $this->language->get('text_list');
        $data['user_token']    = $this->session->data['user_token'];

        // صلاحيات
        $data['can_add']         = $this->user->hasKey('meetings_add');
        $data['can_edit']        = $this->user->hasKey('meetings_edit');
        $data['can_delete']      = $this->user->hasKey('meetings_delete');
        // صلاحيات الحضور
        $data['can_add_attendee']    = $this->user->hasKey('meetings_attendees_add');
        $data['can_delete_attendee'] = $this->user->hasKey('meetings_attendees_delete');

        // فلاتر (مثال)
        $data['filter_type'] = $this->request->get['filter_type'] ?? '';

        // رسائل
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

        // breadcrumbs
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard','user_token='.$data['user_token'],true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('governance/meetings','user_token='.$data['user_token'],true)
        ];

        // تحميل القالب
        $this->response->setOutput(
            $this->load->view('governance/meetings_list', $data)
        );
    }

    /**
     * AJAX: جلب قائمة الاجتماعات
     */
    public function ajaxList() {
        if (!$this->user->hasPermission('access','governance/meetings')) {
            return $this->sendJson(['error'=>'No Access']);
        }

        $filter_type = $this->request->post['filter_type'] ?? '';

        $this->load->model('governance/meetings');
        $results = $this->model_governance_meetings->getMeetings(['meeting_type'=>$filter_type]);

        $data_out = [];
        foreach($results as $mt) {
            $data_out[] = [
                'meeting_id'  => $mt['meeting_id'],
                'meeting_type'=> $mt['meeting_type'],
                'title'       => $mt['title'],
                'meeting_date'=> $mt['meeting_date'],
                'location'    => $mt['location'],
                'added_by'    => $mt['added_by_name'] ?? '',
            ];
        }

        return $this->sendJson(['data'=>$data_out]);
    }

    /**
     * AJAX: إضافة اجتماع جديد
     */
    public function ajaxAdd() {
        if (!$this->user->hasKey('meetings_add')) {
            return $this->sendJson(['error'=>'No permission to add']);
        }

        $this->load->model('governance/meetings');

        $data_in = [
            'meeting_type' => $this->request->post['meeting_type'] ?? '',
            'title'        => $this->request->post['title'] ?? '',
            'meeting_date' => $this->request->post['meeting_date'] ?? date('Y-m-d H:i:s'),
            'location'     => $this->request->post['location'] ?? '',
            'agenda'       => $this->request->post['agenda'] ?? '',
            'decisions'    => $this->request->post['decisions'] ?? '',
            'added_by'     => (int)$this->user->getId()
        ];

        // validation
        if(empty($data_in['title'])) {
            return $this->sendJson(['error'=>'Title is required!']);
        }

        $id = $this->model_governance_meetings->addMeeting($data_in);
        if($id) {
            return $this->sendJson(['success'=>'Meeting added','meeting_id'=>$id]);
        } else {
            return $this->sendJson(['error'=>'Failed to insert']);
        }
    }

    /**
     * AJAX: تعديل اجتماع
     */
    public function ajaxEdit() {
        if (!$this->user->hasKey('meetings_edit')) {
            return $this->sendJson(['error'=>'No permission to edit']);
        }

        $meeting_id = (int)($this->request->post['meeting_id'] ?? 0);
        if($meeting_id<=0) {
            return $this->sendJson(['error'=>'Invalid meeting_id']);
        }

        $this->load->model('governance/meetings');

        $data_in = [
            'meeting_type' => $this->request->post['meeting_type'] ?? '',
            'title'        => $this->request->post['title'] ?? '',
            'meeting_date' => $this->request->post['meeting_date'] ?? date('Y-m-d H:i:s'),
            'location'     => $this->request->post['location'] ?? '',
            'agenda'       => $this->request->post['agenda'] ?? '',
            'decisions'    => $this->request->post['decisions'] ?? '',
        ];

        if(empty($data_in['title'])) {
            return $this->sendJson(['error'=>'Title is required!']);
        }

        $ok = $this->model_governance_meetings->updateMeeting($meeting_id, $data_in);
        if($ok) {
            return $this->sendJson(['success'=>'Meeting updated']);
        } else {
            return $this->sendJson(['error'=>'Error updating']);
        }
    }

    /**
     * AJAX: حذف اجتماع
     */
    public function ajaxDelete() {
        if (!$this->user->hasKey('meetings_delete')) {
            return $this->sendJson(['error'=>'No permission to delete']);
        }

        $meeting_id = (int)($this->request->post['meeting_id'] ?? 0);
        if($meeting_id<=0) {
            return $this->sendJson(['error'=>'Invalid meeting_id']);
        }

        $this->load->model('governance/meetings');
        $del = $this->model_governance_meetings->deleteMeeting($meeting_id);
        if($del) {
            return $this->sendJson(['success'=>'Meeting deleted']);
        } else {
            return $this->sendJson(['error'=>'Error deleting meeting']);
        }
    }

    /**
     * AJAX: جلب اجتماع واحد (لدعم زر التعديل)
     */
    public function getOne() {
        if(!$this->user->hasPermission('access','governance/meetings')) {
            return $this->sendJson(['error'=>'No Access']);
        }

        $meeting_id = (int)($this->request->post['meeting_id'] ?? 0);
        if($meeting_id<=0) {
            return $this->sendJson(['error'=>'Invalid meeting_id']);
        }

        $this->load->model('governance/meetings');
        $info = $this->model_governance_meetings->getMeeting($meeting_id);
        if($info) {
            return $this->sendJson(['success'=>true,'data'=>$info]);
        } else {
            return $this->sendJson(['error'=>'Meeting not found']);
        }
    }

    /**
     * ================ [ Attendees Management ] ================
     * AJAX: جلب قائمة الحاضرين لاجتماع محدد
     */
    public function ajaxGetAttendees() {
        if(!$this->user->hasPermission('access','governance/meetings')) {
            return $this->sendJson(['error'=>'No Access']);
        }

        $meeting_id = (int)($this->request->post['meeting_id'] ?? 0);
        if($meeting_id<=0) {
            return $this->sendJson(['error'=>'Invalid meeting_id']);
        }

        $this->load->model('governance/meetings');
        $attendees = $this->model_governance_meetings->getAttendees($meeting_id);

        return $this->sendJson(['success'=>true,'data'=>$attendees]);
    }

    /**
     * AJAX: إضافة حاضر (موظف أو خارجي)
     */
    public function ajaxAddAttendee() {
        if(!$this->user->hasKey('meetings_attendees_add')) {
            return $this->sendJson(['error'=>'No permission to add attendee']);
        }

        $meeting_id = (int)($this->request->post['meeting_id'] ?? 0);
        $user_id    = $this->request->post['user_id'] ?? null;
        $external_name = $this->request->post['external_name'] ?? '';
        $role_in_meeting= $this->request->post['role_in_meeting'] ?? '';
        $presence_status= $this->request->post['presence_status'] ?? 'attended';

        if($meeting_id <= 0) {
            return $this->sendJson(['error'=>'Invalid meeting_id']);
        }

        // لا مانع من أن يكون user_id فارغًا ونستخدم external_name، أو العكس.
        if(empty($user_id) && empty($external_name)) {
            return $this->sendJson(['error'=>'Either user_id or external_name is required!']);
        }

        $this->load->model('governance/meetings');
        $aid = $this->model_governance_meetings->addAttendee($meeting_id, [
            'user_id' => $user_id,
            'external_name' => $external_name,
            'role_in_meeting'=> $role_in_meeting,
            'presence_status'=> $presence_status
        ]);
        if($aid) {
            return $this->sendJson(['success'=>'Attendee added','attendee_id'=>$aid]);
        } else {
            return $this->sendJson(['error'=>'Failed to add attendee']);
        }
    }

    /**
     * AJAX: حذف حاضر
     */
    public function ajaxRemoveAttendee() {
        if(!$this->user->hasKey('meetings_attendees_delete')) {
            return $this->sendJson(['error'=>'No permission to delete attendee']);
        }

        $attendee_id = (int)($this->request->post['attendee_id'] ?? 0);
        if($attendee_id <= 0) {
            return $this->sendJson(['error'=>'Invalid attendee_id']);
        }

        $this->load->model('governance/meetings');
        $ok = $this->model_governance_meetings->removeAttendee($attendee_id);
        if($ok) {
            return $this->sendJson(['success'=>'Attendee removed']);
        } else {
            return $this->sendJson(['error'=>'Error removing attendee']);
        }
    }

    /**
     * مساعد لإرجاع JSON
     */
    private function sendJson($data) {
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE));
        return;
    }
}
