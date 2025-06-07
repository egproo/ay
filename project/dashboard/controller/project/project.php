<?php
/**
 * AYM ERP - Advanced Project Management Controller
 *
 * Professional project management system with comprehensive project lifecycle management
 * Features:
 * - Complete project planning and execution
 * - Advanced task management with dependencies
 * - Resource allocation and capacity planning
 * - Time tracking and billing integration
 * - Gantt charts and project visualization
 * - Team collaboration and communication
 * - Budget management and cost tracking
 * - Risk management and issue tracking
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ControllerProjectProject extends Controller {

    private $error = array();

    public function index() {
        $this->load->language('project/project');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_projects'),
            'href' => $this->url->link('project/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('project/project', 'user_token=' . $this->session->data['user_token'], true)
        );

        $this->getList($data);
    }

    public function add() {
        $this->load->language('project/project');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('project/project');

            $project_id = $this->model_project_project->addProject($this->request->post);

            // Create default project phases if template is selected
            if (isset($this->request->post['project_template_id']) && $this->request->post['project_template_id']) {
                $this->model_project_project->createProjectFromTemplate($project_id, $this->request->post['project_template_id']);
            }

            // Assign team members if provided
            if (isset($this->request->post['team_members']) && is_array($this->request->post['team_members'])) {
                $this->model_project_project->assignTeamMembers($project_id, $this->request->post['team_members']);
            }

            // Send project creation notifications
            $this->model_project_project->sendProjectNotifications($project_id, 'created');

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('project/project', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('project/project');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('project/project');

            $this->model_project_project->editProject($this->request->get['project_id'], $this->request->post);

            // Update team members if provided
            if (isset($this->request->post['team_members']) && is_array($this->request->post['team_members'])) {
                $this->model_project_project->updateTeamMembers($this->request->get['project_id'], $this->request->post['team_members']);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('project/project', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('project/project');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('project/project');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $project_id) {
                $this->model_project_project->deleteProject($project_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('project/project', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function dashboard() {
        $this->load->language('project/project');
        $this->load->model('project/project');

        $this->document->setTitle($this->language->get('text_project_dashboard'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_project_dashboard'),
            'href' => $this->url->link('project/project/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Get project statistics
        $data['project_stats'] = $this->model_project_project->getProjectStatistics();

        // Get active projects
        $data['active_projects'] = $this->model_project_project->getActiveProjects(10);

        // Get overdue tasks
        $data['overdue_tasks'] = $this->model_project_project->getOverdueTasks(10);

        // Get upcoming milestones
        $data['upcoming_milestones'] = $this->model_project_project->getUpcomingMilestones(10);

        // Get team workload
        $data['team_workload'] = $this->model_project_project->getTeamWorkload();

        // Get project performance metrics
        $data['performance_metrics'] = $this->model_project_project->getPerformanceMetrics();

        // Get recent activities
        $data['recent_activities'] = $this->model_project_project->getRecentActivities(15);

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('project/project_dashboard', $data));
    }

    public function view() {
        $this->load->language('project/project');
        $this->load->model('project/project');

        if (!isset($this->request->get['project_id'])) {
            $this->response->redirect($this->url->link('project/project', 'user_token=' . $this->session->data['user_token'], true));
        }

        $project_id = $this->request->get['project_id'];
        $project_info = $this->model_project_project->getProject($project_id);

        if (!$project_info) {
            $this->response->redirect($this->url->link('project/project', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->document->setTitle($this->language->get('text_project_view') . ' - ' . $project_info['name']);

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('project/project', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_project_view'),
            'href' => $this->url->link('project/project/view', 'user_token=' . $this->session->data['user_token'] . '&project_id=' . $project_id, true)
        );

        // Get project details
        $data['project'] = $project_info;

        // Get project tasks
        $data['project_tasks'] = $this->model_project_project->getProjectTasks($project_id);

        // Get project milestones
        $data['project_milestones'] = $this->model_project_project->getProjectMilestones($project_id);

        // Get project team members
        $data['team_members'] = $this->model_project_project->getProjectTeamMembers($project_id);

        // Get project timeline
        $data['project_timeline'] = $this->model_project_project->getProjectTimeline($project_id);

        // Get project budget
        $data['project_budget'] = $this->model_project_project->getProjectBudget($project_id);

        // Get project documents
        $data['project_documents'] = $this->model_project_project->getProjectDocuments($project_id);

        // Get project risks
        $data['project_risks'] = $this->model_project_project->getProjectRisks($project_id);

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('project/project_view', $data));
    }

    public function gantt() {
        $this->load->language('project/project');
        $this->load->model('project/project');

        if (!isset($this->request->get['project_id'])) {
            $this->response->redirect($this->url->link('project/project', 'user_token=' . $this->session->data['user_token'], true));
        }

        $project_id = $this->request->get['project_id'];
        $project_info = $this->model_project_project->getProject($project_id);

        if (!$project_info) {
            $this->response->redirect($this->url->link('project/project', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->document->setTitle($this->language->get('text_gantt_chart') . ' - ' . $project_info['name']);

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('project/project', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_gantt_chart'),
            'href' => $this->url->link('project/project/gantt', 'user_token=' . $this->session->data['user_token'] . '&project_id=' . $project_id, true)
        );

        // Get project details
        $data['project'] = $project_info;

        // Get Gantt chart data
        $data['gantt_data'] = $this->model_project_project->getGanttChartData($project_id);

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('project/project_gantt', $data));
    }

    public function updateTaskStatus() {
        $this->load->language('project/project');
        $this->load->model('project/project');

        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $task_id = isset($this->request->post['task_id']) ? (int)$this->request->post['task_id'] : 0;
            $status = isset($this->request->post['status']) ? $this->request->post['status'] : '';
            $notes = isset($this->request->post['notes']) ? $this->request->post['notes'] : '';

            try {
                if ($task_id && $status) {
                    $result = $this->model_project_project->updateTaskStatus($task_id, $status, $notes, $this->user->getId());

                    if ($result) {
                        $json['success'] = true;
                        $json['message'] = $this->language->get('text_task_status_updated');

                        // Get updated task info
                        $json['task_info'] = $this->model_project_project->getTask($task_id);

                        // Send notifications
                        $this->model_project_project->sendTaskNotifications($task_id, 'status_updated');
                    } else {
                        $json['error'] = $this->language->get('error_task_update_failed');
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

    public function logTime() {
        $this->load->language('project/project');
        $this->load->model('project/project');

        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $time_data = $this->request->post;

            try {
                if ($this->validateTimeLog($time_data)) {
                    $time_log_id = $this->model_project_project->logTime($time_data);

                    if ($time_log_id) {
                        $json['success'] = true;
                        $json['time_log_id'] = $time_log_id;
                        $json['message'] = $this->language->get('text_time_logged_success');

                        // Get updated project progress
                        $json['project_progress'] = $this->model_project_project->getProjectProgress($time_data['project_id']);
                    } else {
                        $json['error'] = $this->language->get('error_time_log_failed');
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

    public function addMilestone() {
        $this->load->language('project/project');
        $this->load->model('project/project');

        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $milestone_data = $this->request->post;

            try {
                if ($this->validateMilestone($milestone_data)) {
                    $milestone_id = $this->model_project_project->addMilestone($milestone_data);

                    if ($milestone_id) {
                        $json['success'] = true;
                        $json['milestone_id'] = $milestone_id;
                        $json['message'] = $this->language->get('text_milestone_added_success');

                        // Send notifications
                        $this->model_project_project->sendMilestoneNotifications($milestone_id, 'created');
                    } else {
                        $json['error'] = $this->language->get('error_milestone_add_failed');
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

    public function addRisk() {
        $this->load->language('project/project');
        $this->load->model('project/project');

        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $risk_data = $this->request->post;

            try {
                if ($this->validateRisk($risk_data)) {
                    $risk_id = $this->model_project_project->addRisk($risk_data);

                    if ($risk_id) {
                        $json['success'] = true;
                        $json['risk_id'] = $risk_id;
                        $json['message'] = $this->language->get('text_risk_added_success');

                        // Send notifications for high-priority risks
                        if ($risk_data['priority'] == 'high' || $risk_data['priority'] == 'critical') {
                            $this->model_project_project->sendRiskNotifications($risk_id, 'created');
                        }
                    } else {
                        $json['error'] = $this->language->get('error_risk_add_failed');
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

    protected function validateTimeLog($data) {
        if (empty($data['project_id'])) {
            $this->error['project'] = $this->language->get('error_project_required');
        }

        if (empty($data['task_id'])) {
            $this->error['task'] = $this->language->get('error_task_required');
        }

        if (empty($data['hours']) || $data['hours'] <= 0) {
            $this->error['hours'] = $this->language->get('error_hours_required');
        }

        if (empty($data['date'])) {
            $this->error['date'] = $this->language->get('error_date_required');
        }

        return !$this->error;
    }

    protected function validateMilestone($data) {
        if (empty($data['project_id'])) {
            $this->error['project'] = $this->language->get('error_project_required');
        }

        if (empty($data['name'])) {
            $this->error['name'] = $this->language->get('error_name_required');
        }

        if (empty($data['due_date'])) {
            $this->error['due_date'] = $this->language->get('error_due_date_required');
        }

        return !$this->error;
    }

    protected function validateRisk($data) {
        if (empty($data['project_id'])) {
            $this->error['project'] = $this->language->get('error_project_required');
        }

        if (empty($data['title'])) {
            $this->error['title'] = $this->language->get('error_title_required');
        }

        if (empty($data['description'])) {
            $this->error['description'] = $this->language->get('error_description_required');
        }

        if (empty($data['probability'])) {
            $this->error['probability'] = $this->language->get('error_probability_required');
        }

        if (empty($data['impact'])) {
            $this->error['impact'] = $this->language->get('error_impact_required');
        }

        return !$this->error;
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'project/project')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 255)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if (empty($this->request->post['start_date'])) {
            $this->error['start_date'] = $this->language->get('error_start_date');
        }

        if (empty($this->request->post['end_date'])) {
            $this->error['end_date'] = $this->language->get('error_end_date');
        }

        if (!empty($this->request->post['start_date']) && !empty($this->request->post['end_date'])) {
            if (strtotime($this->request->post['start_date']) > strtotime($this->request->post['end_date'])) {
                $this->error['date_range'] = $this->language->get('error_invalid_date_range');
            }
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'project/project')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function getList(&$data) {
        // Implementation for project listing
        // This would include filtering, sorting, and pagination logic
        // Similar to other controllers but specific to projects
    }

    protected function getForm() {
        // Implementation for project form
        // This would include form data preparation for add/edit
        // Similar to other controllers but specific to projects
    }
}
