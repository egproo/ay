<?php
class ControllerWorkflowWorkflow extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('workflow/workflow');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('workflow/workflow');

		$this->getList();
	}

	public function add() {
		$this->load->language('workflow/workflow');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('workflow/workflow');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$workflow_id = $this->model_workflow_workflow->addWorkflow($this->request->post);

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

			$this->response->redirect($this->url->link('workflow/workflow', 'token=' . $this->session->data['token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('workflow/workflow');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('workflow/workflow');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_workflow_workflow->editWorkflow($this->request->get['workflow_id'], $this->request->post);

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

			$this->response->redirect($this->url->link('workflow/workflow', 'token=' . $this->session->data['token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('workflow/workflow');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('workflow/workflow');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $workflow_id) {
				$this->model_workflow_workflow->deleteWorkflow($workflow_id);
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

			$this->response->redirect($this->url->link('workflow/workflow', 'token=' . $this->session->data['token'] . $url, true));
		}

		$this->getList();
	}
	
	public function designer() {
		$this->load->language('workflow/workflow');

		$this->document->setTitle($this->language->get('heading_title_designer'));
		
		// Add workflow designer stylesheet
		$this->document->addStyle('view/stylesheet/workflow/workflow-designer.css');
		
		// Add workflow designer javascript
		$this->document->addScript('view/javascript/workflow/workflow-designer.js');
		
		$data['heading_title'] = $this->language->get('heading_title_designer');
		
		$data['workflow_id'] = isset($this->request->get['workflow_id']) ? $this->request->get['workflow_id'] : 0;
		
		if ($data['workflow_id']) {
			$this->load->model('workflow/workflow');
			$workflow_info = $this->model_workflow_workflow->getWorkflow($data['workflow_id']);
			
			if ($workflow_info) {
				$data['workflow_name'] = $workflow_info['name'];
				$data['workflow_description'] = $workflow_info['description'];
				$data['workflow_data'] = $workflow_info['workflow_data'];
			} else {
				$this->response->redirect($this->url->link('workflow/workflow', 'token=' . $this->session->data['token'], true));
			}
		} else {
			$data['workflow_name'] = $this->language->get('text_new_workflow');
			$data['workflow_description'] = '';
			$data['workflow_data'] = '{}';
		}
		
		$data['token'] = $this->session->data['token'];
		
		$data['save_url'] = $this->url->link('workflow/workflow/save_workflow', 'token=' . $this->session->data['token'], true);
		$data['cancel_url'] = $this->url->link('workflow/workflow', 'token=' . $this->session->data['token'], true);
		$data['delete_url'] = $this->url->link('workflow/workflow/delete', 'token=' . $this->session->data['token'] . '&workflow_id=' . $data['workflow_id'], true);
		
		// Text translations for template
		$data['text_workflow_designer'] = $this->language->get('text_workflow_designer');
		$data['text_name'] = $this->language->get('text_name');
		$data['text_description'] = $this->language->get('text_description');
		$data['text_save'] = $this->language->get('text_save');
		$data['text_new'] = $this->language->get('text_new');
		$data['text_delete'] = $this->language->get('text_delete');
		$data['text_cancel'] = $this->language->get('text_cancel');
		$data['text_properties'] = $this->language->get('text_properties');
		$data['text_apply'] = $this->language->get('text_apply');
		$data['text_nodes'] = $this->language->get('text_nodes');
		$data['text_node_start'] = $this->language->get('text_node_start');
		$data['text_node_end'] = $this->language->get('text_node_end');
		$data['text_node_task'] = $this->language->get('text_node_task');
		$data['text_node_decision'] = $this->language->get('text_node_decision');
		$data['text_node_email'] = $this->language->get('text_node_email');
		$data['text_node_delay'] = $this->language->get('text_node_delay');
		$data['text_zoom_in'] = $this->language->get('text_zoom_in');
		$data['text_zoom_out'] = $this->language->get('text_zoom_out');
		$data['text_fit'] = $this->language->get('text_fit');
		$data['text_confirm_delete'] = $this->language->get('text_confirm_delete');
		$data['text_confirm_new'] = $this->language->get('text_confirm_new');
		$data['text_workflow_saved'] = $this->language->get('text_workflow_saved');
		$data['text_error_saving'] = $this->language->get('text_error_saving');
		
		// Breadcrumbs
		$data['breadcrumbs'] = array();
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('workflow/workflow', 'token=' . $this->session->data['token'], true)
		);
		
		if ($data['workflow_id']) {
			$data['breadcrumbs'][] = array(
				'text' => $data['workflow_name'],
				'href' => $this->url->link('workflow/workflow/designer', 'token=' . $this->session->data['token'] . '&workflow_id=' . $data['workflow_id'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_new_workflow'),
				'href' => $this->url->link('workflow/workflow/designer', 'token=' . $this->session->data['token'], true)
			);
		}
		
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('workflow/workflow_designer', $data));
	}
	
	public function save_workflow() {
		$json = array();
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($this->request->post['workflow_data'])) {
			$this->load->model('workflow/workflow');
			
			$workflow_data = array(
				'name' => $this->request->post['name'],
				'description' => isset($this->request->post['description']) ? $this->request->post['description'] : '',
				'workflow_data' => $this->request->post['workflow_data'],
				'status' => isset($this->request->post['status']) ? $this->request->post['status'] : 0
			);
			
			if (isset($this->request->post['workflow_id']) && $this->request->post['workflow_id']) {
				$this->model_workflow_workflow->editWorkflow($this->request->post['workflow_id'], $workflow_data);
				$json['workflow_id'] = $this->request->post['workflow_id'];
			} else {
				$json['workflow_id'] = $this->model_workflow_workflow->addWorkflow($workflow_data);
			}
			
			$json['success'] = $this->language->get('text_success_save');
		} else {
			$json['error'] = $this->language->get('error_save');
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function setup() {
		$this->load->language('workflow/workflow');

		$this->document->setTitle($this->language->get('heading_title_setup'));
		
		$data['heading_title'] = $this->language->get('heading_title_setup');
		
		$data['text_database_setup'] = $this->language->get('text_database_setup');
		$data['text_setup_info'] = $this->language->get('text_setup_info');
		$data['text_workflow_tables'] = $this->language->get('text_workflow_tables');
		$data['text_execution_instructions'] = $this->language->get('text_execution_instructions');
		
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_workflow_list'] = $this->language->get('button_workflow_list');
		
		$data['db_prefix'] = DB_PREFIX;
		
		$data['workflow_list'] = $this->url->link('workflow/workflow', 'token=' . $this->session->data['token'], true);
		$data['cancel'] = $this->url->link('workflow/workflow', 'token=' . $this->session->data['token'], true);
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('workflow/workflow', 'token=' . $this->session->data['token'], true)
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title_setup'),
			'href' => $this->url->link('workflow/workflow/setup', 'token=' . $this->session->data['token'], true)
		);
		
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('workflow/sql_setup', $data));
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

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

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('workflow/workflow', 'token=' . $this->session->data['token'] . $url, true)
		);

		$data['add'] = $this->url->link('workflow/workflow/designer', 'token=' . $this->session->data['token'] . $url, true);
		$data['delete'] = $this->url->link('workflow/workflow/delete', 'token=' . $this->session->data['token'] . $url, true);
		$data['setup'] = $this->url->link('workflow/workflow/setup', 'token=' . $this->session->data['token'] . $url, true);

		$data['workflows'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$workflow_total = $this->model_workflow_workflow->getTotalWorkflows();

		$results = $this->model_workflow_workflow->getWorkflows($filter_data);

		foreach ($results as $result) {
			$data['workflows'][] = array(
				'workflow_id' => $result['workflow_id'],
				'name'        => $result['name'],
				'description' => utf8_substr(strip_tags($result['description']), 0, 100) . '..',
				'status'      => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
				'date_added'  => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'edit'        => $this->url->link('workflow/workflow/edit', 'token=' . $this->session->data['token'] . '&workflow_id=' . $result['workflow_id'] . $url, true),
				'design'      => $this->url->link('workflow/workflow/designer', 'token=' . $this->session->data['token'] . '&workflow_id=' . $result['workflow_id'] . $url, true)
			);
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_confirm'] = $this->language->get('text_confirm');
		$data['text_setup'] = $this->language->get('text_setup');

		$data['column_name'] = $this->language->get('column_name');
		$data['column_description'] = $this->language->get('column_description');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_date_added'] = $this->language->get('column_date_added');
		$data['column_action'] = $this->language->get('column_action');

		$data['button_add'] = $this->language->get('button_add');
		$data['button_edit'] = $this->language->get('button_edit');
		$data['button_delete'] = $this->language->get('button_delete');
		$data['button_setup'] = $this->language->get('button_setup');
		$data['button_design'] = $this->language->get('button_design');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('workflow/workflow', 'token=' . $this->session->data['token'] . '&sort=name' . $url, true);
		$data['sort_status'] = $this->url->link('workflow/workflow', 'token=' . $this->session->data['token'] . '&sort=status' . $url, true);
		$data['sort_date_added'] = $this->url->link('workflow/workflow', 'token=' . $this->session->data['token'] . '&sort=date_added' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $workflow_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('workflow/workflow', 'token=' . $this->session->data['token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($workflow_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($workflow_total - $this->config->get('config_limit_admin'))) ? $workflow_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $workflow_total, ceil($workflow_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('workflow/workflow_list', $data));
	}

	protected function getForm() {
		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_form'] = !isset($this->request->get['workflow_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_description'] = $this->language->get('entry_description');
		$data['entry_status'] = $this->language->get('entry_status');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_design'] = $this->language->get('button_design');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

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

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('workflow/workflow', 'token=' . $this->session->data['token'] . $url, true)
		);

		if (!isset($this->request->get['workflow_id'])) {
			$data['action'] = $this->url->link('workflow/workflow/add', 'token=' . $this->session->data['token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('workflow/workflow/edit', 'token=' . $this->session->data['token'] . '&workflow_id=' . $this->request->get['workflow_id'] . $url, true);
			$data['design'] = $this->url->link('workflow/workflow/designer', 'token=' . $this->session->data['token'] . '&workflow_id=' . $this->request->get['workflow_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('workflow/workflow', 'token=' . $this->session->data['token'] . $url, true);

		if (isset($this->request->get['workflow_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$workflow_info = $this->model_workflow_workflow->getWorkflow($this->request->get['workflow_id']);
		}

		$data['token'] = $this->session->data['token'];

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($workflow_info)) {
			$data['name'] = $workflow_info['name'];
		} else {
			$data['name'] = '';
		}

		if (isset($this->request->post['description'])) {
			$data['description'] = $this->request->post['description'];
		} elseif (!empty($workflow_info)) {
			$data['description'] = $workflow_info['description'];
		} else {
			$data['description'] = '';
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($workflow_info)) {
			$data['status'] = $workflow_info['status'];
		} else {
			$data['status'] = 1;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('workflow/workflow_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'workflow/workflow')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 255)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'workflow/workflow')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		// Add check for any active workflow instances
		foreach ($this->request->post['selected'] as $workflow_id) {
			$instance_total = $this->model_workflow_workflow->getActiveInstanceCountByWorkflowId($workflow_id);
			
			if ($instance_total) {
				$this->error['warning'] = $this->language->get('error_active_instances');
				break;
			}
		}

		return !$this->error;
	}
} 