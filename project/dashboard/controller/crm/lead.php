<?php
/**
 * AYM ERP - Advanced CRM Lead Management Controller
 *
 * Professional CRM system with comprehensive lead management
 * Features:
 * - Advanced lead scoring and qualification
 * - Multi-stage sales pipeline management
 * - Automated lead nurturing workflows
 * - Communication tracking and history
 * - Task and activity management
 * - Performance analytics and reporting
 * - Integration with marketing campaigns
 * - AI-powered lead insights
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ControllerCrmLead extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('crm/lead');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('crm/lead');
        $this->load->model('user/user');

        $data['user_token'] = $this->session->data['user_token'];

        // روابط Ajax
        $data['ajax_list_url'] = $this->url->link('crm/lead/list', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_save_url'] = $this->url->link('crm/lead/save', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_get_url']  = $this->url->link('crm/lead/getForm', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_delete_url'] = $this->url->link('crm/lead/delete', 'user_token=' . $this->session->data['user_token'], true);

        // جلب المستخدمين لاختيار assigned_to_user_id
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
        $data['heading_title']      = $this->language->get('heading_title');
        $data['text_filter']        = $this->language->get('text_filter');
        $data['text_lead_name']     = $this->language->get('text_lead_name');
        $data['text_enter_lead_name']= $this->language->get('text_enter_lead_name');
        $data['text_status']        = $this->language->get('text_status');
        $data['text_all_statuses']  = $this->language->get('text_all_statuses');
        $data['text_status_new']    = $this->language->get('text_status_new');
        $data['text_status_contacted']= $this->language->get('text_status_contacted');
        $data['text_status_qualified']= $this->language->get('text_status_qualified');
        $data['text_status_unqualified']= $this->language->get('text_status_unqualified');
        $data['text_status_converted']= $this->language->get('text_status_converted');
        $data['button_filter']      = $this->language->get('button_filter');
        $data['button_reset']       = $this->language->get('button_reset');
        $data['button_add_lead']    = $this->language->get('button_add_lead');
        $data['text_lead_list']     = $this->language->get('text_lead_list');
        $data['text_add_lead']      = $this->language->get('text_add_lead');
        $data['text_edit_lead']     = $this->language->get('text_edit_lead');
        $data['text_ajax_error']    = $this->language->get('text_ajax_error');
        $data['text_confirm_delete']= $this->language->get('text_confirm_delete');
        $data['text_firstname']     = $this->language->get('text_firstname');
        $data['text_lastname']      = $this->language->get('text_lastname');
        $data['text_company']       = $this->language->get('text_company');
        $data['text_email']         = $this->language->get('text_email');
        $data['text_phone']         = $this->language->get('text_phone');
        $data['text_source']        = $this->language->get('text_source');
        $data['text_assigned_to']   = $this->language->get('text_assigned_to');
        $data['text_select_user']   = $this->language->get('text_select_user');
        $data['text_notes']         = $this->language->get('text_notes');
        $data['button_close']       = $this->language->get('button_close');
        $data['button_save']        = $this->language->get('button_save');

        $data['column_name']        = $this->language->get('column_name');
        $data['column_company']     = $this->language->get('column_company');
        $data['column_email']       = $this->language->get('column_email');
        $data['column_phone']       = $this->language->get('column_phone');
        $data['column_status']      = $this->language->get('column_status');
        $data['column_actions']     = $this->language->get('column_actions');

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
            'href' => $this->url->link('crm/lead','user_token=' . $this->session->data['user_token'],true)
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/lead_list', $data));
    }

    public function list() {
        $this->load->language('crm/lead');
        $this->load->model('crm/lead');

        $filter_name = isset($this->request->post['filter_name']) ? $this->request->post['filter_name'] : '';
        $filter_status = isset($this->request->post['filter_status']) ? $this->request->post['filter_status'] : '';

        $start  = isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0;
        $length = isset($this->request->post['length']) ? (int)$this->request->post['length'] : 10;
        $draw   = isset($this->request->post['draw']) ? (int)$this->request->post['draw'] : 1;
        $order_column = isset($this->request->post['order'][0]['column']) ? (int)$this->request->post['order'][0]['column'] : 0;
        $order_dir = isset($this->request->post['order'][0]['dir']) ? $this->request->post['order'][0]['dir'] : 'asc';

        $columns = array('name','company','email','phone','status');
        $sort = isset($columns[$order_column]) ? $columns[$order_column] : 'name';

        $filter_data = array(
            'filter_name'   => $filter_name,
            'filter_status' => $filter_status,
            'start'         => $start,
            'limit'         => $length,
            'sort'          => $sort,
            'order'         => $order_dir
        );

        $total = $this->model_crm_lead->getTotalLeads($filter_data);
        $results = $this->model_crm_lead->getLeads($filter_data);

        $data = array();
        foreach ($results as $result) {
            $actions = '';
            if ($this->user->hasPermission('modify', 'crm/lead')) {
                $actions .= '<button class="btn btn-primary btn-sm btn-edit" data-id="'. $result['lead_id'] .'"><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm btn-delete" data-id="'. $result['lead_id'] .'"><i class="fa fa-trash"></i></button>';
            } else {
                $actions .= '<button class="btn btn-primary btn-sm" disabled><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm" disabled><i class="fa fa-trash"></i></button>';
            }

            $data[] = array(
                'name'    => $result['firstname'] . ' ' . $result['lastname'],
                'company' => $result['company'],
                'email'   => $result['email'],
                'phone'   => $result['phone'],
                'status'  => $this->language->get('text_status_'.$result['status']),
                'actions' => $actions
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
        $this->load->language('crm/lead');
        $this->load->model('crm/lead');

        $json = array();
        if (isset($this->request->post['lead_id'])) {
            $lead_id = (int)$this->request->post['lead_id'];
            $info = $this->model_crm_lead->getLead($lead_id);

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
        $this->load->language('crm/lead');
        $this->load->model('crm/lead');

        $json = array();

        if (!$this->user->hasPermission('modify', 'crm/lead')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $lead_id = isset($this->request->post['lead_id']) ? (int)$this->request->post['lead_id'] : 0;

            $data = array(
                'firstname'           => $this->request->post['firstname'],
                'lastname'            => $this->request->post['lastname'],
                'company'             => $this->request->post['company'],
                'email'               => $this->request->post['email'],
                'phone'               => $this->request->post['phone'],
                'source'              => $this->request->post['source'],
                'status'              => $this->request->post['status'],
                'assigned_to_user_id' => $this->request->post['assigned_to_user_id'],
                'notes'               => $this->request->post['notes']
            );

            if (empty($data['firstname'])) {
                $json['error'] = $this->language->get('error_required');
            } else {
                if ($lead_id) {
                    $this->model_crm_lead->editLead($lead_id, $data);
                    $json['success'] = $this->language->get('text_success_edit');
                } else {
                    $this->model_crm_lead->addLead($data);
                    $json['success'] = $this->language->get('text_success_add');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function delete() {
        $this->load->language('crm/lead');
        $this->load->model('crm/lead');

        $json = array();

        if (!$this->user->hasPermission('modify', 'crm/lead')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['lead_id'])) {
                $lead_id = (int)$this->request->post['lead_id'];
                $this->model_crm_lead->deleteLead($lead_id);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function dashboard() {
        $this->load->language('crm/lead');
        $this->load->model('crm/lead');

        $this->document->setTitle($this->language->get('text_crm_dashboard'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_crm_dashboard'),
            'href' => $this->url->link('crm/lead/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Get CRM statistics
        $data['crm_stats'] = $this->model_crm_lead->getCRMStatistics();

        // Get sales pipeline data
        $data['pipeline_data'] = $this->model_crm_lead->getPipelineData();

        // Get recent activities
        $data['recent_activities'] = $this->model_crm_lead->getRecentActivities(10);

        // Get top leads
        $data['top_leads'] = $this->model_crm_lead->getTopLeads(10);

        // Get conversion funnel
        $data['conversion_funnel'] = $this->model_crm_lead->getConversionFunnel();

        // Get performance metrics
        $data['performance_metrics'] = $this->model_crm_lead->getPerformanceMetrics();

        // Get upcoming tasks
        $data['upcoming_tasks'] = $this->model_crm_lead->getUpcomingTasks(10);

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/lead_dashboard', $data));
    }

    public function pipeline() {
        $this->load->language('crm/lead');
        $this->load->model('crm/lead');

        $this->document->setTitle($this->language->get('text_sales_pipeline'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_sales_pipeline'),
            'href' => $this->url->link('crm/lead/pipeline', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Get pipeline stages
        $data['pipeline_stages'] = $this->model_crm_lead->getPipelineStages();

        // Get leads by stage
        $data['leads_by_stage'] = array();
        foreach ($data['pipeline_stages'] as $stage) {
            $data['leads_by_stage'][$stage['stage_id']] = $this->model_crm_lead->getLeadsByStage($stage['stage_id']);
        }

        // Get pipeline statistics
        $data['pipeline_stats'] = $this->model_crm_lead->getPipelineStatistics();

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/sales_pipeline', $data));
    }

    public function moveStage() {
        $this->load->language('crm/lead');
        $this->load->model('crm/lead');

        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $lead_id = isset($this->request->post['lead_id']) ? (int)$this->request->post['lead_id'] : 0;
            $stage_id = isset($this->request->post['stage_id']) ? (int)$this->request->post['stage_id'] : 0;
            $notes = isset($this->request->post['notes']) ? $this->request->post['notes'] : '';

            try {
                if ($lead_id && $stage_id) {
                    $result = $this->model_crm_lead->moveLeadToStage($lead_id, $stage_id, $notes);

                    if ($result) {
                        $json['success'] = true;
                        $json['message'] = $this->language->get('text_stage_moved_success');

                        // Get updated lead info
                        $json['lead_info'] = $this->model_crm_lead->getLead($lead_id);
                    } else {
                        $json['error'] = $this->language->get('error_stage_move_failed');
                    }
                } else {
                    $json['error'] = $this->language->get('error_invalid_data');
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function addActivity() {
        $this->load->language('crm/lead');
        $this->load->model('crm/lead');

        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $activity_data = $this->request->post;

            try {
                if ($this->validateActivity($activity_data)) {
                    $activity_id = $this->model_crm_lead->addActivity($activity_data['lead_id'], $activity_data);

                    if ($activity_id) {
                        $json['success'] = true;
                        $json['activity_id'] = $activity_id;
                        $json['message'] = $this->language->get('text_activity_added_success');
                    } else {
                        $json['error'] = $this->language->get('error_activity_add_failed');
                    }
                } else {
                    $json['error'] = $this->error;
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function convertToCustomer() {
        $this->load->language('crm/lead');
        $this->load->model('crm/lead');

        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $lead_id = isset($this->request->post['lead_id']) ? (int)$this->request->post['lead_id'] : 0;

            try {
                if ($lead_id) {
                    $customer_id = $this->model_crm_lead->convertLeadToCustomer($lead_id);

                    if ($customer_id) {
                        $json['success'] = true;
                        $json['customer_id'] = $customer_id;
                        $json['message'] = $this->language->get('text_lead_converted_success');
                        $json['redirect_url'] = $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $customer_id, true);
                    } else {
                        $json['error'] = $this->language->get('error_conversion_failed');
                    }
                } else {
                    $json['error'] = $this->language->get('error_invalid_lead');
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function leadScoring() {
        $this->load->language('crm/lead');
        $this->load->model('crm/lead');

        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $lead_id = isset($this->request->post['lead_id']) ? (int)$this->request->post['lead_id'] : 0;

            try {
                if ($lead_id) {
                    $score = $this->model_crm_lead->calculateLeadScore($lead_id);

                    if ($score !== false) {
                        $json['success'] = true;
                        $json['score'] = $score;
                        $json['score_breakdown'] = $this->model_crm_lead->getLeadScoreBreakdown($lead_id);
                        $json['message'] = $this->language->get('text_score_calculated_success');
                    } else {
                        $json['error'] = $this->language->get('error_score_calculation_failed');
                    }
                } else {
                    $json['error'] = $this->language->get('error_invalid_lead');
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validateActivity($data) {
        if (empty($data['lead_id'])) {
            $this->error['lead'] = $this->language->get('error_lead_required');
        }

        if (empty($data['type'])) {
            $this->error['type'] = $this->language->get('error_activity_type_required');
        }

        if (empty($data['subject'])) {
            $this->error['subject'] = $this->language->get('error_subject_required');
        }

        return !$this->error;
    }
}
