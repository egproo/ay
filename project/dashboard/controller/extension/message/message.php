<?php
/**
 * @package     AYM CMS
 * @author      Team AYM <info@aymcms.com>
 * @copyright   Copyright (c) 2021 AYM. (https://www.aymcms.com)
 * @license     https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 */

class ControllerExtensionMessageMessage extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/message/message');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/message');

		$this->getList();
	}

	public function compose() {
		$this->load->language('extension/message/message');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/message');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$message_id = $this->model_extension_message->addMessage($this->request->post);
			
			$this->session->data['success'] = $this->language->get('text_success_send');

			$this->response->redirect($this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function reply() {
		$this->load->language('extension/message/message');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/message');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$message_id = $this->model_extension_message->addMessage($this->request->post);
			
			$this->session->data['success'] = $this->language->get('text_success_send');

			$this->response->redirect($this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function view() {
		$this->load->language('extension/message/message');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/message');

		$this->getView();
	}

	public function delete() {
		$this->load->language('extension/message/message');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/message');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $message_id) {
				$this->model_extension_message->deleteMessage($message_id);
			}

			$this->session->data['success'] = $this->language->get('text_success_delete');

			$url = '';

			if (isset($this->request->get['filter_subject'])) {
				$url .= '&filter_subject=' . urlencode(html_entity_decode($this->request->get['filter_subject'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_date'])) {
				$url .= '&filter_date=' . $this->request->get['filter_date'];
			}

			if (isset($this->request->get['filter_read'])) {
				$url .= '&filter_read=' . $this->request->get['filter_read'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	public function markRead() {
		$this->load->language('extension/message/message');

		$json = array();

		if (isset($this->request->post['message_id']) && $this->validatePermission()) {
			$this->load->model('extension/message');

			$this->model_extension_message->markMessageRead($this->request->post['message_id']);

			$json['success'] = $this->language->get('text_success_mark');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function markUnread() {
		$this->load->language('extension/message/message');

		$json = array();

		if (isset($this->request->post['message_id']) && $this->validatePermission()) {
			$this->load->model('extension/message');

			$this->model_extension_message->markMessageUnread($this->request->post['message_id']);

			$json['success'] = $this->language->get('text_success_mark');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function getList() {
		if (isset($this->request->get['filter_subject'])) {
			$filter_subject = $this->request->get['filter_subject'];
		} else {
			$filter_subject = '';
		}

		if (isset($this->request->get['filter_date'])) {
			$filter_date = $this->request->get['filter_date'];
		} else {
			$filter_date = '';
		}

		if (isset($this->request->get['filter_read'])) {
			$filter_read = $this->request->get['filter_read'];
		} else {
			$filter_read = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'm.date_added';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['filter_subject'])) {
			$url .= '&filter_subject=' . urlencode(html_entity_decode($this->request->get['filter_subject'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_date'])) {
			$url .= '&filter_date=' . $this->request->get['filter_date'];
		}

		if (isset($this->request->get['filter_read'])) {
			$url .= '&filter_read=' . $this->request->get['filter_read'];
		}

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
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['compose'] = $this->url->link('extension/message/message/compose', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('extension/message/message/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['messages'] = array();

		$filter_data = array(
			'filter_subject'  => $filter_subject,
			'filter_date'     => $filter_date,
			'filter_read'     => $filter_read,
			'sort'            => $sort,
			'order'           => $order,
			'start'           => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'           => $this->config->get('config_limit_admin')
		);

		$message_total = $this->model_extension_message->getTotalMessages($filter_data);

		$results = $this->model_extension_message->getMessages($filter_data);

		foreach ($results as $result) {
			$data['messages'][] = array(
				'message_id'  => $result['message_id'],
				'subject'     => $result['subject'],
				'sender'      => $result['sender'],
				'date_added'  => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'read'        => $result['read'],
				'view'        => $this->url->link('extension/message/message/view', 'user_token=' . $this->session->data['user_token'] . '&message_id=' . $result['message_id'] . $url, true),
				'reply'       => $this->url->link('extension/message/message/reply', 'user_token=' . $this->session->data['user_token'] . '&message_id=' . $result['message_id'] . $url, true)
			);
		}

		$data['user_token'] = $this->session->data['user_token'];

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

		if (isset($this->request->get['filter_subject'])) {
			$url .= '&filter_subject=' . urlencode(html_entity_decode($this->request->get['filter_subject'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_date'])) {
			$url .= '&filter_date=' . $this->request->get['filter_date'];
		}

		if (isset($this->request->get['filter_read'])) {
			$url .= '&filter_read=' . $this->request->get['filter_read'];
		}

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_subject'] = $this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'] . '&sort=m.subject' . $url, true);
		$data['sort_sender'] = $this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'] . '&sort=m.sender' . $url, true);
		$data['sort_date_added'] = $this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'] . '&sort=m.date_added' . $url, true);

		$url = '';

		if (isset($this->request->get['filter_subject'])) {
			$url .= '&filter_subject=' . urlencode(html_entity_decode($this->request->get['filter_subject'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_date'])) {
			$url .= '&filter_date=' . $this->request->get['filter_date'];
		}

		if (isset($this->request->get['filter_read'])) {
			$url .= '&filter_read=' . $this->request->get['filter_read'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $message_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($message_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($message_total - $this->config->get('config_limit_admin'))) ? $message_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $message_total, ceil($message_total / $this->config->get('config_limit_admin')));

		$data['filter_subject'] = $filter_subject;
		$data['filter_date'] = $filter_date;
		$data['filter_read'] = $filter_read;

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/message/message_list', $data));
	}

	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['message_id']) ? $this->language->get('text_compose') : $this->language->get('text_reply');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['to'])) {
			$data['error_to'] = $this->error['to'];
		} else {
			$data['error_to'] = '';
		}

		if (isset($this->error['subject'])) {
			$data['error_subject'] = $this->error['subject'];
		} else {
			$data['error_subject'] = '';
		}

		if (isset($this->error['message'])) {
			$data['error_message'] = $this->error['message'];
		} else {
			$data['error_message'] = '';
		}

		$url = '';

		if (isset($this->request->get['filter_subject'])) {
			$url .= '&filter_subject=' . urlencode(html_entity_decode($this->request->get['filter_subject'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_date'])) {
			$url .= '&filter_date=' . $this->request->get['filter_date'];
		}

		if (isset($this->request->get['filter_read'])) {
			$url .= '&filter_read=' . $this->request->get['filter_read'];
		}

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
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $data['text_form'],
			'href' => $this->url->link('extension/message/message/compose', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['message_id'])) {
			$data['action'] = $this->url->link('extension/message/message/compose', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('extension/message/message/reply', 'user_token=' . $this->session->data['user_token'] . '&message_id=' . $this->request->get['message_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['message_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$message_info = $this->model_extension_message->getMessage($this->request->get['message_id']);
		}

		$this->load->model('user/user');

		$data['users'] = $this->model_user_user->getUsers();

		if (isset($this->request->post['to'])) {
			$data['to'] = $this->request->post['to'];
		} elseif (!empty($message_info)) {
			$data['to'] = $message_info['sender_id'];
		} else {
			$data['to'] = array();
		}

		if (isset($this->request->post['subject'])) {
			$data['subject'] = $this->request->post['subject'];
		} elseif (!empty($message_info)) {
			$data['subject'] = 'RE: ' . $message_info['subject'];
		} else {
			$data['subject'] = '';
		}

		if (isset($this->request->post['message'])) {
			$data['message'] = $this->request->post['message'];
		} elseif (!empty($message_info)) {
			$data['message'] = '';
		} else {
			$data['message'] = '';
		}

		// Get original message content for reply quoting
		if (!empty($message_info)) {
			$data['original_message'] = $message_info['message'];
			$data['original_sender'] = $message_info['sender'];
			$data['original_date'] = date($this->language->get('date_format_short'), strtotime($message_info['date_added']));
		} else {
			$data['original_message'] = '';
			$data['original_sender'] = '';
			$data['original_date'] = '';
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/message/message_form', $data));
	}

	protected function getView() {
		if (isset($this->request->get['message_id'])) {
			$message_id = $this->request->get['message_id'];
		} else {
			$message_id = 0;
		}

		if (!$this->validatePermission()) {
			$this->response->redirect($this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'], true));
		}

		$message_info = $this->model_extension_message->getMessage($message_id);

		if (!$message_info) {
			$this->response->redirect($this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'], true));
		}

		// Mark as read when viewed
		if (!$message_info['read']) {
			$this->model_extension_message->markMessageRead($message_id);
		}

		$data['subject'] = $message_info['subject'];
		$data['message'] = nl2br($message_info['message']);
		$data['sender'] = $message_info['sender'];
		$data['date_added'] = date($this->language->get('date_format_short'), strtotime($message_info['date_added']));

		// Get message attachments
		$data['attachments'] = array();
		
		$attachments = $this->model_extension_message->getMessageAttachments($message_id);
		
		foreach ($attachments as $attachment) {
			$data['attachments'][] = array(
				'name' => $attachment['name'],
				'href' => $this->url->link('extension/message/message/download', 'user_token=' . $this->session->data['user_token'] . '&attachment_id=' . $attachment['attachment_id'], true)
			);
		}

		$url = '';

		if (isset($this->request->get['filter_subject'])) {
			$url .= '&filter_subject=' . urlencode(html_entity_decode($this->request->get['filter_subject'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_date'])) {
			$url .= '&filter_date=' . $this->request->get['filter_date'];
		}

		if (isset($this->request->get['filter_read'])) {
			$url .= '&filter_read=' . $this->request->get['filter_read'];
		}

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
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_view'),
			'href' => $this->url->link('extension/message/message/view', 'user_token=' . $this->session->data['user_token'] . '&message_id=' . $message_id . $url, true)
		);

		$data['back'] = $this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['reply'] = $this->url->link('extension/message/message/reply', 'user_token=' . $this->session->data['user_token'] . '&message_id=' . $message_id . $url, true);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/message/message_view', $data));
	}

	public function download() {
		$this->load->language('extension/message/message');

		if (isset($this->request->get['attachment_id'])) {
			$attachment_id = $this->request->get['attachment_id'];
		} else {
			$attachment_id = 0;
		}

		if (!$this->validatePermission()) {
			$this->response->redirect($this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->model('extension/message');

		$attachment_info = $this->model_extension_message->getAttachment($attachment_id);

		if ($attachment_info) {
			$file = DIR_UPLOAD . $attachment_info['filename'];

			if (file_exists($file)) {
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="' . $attachment_info['name'] . '"');
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file));

				readfile($file, 'rb');
				exit;
			} else {
				$this->session->data['error'] = $this->language->get('error_file');

				$this->response->redirect($this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'], true));
			}
		} else {
			$this->response->redirect($this->url->link('extension/message/message', 'user_token=' . $this->session->data['user_token'], true));
		}
	}

	protected function validateForm() {
		if (!$this->validatePermission()) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (empty($this->request->post['to'])) {
			$this->error['to'] = $this->language->get('error_to');
		}

		if ((utf8_strlen($this->request->post['subject']) < 3) || (utf8_strlen($this->request->post['subject']) > 255)) {
			$this->error['subject'] = $this->language->get('error_subject');
		}

		if (utf8_strlen($this->request->post['message']) < 10) {
			$this->error['message'] = $this->language->get('error_message');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->validatePermission()) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	protected function validatePermission() {
		if (!$this->user->hasPermission('modify', 'extension/message/message')) {
			$this->error['warning'] = $this->language->get('error_permission');
			return false;
		}
		
		return true;
	}

	public function install() {
		$this->load->model('extension/message/message');
		$this->model_extension_message_message->install();
		
		$this->load->model('user/user_group');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/message/message');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/message/message');
	}
	
	public function uninstall() {
		$this->load->model('extension/message/message');
		$this->model_extension_message_message->uninstall();
		
		$this->load->model('user/user_group');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/message/message');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'extension/message/message');
	}
} 