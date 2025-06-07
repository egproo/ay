<?php
class ControllerPurchasePurchaseAnalytics extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('purchase/purchase_analytics');
		$this->load->language('common/column_left');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('purchase/purchase_analytics');

		// تحقق من الصلاحيات
		if (!$this->user->hasPermission('access', 'purchase/purchase_analytics')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
		}

		// متغيرات بدء الصفحة
		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_loading'] = $this->language->get('text_loading');
		$data['text_spending_by_category'] = $this->language->get('text_spending_by_category');
		$data['text_spending_trend'] = $this->language->get('text_spending_trend');
		$data['text_top_suppliers'] = $this->language->get('text_top_suppliers');
		$data['text_supplier_performance'] = $this->language->get('text_supplier_performance');
		$data['text_po_status'] = $this->language->get('text_po_status');
		$data['text_lead_time'] = $this->language->get('text_lead_time');
		$data['text_invoice_matching'] = $this->language->get('text_invoice_matching');
		$data['text_price_variance'] = $this->language->get('text_price_variance');

		$data['text_export'] = $this->language->get('text_export');
		$data['text_date_range'] = $this->language->get('text_date_range');
		$data['text_filter'] = $this->language->get('text_filter');
		$data['text_apply'] = $this->language->get('text_apply');

		$data['button_view'] = $this->language->get('button_view');
		$data['button_export'] = $this->language->get('button_export');

		// تاريخ البدء والانتهاء (افتراضي: آخر 30 يوم)
		$data['date_start'] = date('Y-m-d', strtotime('-30 days'));
		$data['date_end'] = date('Y-m-d');

		if (isset($this->request->get['date_start'])) {
			$data['date_start'] = $this->request->get['date_start'];
		}

		if (isset($this->request->get['date_end'])) {
			$data['date_end'] = $this->request->get['date_end'];
		}

		// إعداد البيانات الرئيسية
		$data['spending_by_category'] = $this->model_purchase_purchase_analytics->getSpendingByCategory($data['date_start'], $data['date_end']);
		$data['spending_trend'] = $this->model_purchase_purchase_analytics->getSpendingTrend($data['date_start'], $data['date_end']);
		$data['top_suppliers'] = $this->model_purchase_purchase_analytics->getTopSuppliers($data['date_start'], $data['date_end']);
		$data['supplier_performance'] = $this->model_purchase_purchase_analytics->getSupplierPerformance($data['date_start'], $data['date_end']);
		$data['po_status_breakdown'] = $this->model_purchase_purchase_analytics->getPOStatusBreakdown($data['date_start'], $data['date_end']);
		$data['lead_time_stats'] = $this->model_purchase_purchase_analytics->getAverageLeadTime($data['date_start'], $data['date_end']);
		$data['invoice_matching_stats'] = $this->model_purchase_purchase_analytics->getInvoiceMatchingStats($data['date_start'], $data['date_end']);
		$data['price_variance_alerts'] = $this->model_purchase_purchase_analytics->getPriceVarianceAlerts($data['date_start'], $data['date_end']);

		// إعداد روابط التصدير وتحديث البيانات
		$data['action'] = $this->url->link('purchase/purchase_analytics', 'user_token=' . $this->session->data['user_token'], true);
		$data['export'] = $this->url->link('purchase/purchase_analytics/export', 'user_token=' . $this->session->data['user_token'], true);

		// إعداد المتغيرات الضرورية للواجهة
		$data['user_token'] = $this->session->data['user_token'];

		// إعداد الفتات
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('purchase/purchase_analytics', 'user_token=' . $this->session->data['user_token'], true)
		);

		// تحميل المكونات المشتركة
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		// عرض الصفحة
		$this->response->setOutput($this->load->view('purchase/purchase_analytics', $data));
	}

	/**
	 * الحصول على بيانات الإنفاق بناءً على المعايير المحددة
	 */
	public function ajaxGetSpendingData() {
		$this->load->language('purchase/purchase_analytics');
		$json = array();

		// التحقق من الصلاحيات
		if (!$this->user->hasPermission('access', 'purchase/purchase_analytics')) {
			$json['error'] = $this->language->get('error_permission');
			return $this->sendJSON($json);
		}

		if (isset($this->request->get['date_start']) && isset($this->request->get['date_end'])) {
			$this->load->model('purchase/purchase_analytics');

			$date_start = $this->request->get['date_start'];
			$date_end = $this->request->get['date_end'];
			$type = isset($this->request->get['type']) ? $this->request->get['type'] : 'category';

			try {
				if ($type == 'category') {
					$json['data'] = $this->model_purchase_purchase_analytics->getSpendingByCategory($date_start, $date_end);
				} elseif ($type == 'trend') {
					$json['data'] = $this->model_purchase_purchase_analytics->getSpendingTrend($date_start, $date_end);
				} elseif ($type == 'supplier') {
					$json['data'] = $this->model_purchase_purchase_analytics->getTopSuppliers($date_start, $date_end);
				} elseif ($type == 'status') {
					$json['data'] = $this->model_purchase_purchase_analytics->getPOStatusBreakdown($date_start, $date_end);
				} elseif ($type == 'performance') {
					$json['data'] = $this->model_purchase_purchase_analytics->getSupplierPerformance($date_start, $date_end);
				} elseif ($type == 'lead_time') {
					$json['data'] = $this->model_purchase_purchase_analytics->getAverageLeadTime($date_start, $date_end);
				} elseif ($type == 'matching') {
					$json['data'] = $this->model_purchase_purchase_analytics->getInvoiceMatchingStats($date_start, $date_end);
				} elseif ($type == 'variance') {
					$json['data'] = $this->model_purchase_purchase_analytics->getPriceVarianceAlerts($date_start, $date_end);
				} else {
					$json['error'] = $this->language->get('error_invalid_type');
					return $this->sendJSON($json);
				}

				$json['success'] = true;
			} catch (Exception $e) {
				$json['error'] = $e->getMessage();
			}
		} else {
			$json['error'] = $this->language->get('error_date_range_required');
		}

		$this->sendJSON($json);
	}

	/**
	 * الحصول على ملخص سريع للإحصائيات
	 */
	public function ajaxGetQuickStats() {
		$this->load->language('purchase/purchase_analytics');
		$json = array();

		// التحقق من الصلاحيات
		if (!$this->user->hasPermission('access', 'purchase/purchase_analytics')) {
			$json['error'] = $this->language->get('error_permission');
			return $this->sendJSON($json);
		}

		$this->load->model('purchase/purchase_analytics');

		$date_start = isset($this->request->get['date_start']) ? $this->request->get['date_start'] : date('Y-m-d', strtotime('-30 days'));
		$date_end = isset($this->request->get['date_end']) ? $this->request->get['date_end'] : date('Y-m-d');

		try {
			$stats = array();
			$stats['total_spending'] = $this->model_purchase_purchase_analytics->getTotalSpending($date_start, $date_end);
			$stats['total_orders'] = $this->model_purchase_purchase_analytics->getTotalOrders($date_start, $date_end);
			$stats['avg_order_value'] = $this->model_purchase_purchase_analytics->getAverageOrderValue($date_start, $date_end);
			$stats['top_supplier'] = $this->model_purchase_purchase_analytics->getTopSupplier($date_start, $date_end);
			$stats['pending_approvals'] = $this->model_purchase_purchase_analytics->getPendingApprovals();
			$stats['overdue_orders'] = $this->model_purchase_purchase_analytics->getOverdueOrders();

			$json['data'] = $stats;
			$json['success'] = true;
		} catch (Exception $e) {
			$json['error'] = $e->getMessage();
		}

		$this->sendJSON($json);
	}

	/**
	 * إرسال استجابة JSON
	 */
	private function sendJSON($json) {
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * تصدير التقارير إلى ملف Excel
	 */
	public function export() {
		if (!$this->user->hasPermission('modify', 'purchase/purchase_analytics')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->language('purchase/purchase_analytics');
		$this->load->model('purchase/purchase_analytics');

		// تحديد المعايير
		$date_start = isset($this->request->get['date_start']) ? $this->request->get['date_start'] : date('Y-m-d', strtotime('-30 days'));
		$date_end = isset($this->request->get['date_end']) ? $this->request->get['date_end'] : date('Y-m-d');

		// الحصول على البيانات
		$spending_by_category = $this->model_purchase_purchase_analytics->getSpendingByCategory($date_start, $date_end);
		$spending_trend = $this->model_purchase_purchase_analytics->getSpendingTrend($date_start, $date_end);
		$top_suppliers = $this->model_purchase_purchase_analytics->getTopSuppliers($date_start, $date_end);
		$supplier_performance = $this->model_purchase_purchase_analytics->getSupplierPerformance($date_start, $date_end);
		$po_status_breakdown = $this->model_purchase_purchase_analytics->getPOStatusBreakdown($date_start, $date_end);
		$lead_time_stats = $this->model_purchase_purchase_analytics->getAverageLeadTime($date_start, $date_end);
		$invoice_matching_stats = $this->model_purchase_purchase_analytics->getInvoiceMatchingStats($date_start, $date_end);
		$price_variance_alerts = $this->model_purchase_purchase_analytics->getPriceVarianceAlerts($date_start, $date_end);

		// إنشاء ملف إكسل
		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

		// إعداد ورقة الإنفاق حسب الفئة
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setTitle($this->language->get('text_spending_by_category'));

		// إضافة العناوين
		$sheet->setCellValue('A1', $this->language->get('text_category'));
		$sheet->setCellValue('B1', $this->language->get('text_amount'));
		$sheet->setCellValue('C1', $this->language->get('text_percentage'));

		// إضافة البيانات
		$row = 2;
		foreach ($spending_by_category as $data) {
			$sheet->setCellValue('A' . $row, $data['name']);
			$sheet->setCellValue('B' . $row, $data['amount']);
			$sheet->setCellValue('C' . $row, $data['percentage'] . '%');
			$row++;
		}

		// إعداد ورقة اتجاه الإنفاق
		$spreadsheet->createSheet();
		$spreadsheet->setActiveSheetIndex(1);
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setTitle($this->language->get('text_spending_trend'));

		// إضافة العناوين
		$sheet->setCellValue('A1', $this->language->get('text_period'));
		$sheet->setCellValue('B1', $this->language->get('text_amount'));

		// إضافة البيانات
		$row = 2;
		foreach ($spending_trend as $data) {
			$sheet->setCellValue('A' . $row, $data['date_period']);
			$sheet->setCellValue('B' . $row, $data['amount']);
			$row++;
		}

		// إعداد ورقة أفضل الموردين
		$spreadsheet->createSheet();
		$spreadsheet->setActiveSheetIndex(2);
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setTitle($this->language->get('text_top_suppliers'));

		// إضافة العناوين
		$sheet->setCellValue('A1', $this->language->get('text_supplier'));
		$sheet->setCellValue('B1', $this->language->get('text_orders'));
		$sheet->setCellValue('C1', $this->language->get('text_amount'));

		// إضافة البيانات
		$row = 2;
		foreach ($top_suppliers as $data) {
			$sheet->setCellValue('A' . $row, $data['name']);
			$sheet->setCellValue('B' . $row, $data['orders']);
			$sheet->setCellValue('C' . $row, $data['amount']);
			$row++;
		}

		// إعداد ورقة أداء الموردين
		$spreadsheet->createSheet();
		$spreadsheet->setActiveSheetIndex(3);
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setTitle($this->language->get('text_supplier_performance'));

		// إضافة العناوين
		$sheet->setCellValue('A1', $this->language->get('text_supplier'));
		$sheet->setCellValue('B1', $this->language->get('text_lead_time_days'));
		$sheet->setCellValue('C1', $this->language->get('text_quality_rate'));
		$sheet->setCellValue('D1', $this->language->get('text_on_time_rate'));

		// إضافة البيانات
		$row = 2;
		foreach ($supplier_performance as $data) {
			$sheet->setCellValue('A' . $row, $data['name']);
			$sheet->setCellValue('B' . $row, $data['lead_time']);
			$sheet->setCellValue('C' . $row, $data['quality_rate'] . '%');
			$sheet->setCellValue('D' . $row, $data['on_time_rate'] . '%');
			$row++;
		}

		// تعيين الورقة النشطة إلى الأولى
		$spreadsheet->setActiveSheetIndex(0);

		// إنشاء كائن Writer لتصدير ملف Excel
		$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

		// Header لتنزيل الملف
		$file_name = 'purchase_analytics_' . date('Y-m-d') . '.xlsx';

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . $file_name . '"');
		header('Cache-Control: max-age=0');

		$writer->save('php://output');
		exit;
	}
}