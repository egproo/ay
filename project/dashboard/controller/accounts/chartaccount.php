<?php
/**
 * تحكم دليل الحسابات المتقدم والمتكامل
 * يدعم العرض الشجري، الطباعة، التصدير، والتكامل الكامل مع النظام
 */
class ControllerAccountsChartaccount extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('accounts/chartaccount');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('accounts/chartaccount');

		// إضافة CSS و JavaScript المتقدم
		$this->document->addStyle('view/stylesheet/accounts/chartaccount.css');
		$this->document->addScript('view/javascript/accounts/chartaccount.js');
		$this->document->addScript('view/javascript/jquery/jstree/jstree.min.js');
		$this->document->addStyle('view/javascript/jquery/jstree/themes/default/style.min.css');

		$this->getList();
	}
	public function add() {
		$this->load->language('accounts/chartaccount');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('accounts/chartaccount');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_accounts_chartaccount->addAccount($this->request->post);

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

			$this->response->redirect($this->url->link('accounts/chartaccount', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('accounts/chartaccount');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('accounts/chartaccount');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_accounts_chartaccount->editAccount($this->request->get['account_id'], $this->request->post);

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

			$this->response->redirect($this->url->link('accounts/chartaccount', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('accounts/chartaccount');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('accounts/chartaccount');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $account_id) {
				$this->model_accounts_chartaccount->deleteAccount($account_id);
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

			$this->response->redirect($this->url->link('accounts/chartaccount', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'account_code';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
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
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('accounts/chartaccount', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('accounts/chartaccount/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('accounts/chartaccount/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['accounts'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * 999999999,
			'limit' => 999999999
		);

		$account_total = $this->model_accounts_chartaccount->getTotalAccounts();

		$results = $this->model_accounts_chartaccount->getAccounts($filter_data);

		foreach ($results as $result) {
			$data['accounts'][] = array(
				'account_id' => $result['account_id'],
				'account_code'        => $result['account_code'],
				'account_type'        => $result['account_type'],
				'name'        => $result['name'],
				'edit'        => $this->url->link('accounts/chartaccount/edit', 'user_token=' . $this->session->data['user_token'] . '&account_id=' . $result['account_id'] . $url, true),
				'delete'      => $this->url->link('accounts/chartaccount/delete', 'user_token=' . $this->session->data['user_token'] . '&account_id=' . $result['account_id'] . $url, true)
			);
		}

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
		$data['sort_account_code'] = $this->url->link('accounts/chartaccount', 'user_token=' . $this->session->data['user_token'] . '&sort=account_code' . $url, true);
		$data['sort_name'] = $this->url->link('accounts/chartaccount', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
        $data['export_action'] = $this->url->link('extension/export_import/download', 'user_token=' . $this->session->data['user_token'], true);
        $data['import_action'] = $this->url->link('extension/export_import/upload', 'user_token=' . $this->session->data['user_token'], true);



		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $account_total;
		$pagination->page = $page;
		$pagination->limit = 999999999;
		$pagination->url = $this->url->link('accounts/chartaccount', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($account_total) ? (($page - 1) * 999999999) + 1 : 0, ((($page - 1) * 999999999) > ($account_total - 999999999)) ? $account_total : ((($page - 1) * 999999999) + 999999999), $account_total, ceil($account_total / 999999999));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('accounts/account_list', $data));
	}


	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['account_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}


		if (isset($this->error['parent'])) {
			$data['error_parent'] = $this->error['parent'];
		} else {
			$data['error_parent'] = '';
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
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('accounts/chartaccount', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['account_id'])) {
			$data['action'] = $this->url->link('accounts/chartaccount/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('accounts/chartaccount/edit', 'user_token=' . $this->session->data['user_token'] . '&account_id=' . $this->request->get['account_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('accounts/chartaccount', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['account_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$account_info = $this->model_accounts_chartaccount->getAccount($this->request->get['account_id']);
		}

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['account_description'])) {
			$data['account_description'] = $this->request->post['account_description'];
		} elseif (isset($this->request->get['account_id'])) {
			$data['account_description'] = $this->model_accounts_chartaccount->getAccountDescriptions($this->request->get['account_id']);
		} else {
			$data['account_description'] = array();
		}

		if (isset($this->request->post['parent_id'])) {
			$data['parent_id'] = $this->request->post['parent_id'];
		} elseif (!empty($account_info)) {
			$data['parent_id'] = $account_info['parent_id'];
		} else {
			$data['parent_id'] = 0;
		}
		if (isset($this->request->post['account_code'])) {
			$data['account_code'] = $this->request->post['account_code'];
		} elseif (!empty($account_info)) {
			$data['account_code'] = $account_info['account_code'];
		} else {
			$data['account_code'] = 0;
		}
		if (isset($this->request->post['account_type'])) {
			$data['account_type'] = $this->request->post['account_type'];
		} elseif (!empty($account_info)) {
			$data['account_type'] = $account_info['account_type'];
		} else {
			$data['account_type'] = 'debit';
		}
		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($account_info)) {
			$data['status'] = $account_info['status'];
		} else {
			$data['status'] = true;
		}


		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('accounts/account_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'accounts/chartaccount')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['account_description'] as $language_id => $value) {
			if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 255)) {
				$this->error['name'][$language_id] = $this->language->get('error_name');
			}

		}


		if ($this->request->post['parent_id'] == $this->request->get['account_id']) {
			$this->error['parent'] = $this->language->get('error_parent');
		}




		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'accounts/chartaccount')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	protected function validateRepair() {
		if (!$this->user->hasPermission('modify', 'accounts/chartaccount')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function autocomplete() {
		$json = array();

		if (isset($this->request->get['filter_name'])) {
			$this->load->model('accounts/chartaccount');

			$filter_data = array(
				'filter_name' => $this->request->get['filter_name'],
				'sort'        => 'name',
				'order'       => 'ASC',
				'start'       => 0,
				'limit'       => 5
			);

			$results = $this->model_accounts_chartaccount->getAccounts($filter_data);

			foreach ($results as $result) {
				$json[] = array(
					'account_id' => $result['account_id'],
					'name'        => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
				);
			}
		}


		$sort_order = array();

		foreach ($json as $key => $value) {
			$sort_order[$key] = $value['name'];
		}

		array_multisort($sort_order, SORT_ASC, $json);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * عرض شجري للحسابات
	 */
	public function tree() {
		$this->load->language('accounts/chartaccount');
		$this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_tree_view'));
		$this->load->model('accounts/chartaccount');

		// إضافة مكتبات العرض الشجري
		$this->document->addScript('view/javascript/jquery/jstree/jstree.min.js');
		$this->document->addStyle('view/javascript/jquery/jstree/themes/default/style.min.css');
		$this->document->addScript('view/javascript/accounts/tree.js');

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('accounts/chartaccount', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_tree_view'),
			'href' => $this->url->link('accounts/chartaccount/tree', 'user_token=' . $this->session->data['user_token'], true)
		);

		// الحصول على البيانات الشجرية
		$data['accounts_tree'] = $this->model_accounts_chartaccount->getAccountsTree();
		$data['tree_data_url'] = $this->url->link('accounts/chartaccount/getTreeData', 'user_token=' . $this->session->data['user_token'], true);

		// روابط الإجراءات
		$data['add'] = $this->url->link('accounts/chartaccount/add', 'user_token=' . $this->session->data['user_token'], true);
		$data['list_view'] = $this->url->link('accounts/chartaccount', 'user_token=' . $this->session->data['user_token'], true);
		$data['print'] = $this->url->link('accounts/chartaccount/print', 'user_token=' . $this->session->data['user_token'], true);
		$data['export'] = $this->url->link('accounts/chartaccount/export', 'user_token=' . $this->session->data['user_token'], true);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('accounts/chartaccount_tree', $data));
	}

	/**
	 * الحصول على بيانات الشجرة عبر AJAX
	 */
	public function getTreeData() {
		$this->load->model('accounts/chartaccount');

		$parent_id = isset($this->request->get['id']) && $this->request->get['id'] != '#' ?
					 (int)$this->request->get['id'] : null;

		$accounts = $this->model_accounts_chartaccount->getAccountsTree($parent_id);
		$tree_data = array();

		foreach ($accounts as $account) {
			$has_children = !empty($account['children']);

			$tree_data[] = array(
				'id' => $account['account_id'],
				'text' => $account['account_code'] . ' - ' . $account['name'],
				'children' => $has_children,
				'data' => array(
					'account_code' => $account['account_code'],
					'account_type' => $account['account_type'],
					'current_balance' => $account['current_balance'] ?? 0,
					'is_parent' => $account['is_parent'] ?? 0,
					'allow_posting' => $account['allow_posting'] ?? 1
				),
				'a_attr' => array(
					'href' => $this->url->link('accounts/chartaccount/edit',
						'user_token=' . $this->session->data['user_token'] . '&account_id=' . $account['account_id'], true)
				),
				'li_attr' => array(
					'data-account-id' => $account['account_id'],
					'data-account-code' => $account['account_code'],
					'data-account-type' => $account['account_type']
				)
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($tree_data));
	}

	/**
	 * طباعة دليل الحسابات
	 */
	public function print() {
		$this->load->language('accounts/chartaccount');
		$this->load->model('accounts/chartaccount');

		// معاملات الطباعة
		$format = $this->request->get['format'] ?? 'tree';
		$include_balances = isset($this->request->get['include_balances']) ?
						   (bool)$this->request->get['include_balances'] : true;
		$account_type = $this->request->get['account_type'] ?? '';

		$data['company_name'] = $this->config->get('config_name');
		$data['print_date'] = date($this->language->get('date_format_long'));
		$data['format'] = $format;
		$data['include_balances'] = $include_balances;

		// الحصول على البيانات حسب التنسيق
		if ($format == 'tree') {
			$data['accounts'] = $this->model_accounts_chartaccount->getAccountsTree();
		} else {
			$filter_data = array();
			if ($account_type) {
				$filter_data['filter_account_type'] = $account_type;
			}
			$data['accounts'] = $this->model_accounts_chartaccount->getAccounts($filter_data);
		}

		// إعداد الطباعة
		$this->response->addHeader('Content-Type: text/html; charset=utf-8');
		$this->response->setOutput($this->load->view('accounts/chartaccount_print', $data));
	}

	/**
	 * تصدير دليل الحسابات
	 */
	public function export() {
		$this->load->language('accounts/chartaccount');
		$this->load->model('accounts/chartaccount');

		$format = $this->request->get['format'] ?? 'excel';
		$include_balances = isset($this->request->get['include_balances']) ?
						   (bool)$this->request->get['include_balances'] : true;

		$accounts = $this->model_accounts_chartaccount->getAccounts();

		switch ($format) {
			case 'excel':
				$this->exportToExcel($accounts, $include_balances);
				break;
			case 'pdf':
				$this->exportToPdf($accounts, $include_balances);
				break;
			case 'csv':
				$this->exportToCsv($accounts, $include_balances);
				break;
			default:
				$this->exportToExcel($accounts, $include_balances);
		}
	}

	/**
	 * تصدير إلى Excel
	 */
	private function exportToExcel($accounts, $include_balances = true) {
		$filename = 'chart_of_accounts_' . date('Y-m-d') . '.xls';

		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $filename . '"');
		header('Cache-Control: max-age=0');

		echo '<table border="1">';
		echo '<tr>';
		echo '<th>رقم الحساب</th>';
		echo '<th>اسم الحساب</th>';
		echo '<th>نوع الحساب</th>';
		echo '<th>طبيعة الحساب</th>';
		echo '<th>الحساب الأب</th>';
		if ($include_balances) {
			echo '<th>الرصيد الحالي</th>';
		}
		echo '<th>حالة الحساب</th>';
		echo '</tr>';

		foreach ($accounts as $account) {
			echo '<tr>';
			echo '<td>' . $account['account_code'] . '</td>';
			echo '<td>' . $account['name'] . '</td>';
			echo '<td>' . $this->getAccountTypeText($account['account_type']) . '</td>';
			echo '<td>' . $this->getAccountNatureText($account['account_nature']) . '</td>';
			echo '<td>' . ($account['parent_name'] ?? '') . '</td>';
			if ($include_balances) {
				echo '<td>' . number_format($account['current_balance'] ?? 0, 2) . '</td>';
			}
			echo '<td>' . ($account['is_active'] ? 'نشط' : 'غير نشط') . '</td>';
			echo '</tr>';
		}

		echo '</table>';
	}

	/**
	 * تصدير إلى PDF
	 */
	private function exportToPdf($accounts, $include_balances = true) {
		require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');

		$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
		$pdf->SetCreator('ERP System');
		$pdf->SetAuthor($this->config->get('config_name'));
		$pdf->SetTitle('دليل الحسابات');

		// إعداد الخط العربي
		$pdf->SetFont('aealarabiya', '', 12);
		$pdf->AddPage();

		// عنوان التقرير
		$pdf->Cell(0, 10, 'دليل الحسابات', 0, 1, 'C');
		$pdf->Cell(0, 10, $this->config->get('config_name'), 0, 1, 'C');
		$pdf->Cell(0, 10, 'تاريخ الطباعة: ' . date('Y-m-d'), 0, 1, 'C');
		$pdf->Ln(10);

		// رأس الجدول
		$pdf->SetFont('aealarabiya', 'B', 10);
		$pdf->Cell(30, 8, 'رقم الحساب', 1, 0, 'C');
		$pdf->Cell(60, 8, 'اسم الحساب', 1, 0, 'C');
		$pdf->Cell(30, 8, 'نوع الحساب', 1, 0, 'C');
		if ($include_balances) {
			$pdf->Cell(30, 8, 'الرصيد', 1, 0, 'C');
		}
		$pdf->Cell(20, 8, 'الحالة', 1, 1, 'C');

		// بيانات الجدول
		$pdf->SetFont('aealarabiya', '', 9);
		foreach ($accounts as $account) {
			$pdf->Cell(30, 6, $account['account_code'], 1, 0, 'C');
			$pdf->Cell(60, 6, $account['name'], 1, 0, 'R');
			$pdf->Cell(30, 6, $this->getAccountTypeText($account['account_type']), 1, 0, 'C');
			if ($include_balances) {
				$pdf->Cell(30, 6, number_format($account['current_balance'] ?? 0, 2), 1, 0, 'R');
			}
			$pdf->Cell(20, 6, ($account['is_active'] ? 'نشط' : 'غير نشط'), 1, 1, 'C');
		}

		$pdf->Output('chart_of_accounts_' . date('Y-m-d') . '.pdf', 'D');
	}

	/**
	 * تصدير إلى CSV
	 */
	private function exportToCsv($accounts, $include_balances = true) {
		$filename = 'chart_of_accounts_' . date('Y-m-d') . '.csv';

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment;filename="' . $filename . '"');
		header('Cache-Control: max-age=0');

		$output = fopen('php://output', 'w');

		// إضافة BOM للدعم العربي
		fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

		// رأس الجدول
		$headers = array('رقم الحساب', 'اسم الحساب', 'نوع الحساب', 'طبيعة الحساب', 'الحساب الأب');
		if ($include_balances) {
			$headers[] = 'الرصيد الحالي';
		}
		$headers[] = 'حالة الحساب';

		fputcsv($output, $headers);

		// البيانات
		foreach ($accounts as $account) {
			$row = array(
				$account['account_code'],
				$account['name'],
				$this->getAccountTypeText($account['account_type']),
				$this->getAccountNatureText($account['account_nature']),
				$account['parent_name'] ?? ''
			);

			if ($include_balances) {
				$row[] = number_format($account['current_balance'] ?? 0, 2);
			}

			$row[] = ($account['is_active'] ? 'نشط' : 'غير نشط');

			fputcsv($output, $row);
		}

		fclose($output);
	}

	/**
	 * استيراد دليل الحسابات
	 */
	public function import() {
		$this->load->language('accounts/chartaccount');
		$this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_import'));

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateImport()) {
			$this->processImport();
		}

		$this->getImportForm();
	}

	/**
	 * معالجة الاستيراد
	 */
	private function processImport() {
		$this->load->model('accounts/chartaccount');

		if (isset($this->request->files['import_file']) && $this->request->files['import_file']['error'] == 0) {
			$file = $this->request->files['import_file'];
			$extension = pathinfo($file['name'], PATHINFO_EXTENSION);

			switch (strtolower($extension)) {
				case 'csv':
					$this->importFromCsv($file['tmp_name']);
					break;
				case 'xls':
				case 'xlsx':
					$this->importFromExcel($file['tmp_name']);
					break;
				default:
					$this->error['warning'] = 'نوع الملف غير مدعوم';
			}
		}
	}

	/**
	 * استيراد من CSV
	 */
	private function importFromCsv($file_path) {
		$handle = fopen($file_path, 'r');
		$headers = fgetcsv($handle); // تجاهل الرأس
		$imported = 0;
		$errors = 0;

		while (($data = fgetcsv($handle)) !== FALSE) {
			try {
				$account_data = array(
					'account_code' => $data[0],
					'account_type' => $this->getAccountTypeFromText($data[2]),
					'parent_id' => $this->getParentIdFromName($data[4]),
					'is_active' => ($data[5] == 'نشط') ? 1 : 0,
					'account_description' => array(
						$this->config->get('config_language_id') => array(
							'name' => $data[1],
							'description' => ''
						)
					)
				);

				$this->model_accounts_chartaccount->addAccount($account_data);
				$imported++;
			} catch (Exception $e) {
				$errors++;
			}
		}

		fclose($handle);

		$this->session->data['success'] = sprintf('تم استيراد %d حساب بنجاح. فشل في استيراد %d حساب.', $imported, $errors);
	}

	/**
	 * دوال مساعدة
	 */
	private function getAccountTypeText($type) {
		$types = array(
			'asset' => 'أصول',
			'liability' => 'خصوم',
			'equity' => 'حقوق ملكية',
			'revenue' => 'إيرادات',
			'expense' => 'مصروفات'
		);
		return $types[$type] ?? $type;
	}

	private function getAccountNatureText($nature) {
		$natures = array(
			'debit' => 'مدين',
			'credit' => 'دائن'
		);
		return $natures[$nature] ?? $nature;
	}

	private function getAccountTypeFromText($text) {
		$types = array(
			'أصول' => 'asset',
			'خصوم' => 'liability',
			'حقوق ملكية' => 'equity',
			'إيرادات' => 'revenue',
			'مصروفات' => 'expense'
		);
		return $types[$text] ?? 'asset';
	}

	private function getParentIdFromName($name) {
		if (empty($name)) return 0;

		$this->load->model('accounts/chartaccount');
		$accounts = $this->model_accounts_chartaccount->getAccounts(array('filter_name' => $name));

		return !empty($accounts) ? $accounts[0]['account_id'] : 0;
	}

	private function validateImport() {
		if (!$this->user->hasPermission('modify', 'accounts/chartaccount')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!isset($this->request->files['import_file']) || $this->request->files['import_file']['error'] != 0) {
			$this->error['file'] = 'يرجى اختيار ملف صحيح للاستيراد';
		}

		return !$this->error;
	}

	private function getImportForm() {
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('accounts/chartaccount', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_import'),
			'href' => $this->url->link('accounts/chartaccount/import', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('accounts/chartaccount/import', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('accounts/chartaccount', 'user_token=' . $this->session->data['user_token'], true);

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

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('accounts/chartaccount_import', $data));
	}
}
