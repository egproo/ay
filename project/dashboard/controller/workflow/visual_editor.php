<?php
class ControllerWorkflowVisualEditor extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('workflow/visual_editor');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('workflow/workflow');

		if (isset($this->request->get['workflow_id'])) {
			$workflow_id = $this->request->get['workflow_id'];
		} else {
			$workflow_id = 0;
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			if ($workflow_id) {
				$this->model_workflow_workflow->editWorkflow($workflow_id, $this->request->post);
			} else {
				$workflow_id = $this->model_workflow_workflow->addWorkflow($this->request->post);
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

			$this->response->redirect($this->url->link('workflow/workflow', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	protected function getForm() {
		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_form'] = !isset($this->request->get['workflow_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		$data['text_none'] = $this->language->get('text_none');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_active'] = $this->language->get('text_active');
		$data['text_inactive'] = $this->language->get('text_inactive');
		$data['text_archived'] = $this->language->get('text_archived');
		$data['text_nodes'] = $this->language->get('text_nodes');
		$data['text_trigger'] = $this->language->get('text_trigger');
		$data['text_approval'] = $this->language->get('text_approval');
		$data['text_condition'] = $this->language->get('text_condition');
		$data['text_email'] = $this->language->get('text_email');
		$data['text_notification'] = $this->language->get('text_notification');
		$data['text_task'] = $this->language->get('text_task');
		$data['text_delay'] = $this->language->get('text_delay');
		$data['text_webhook'] = $this->language->get('text_webhook');
		$data['text_function'] = $this->language->get('text_function');
		$data['text_node'] = $this->language->get('text_node');
		$data['text_click_to_configure'] = $this->language->get('text_click_to_configure');
		$data['text_configure'] = $this->language->get('text_configure');
		$data['text_configure_node'] = $this->language->get('text_configure_node');
		$data['text_approver'] = $this->language->get('text_approver');
		$data['text_recipient'] = $this->language->get('text_recipient');

		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_description'] = $this->language->get('entry_description');
		$data['entry_workflow_type'] = $this->language->get('entry_workflow_type');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_department'] = $this->language->get('entry_department');
		$data['entry_escalation_enabled'] = $this->language->get('entry_escalation_enabled');
		$data['entry_escalation_after_days'] = $this->language->get('entry_escalation_after_days');
		$data['entry_notify_creator'] = $this->language->get('entry_notify_creator');

		$data['tab_general'] = $this->language->get('tab_general');
		$data['tab_visual_editor'] = $this->language->get('tab_visual_editor');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

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

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_workflow'),
			'href' => $this->url->link('workflow/workflow', 'user_token=' . $this->session->data['user_token'], true)
		);

		if (!isset($this->request->get['workflow_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_add'),
				'href' => $this->url->link('workflow/visual_editor', 'user_token=' . $this->session->data['user_token'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_edit'),
				'href' => $this->url->link('workflow/visual_editor', 'user_token=' . $this->session->data['user_token'] . '&workflow_id=' . $this->request->get['workflow_id'], true)
			);
		}

		if (!isset($this->request->get['workflow_id'])) {
			$data['action'] = $this->url->link('workflow/visual_editor', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('workflow/visual_editor', 'user_token=' . $this->session->data['user_token'] . '&workflow_id=' . $this->request->get['workflow_id'], true);
		}

		$data['cancel'] = $this->url->link('workflow/workflow', 'user_token=' . $this->session->data['user_token'], true);

		// Load workflow data if editing
		if (isset($this->request->get['workflow_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$workflow_info = $this->model_workflow_workflow->getWorkflow($this->request->get['workflow_id']);
		}

		// Form data
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

		if (isset($this->request->post['workflow_type'])) {
			$data['workflow_type'] = $this->request->post['workflow_type'];
		} elseif (!empty($workflow_info)) {
			$data['workflow_type'] = $workflow_info['workflow_type'];
		} else {
			$data['workflow_type'] = 'document_approval';
		}

		// Workflow types
		$data['workflow_types'] = array();
		$data['workflow_types'][] = array('value' => 'document_approval', 'text' => $this->language->get('text_document_approval'));
		$data['workflow_types'][] = array('value' => 'purchase_approval', 'text' => $this->language->get('text_purchase_approval'));
		$data['workflow_types'][] = array('value' => 'leave_request', 'text' => $this->language->get('text_leave_request'));
		$data['workflow_types'][] = array('value' => 'expense_claim', 'text' => $this->language->get('text_expense_claim'));
		$data['workflow_types'][] = array('value' => 'payment_approval', 'text' => $this->language->get('text_payment_approval'));
		$data['workflow_types'][] = array('value' => 'other', 'text' => $this->language->get('text_other'));

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($workflow_info)) {
			$data['status'] = $workflow_info['status'];
		} else {
			$data['status'] = 'active';
		}

		if (isset($this->request->post['department_id'])) {
			$data['department_id'] = $this->request->post['department_id'];
		} elseif (!empty($workflow_info)) {
			$data['department_id'] = $workflow_info['department_id'];
		} else {
			$data['department_id'] = 0;
		}

		// Load departments
		$this->load->model('hr/department');
		$data['departments'] = $this->model_hr_department->getDepartments();

		if (isset($this->request->post['escalation_enabled'])) {
			$data['escalation_enabled'] = $this->request->post['escalation_enabled'];
		} elseif (!empty($workflow_info)) {
			$data['escalation_enabled'] = $workflow_info['escalation_enabled'];
		} else {
			$data['escalation_enabled'] = 0;
		}

		if (isset($this->request->post['escalation_after_days'])) {
			$data['escalation_after_days'] = $this->request->post['escalation_after_days'];
		} elseif (!empty($workflow_info)) {
			$data['escalation_after_days'] = $workflow_info['escalation_after_days'];
		} else {
			$data['escalation_after_days'] = 3;
		}

		if (isset($this->request->post['notify_creator'])) {
			$data['notify_creator'] = $this->request->post['notify_creator'];
		} elseif (!empty($workflow_info)) {
			$data['notify_creator'] = $workflow_info['notify_creator'];
		} else {
			$data['notify_creator'] = 1;
		}

		if (isset($this->request->post['workflow_json'])) {
			$data['workflow_json'] = $this->request->post['workflow_json'];
		} elseif (!empty($workflow_info) && isset($workflow_info['workflow_json'])) {
			$data['workflow_json'] = $workflow_info['workflow_json'];
		} else {
			$data['workflow_json'] = '';
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('workflow/visual_editor', $data));
	}

	public function getNodeConfig() {
		$this->load->language('workflow/visual_editor');

		$json = array();

		if (isset($this->request->post['node_id']) && isset($this->request->post['node_type'])) {
			$node_id = $this->request->post['node_id'];
			$node_type = $this->request->post['node_type'];

			// Load appropriate config form template based on node type
			$html = $this->load->view('workflow/node/' . $node_type, array(
				'node_id' => $node_id,
				'user_token' => $this->session->data['user_token']
			));

			$this->response->setOutput($html);
		} else {
			// Return error
			$json['error'] = $this->language->get('error_invalid_node');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
		}
	}

	public function saveNodeConfig() {
		$this->load->language('workflow/visual_editor');

		$json = array();

		if (isset($this->request->post['node_id']) && isset($this->request->post['node_type'])) {
			$node_id = $this->request->post['node_id'];
			$node_type = $this->request->post['node_type'];

			// Process form data based on node type
			$config_data = array();

			// Common fields for all node types
			if (isset($this->request->post['node_name'])) {
				$config_data['name'] = $this->request->post['node_name'];
			}

			if (isset($this->request->post['node_description'])) {
				$config_data['description'] = $this->request->post['node_description'];
			}

			// Node type specific data
			switch ($node_type) {
				case 'approval':
					if (isset($this->request->post['approver_type'])) {
						$config_data['approver_type'] = $this->request->post['approver_type'];
						
						if ($this->request->post['approver_type'] == 'user') {
							$config_data['approver_user_id'] = (int)$this->request->post['approver_user_id'];
							
							// Get user name for display
							$this->load->model('user/user');
							$user_info = $this->model_user_user->getUser($config_data['approver_user_id']);
							if ($user_info) {
								$config_data['approver_name'] = $user_info['firstname'] . ' ' . $user_info['lastname'];
							}
						} else {
							$config_data['approver_group_id'] = (int)$this->request->post['approver_group_id'];
							
							// Get group name for display
							$this->load->model('user/user_group');
							$group_info = $this->model_user_user_group->getUserGroup($config_data['approver_group_id']);
							if ($group_info) {
								$config_data['approver_name'] = $group_info['name'];
							}
						}
					}
					
					if (isset($this->request->post['approval_type'])) {
						$config_data['approval_type'] = $this->request->post['approval_type'];
					}
					break;
					
				case 'email':
					if (isset($this->request->post['recipient'])) {
						$config_data['recipient'] = $this->request->post['recipient'];
					}
					
					if (isset($this->request->post['subject'])) {
						$config_data['subject'] = $this->request->post['subject'];
					}
					
					if (isset($this->request->post['message'])) {
						$config_data['message'] = $this->request->post['message'];
					}
					break;
					
				case 'notification':
					if (isset($this->request->post['notification_type'])) {
						$config_data['notification_type'] = $this->request->post['notification_type'];
					}
					
					if (isset($this->request->post['notification_title'])) {
						$config_data['notification_title'] = $this->request->post['notification_title'];
					}
					
					if (isset($this->request->post['notification_message'])) {
						$config_data['notification_message'] = $this->request->post['notification_message'];
					}
					
					if (isset($this->request->post['notification_priority'])) {
						$config_data['notification_priority'] = $this->request->post['notification_priority'];
					}
					break;
					
				// Add more node types as needed
			}

			$json['success'] = true;
			$json['data'] = $config_data;
		} else {
			$json['error'] = $this->language->get('error_invalid_node');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
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
} 