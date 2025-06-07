<?php
/**
 * تحكم تقارير ضريبة القيمة المضافة المحسن
 * يدعم إنشاء تقارير ضريبة القيمة المضافة وفقاً للمعايير المصرية والعربية
 */
class ControllerReportsVatReport extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('reports/vat_report');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('reports/vat_report');
        $this->getReport();
    }

    public function generate() {
        $this->load->language('reports/vat_report');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('reports/vat_report');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->getReport();
        } else {
            $this->getForm();
        }
    }

    public function sales() {
        $this->load->language('reports/vat_report');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('reports/vat_report');
        $this->getSalesVatReport();
    }

    public function purchases() {
        $this->load->language('reports/vat_report');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('reports/vat_report');
        $this->getPurchasesVatReport();
    }

    public function summary() {
        $this->load->language('reports/vat_report');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('reports/vat_report');
        $this->getVatSummary();
    }

    protected function getReport() {
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('reports/vat_report', 'user_token=' . $this->session->data['user_token'], true)
        );

        // معاملات التقرير
        if (isset($this->request->post['date_start'])) {
            $date_start = $this->request->post['date_start'];
        } else {
            $date_start = date('Y-m-01');
        }

        if (isset($this->request->post['date_end'])) {
            $date_end = $this->request->post['date_end'];
        } else {
            $date_end = date('Y-m-d');
        }

        if (isset($this->request->post['report_type'])) {
            $report_type = $this->request->post['report_type'];
        } else {
            $report_type = 'comprehensive';
        }

        if (isset($this->request->post['vat_rate'])) {
            $vat_rate = $this->request->post['vat_rate'];
        } else {
            $vat_rate = '';
        }

        // إنشاء التقرير
        $vat_data = $this->model_reports_vat_report->generateVatReport($date_start, $date_end, $report_type, $vat_rate);

        $data['vat_report'] = $vat_data;
        $data['date_start'] = $date_start;
        $data['date_end'] = $date_end;
        $data['report_type'] = $report_type;
        $data['vat_rate'] = $vat_rate;
        $data['date_start_formatted'] = date($this->language->get('date_format_short'), strtotime($date_start));
        $data['date_end_formatted'] = date($this->language->get('date_format_short'), strtotime($date_end));

        // روابط التصدير
        $data['export_pdf'] = $this->url->link('reports/vat_report/exportPdf', 'user_token=' . $this->session->data['user_token'] . '&date_start=' . $date_start . '&date_end=' . $date_end . '&report_type=' . $report_type . '&vat_rate=' . $vat_rate, true);
        $data['export_excel'] = $this->url->link('reports/vat_report/exportExcel', 'user_token=' . $this->session->data['user_token'] . '&date_start=' . $date_start . '&date_end=' . $date_end . '&report_type=' . $report_type . '&vat_rate=' . $vat_rate, true);

        // روابط التقارير المختلفة
        $data['sales_report'] = $this->url->link('reports/vat_report/sales', 'user_token=' . $this->session->data['user_token'] . '&date_start=' . $date_start . '&date_end=' . $date_end, true);
        $data['purchases_report'] = $this->url->link('reports/vat_report/purchases', 'user_token=' . $this->session->data['user_token'] . '&date_start=' . $date_start . '&date_end=' . $date_end, true);
        $data['summary_report'] = $this->url->link('reports/vat_report/summary', 'user_token=' . $this->session->data['user_token'] . '&date_start=' . $date_start . '&date_end=' . $date_end, true);

        $data['generate'] = $this->url->link('reports/vat_report/generate', 'user_token=' . $this->session->data['user_token'], true);

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

        $this->response->setOutput($this->load->view('reports/vat_report', $data));
    }

    protected function getSalesVatReport() {
        $date_start = $this->request->get['date_start'] ?? date('Y-m-01');
        $date_end = $this->request->get['date_end'] ?? date('Y-m-d');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('reports/vat_report', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_sales_vat'),
            'href' => $this->url->link('reports/vat_report/sales', 'user_token=' . $this->session->data['user_token'], true)
        );

        $sales_vat = $this->model_reports_vat_report->getSalesVatReport($date_start, $date_end);

        $data['sales_vat'] = $sales_vat;
        $data['date_start'] = $date_start;
        $data['date_end'] = $date_end;
        $data['date_start_formatted'] = date($this->language->get('date_format_short'), strtotime($date_start));
        $data['date_end_formatted'] = date($this->language->get('date_format_short'), strtotime($date_end));

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('reports/vat_sales_report', $data));
    }

    protected function getPurchasesVatReport() {
        $date_start = $this->request->get['date_start'] ?? date('Y-m-01');
        $date_end = $this->request->get['date_end'] ?? date('Y-m-d');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('reports/vat_report', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_purchases_vat'),
            'href' => $this->url->link('reports/vat_report/purchases', 'user_token=' . $this->session->data['user_token'], true)
        );

        $purchases_vat = $this->model_reports_vat_report->getPurchasesVatReport($date_start, $date_end);

        $data['purchases_vat'] = $purchases_vat;
        $data['date_start'] = $date_start;
        $data['date_end'] = $date_end;
        $data['date_start_formatted'] = date($this->language->get('date_format_short'), strtotime($date_start));
        $data['date_end_formatted'] = date($this->language->get('date_format_short'), strtotime($date_end));

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('reports/vat_purchases_report', $data));
    }

    protected function getVatSummary() {
        $date_start = $this->request->get['date_start'] ?? date('Y-m-01');
        $date_end = $this->request->get['date_end'] ?? date('Y-m-d');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('reports/vat_report', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_vat_summary'),
            'href' => $this->url->link('reports/vat_report/summary', 'user_token=' . $this->session->data['user_token'], true)
        );

        $vat_summary = $this->model_reports_vat_report->getVatSummary($date_start, $date_end);

        $data['vat_summary'] = $vat_summary;
        $data['date_start'] = $date_start;
        $data['date_end'] = $date_end;
        $data['date_start_formatted'] = date($this->language->get('date_format_short'), strtotime($date_start));
        $data['date_end_formatted'] = date($this->language->get('date_format_short'), strtotime($date_end));

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('reports/vat_summary_report', $data));
    }

    protected function getForm() {
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('reports/vat_report', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('reports/vat_report/generate', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('reports/vat_report', 'user_token=' . $this->session->data['user_token'], true);

        // بيانات النموذج
        if (isset($this->request->post['date_start'])) {
            $data['date_start'] = $this->request->post['date_start'];
        } else {
            $data['date_start'] = date('Y-m-01');
        }

        if (isset($this->request->post['date_end'])) {
            $data['date_end'] = $this->request->post['date_end'];
        } else {
            $data['date_end'] = date('Y-m-d');
        }

        if (isset($this->request->post['report_type'])) {
            $data['report_type'] = $this->request->post['report_type'];
        } else {
            $data['report_type'] = 'comprehensive';
        }

        if (isset($this->request->post['vat_rate'])) {
            $data['vat_rate'] = $this->request->post['vat_rate'];
        } else {
            $data['vat_rate'] = '';
        }

        // أنواع التقارير
        $data['report_types'] = array(
            'comprehensive' => $this->language->get('text_comprehensive'),
            'sales_only' => $this->language->get('text_sales_only'),
            'purchases_only' => $this->language->get('text_purchases_only'),
            'summary' => $this->language->get('text_summary')
        );

        // معدلات ضريبة القيمة المضافة
        $data['vat_rates'] = array(
            '' => $this->language->get('text_all_rates'),
            '0' => '0%',
            '5' => '5%',
            '14' => '14%',
            '15' => '15%'
        );

        if (isset($this->error['date_start'])) {
            $data['error_date_start'] = $this->error['date_start'];
        } else {
            $data['error_date_start'] = '';
        }

        if (isset($this->error['date_end'])) {
            $data['error_date_end'] = $this->error['date_end'];
        } else {
            $data['error_date_end'] = '';
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('reports/vat_report_form', $data));
    }

    public function exportPdf() {
        $this->load->language('reports/vat_report');
        $this->load->model('reports/vat_report');

        $date_start = $this->request->get['date_start'] ?? date('Y-m-01');
        $date_end = $this->request->get['date_end'] ?? date('Y-m-d');
        $report_type = $this->request->get['report_type'] ?? 'comprehensive';
        $vat_rate = $this->request->get['vat_rate'] ?? '';

        $vat_data = $this->model_reports_vat_report->generateVatReport($date_start, $date_end, $report_type, $vat_rate);

        // إنشاء PDF
        $this->load->library('pdf');
        
        $pdf = new PDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        
        // عنوان التقرير
        $pdf->Cell(0, 10, $this->language->get('heading_title'), 0, 1, 'C');
        $pdf->Cell(0, 10, 'من ' . date('d/m/Y', strtotime($date_start)) . ' إلى ' . date('d/m/Y', strtotime($date_end)), 0, 1, 'C');
        $pdf->Ln(10);

        // محتوى التقرير
        $this->addVatReportToPdf($pdf, $vat_data);

        $pdf->Output('vat_report_' . date('Y-m-d') . '.pdf', 'D');
    }

    public function exportExcel() {
        $this->load->language('reports/vat_report');
        $this->load->model('reports/vat_report');

        $date_start = $this->request->get['date_start'] ?? date('Y-m-01');
        $date_end = $this->request->get['date_end'] ?? date('Y-m-d');
        $report_type = $this->request->get['report_type'] ?? 'comprehensive';
        $vat_rate = $this->request->get['vat_rate'] ?? '';

        $vat_data = $this->model_reports_vat_report->generateVatReport($date_start, $date_end, $report_type, $vat_rate);

        // إنشاء Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="vat_report_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');

        echo '<table border="1">';
        echo '<tr><th colspan="6">' . $this->language->get('heading_title') . '</th></tr>';
        echo '<tr><th colspan="6">من ' . date('d/m/Y', strtotime($date_start)) . ' إلى ' . date('d/m/Y', strtotime($date_end)) . '</th></tr>';

        $this->addVatReportToExcel($vat_data);

        echo '</table>';
    }

    private function addVatReportToPdf($pdf, $vat_data) {
        $pdf->SetFont('Arial', 'B', 12);
        
        // ضريبة المبيعات
        $pdf->Cell(0, 8, 'ضريبة القيمة المضافة على المبيعات', 0, 1);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(40, 6, 'المعدل', 1, 0, 'C');
        $pdf->Cell(40, 6, 'المبلغ الخاضع', 1, 0, 'C');
        $pdf->Cell(40, 6, 'مبلغ الضريبة', 1, 0, 'C');
        $pdf->Cell(40, 6, 'الإجمالي', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 9);
        foreach ($vat_data['sales_vat'] as $rate => $amounts) {
            $pdf->Cell(40, 6, $rate . '%', 1, 0, 'C');
            $pdf->Cell(40, 6, number_format($amounts['taxable_amount'], 2), 1, 0, 'R');
            $pdf->Cell(40, 6, number_format($amounts['tax_amount'], 2), 1, 0, 'R');
            $pdf->Cell(40, 6, number_format($amounts['total_amount'], 2), 1, 1, 'R');
        }
        $pdf->Ln(5);

        // ضريبة المشتريات
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'ضريبة القيمة المضافة على المشتريات', 0, 1);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(40, 6, 'المعدل', 1, 0, 'C');
        $pdf->Cell(40, 6, 'المبلغ الخاضع', 1, 0, 'C');
        $pdf->Cell(40, 6, 'مبلغ الضريبة', 1, 0, 'C');
        $pdf->Cell(40, 6, 'الإجمالي', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 9);
        foreach ($vat_data['purchases_vat'] as $rate => $amounts) {
            $pdf->Cell(40, 6, $rate . '%', 1, 0, 'C');
            $pdf->Cell(40, 6, number_format($amounts['taxable_amount'], 2), 1, 0, 'R');
            $pdf->Cell(40, 6, number_format($amounts['tax_amount'], 2), 1, 0, 'R');
            $pdf->Cell(40, 6, number_format($amounts['total_amount'], 2), 1, 1, 'R');
        }
        $pdf->Ln(10);

        // الملخص
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'ملخص ضريبة القيمة المضافة', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(100, 6, 'إجمالي ضريبة المبيعات:', 0, 0);
        $pdf->Cell(60, 6, number_format($vat_data['summary']['total_sales_vat'], 2), 0, 1, 'R');
        $pdf->Cell(100, 6, 'إجمالي ضريبة المشتريات:', 0, 0);
        $pdf->Cell(60, 6, number_format($vat_data['summary']['total_purchases_vat'], 2), 0, 1, 'R');
        $pdf->Cell(100, 6, 'صافي ضريبة القيمة المضافة:', 0, 0);
        $pdf->Cell(60, 6, number_format($vat_data['summary']['net_vat'], 2), 0, 1, 'R');
    }

    private function addVatReportToExcel($vat_data) {
        // ضريبة المبيعات
        echo '<tr><th colspan="4">ضريبة القيمة المضافة على المبيعات</th></tr>';
        echo '<tr><th>المعدل</th><th>المبلغ الخاضع</th><th>مبلغ الضريبة</th><th>الإجمالي</th></tr>';
        
        foreach ($vat_data['sales_vat'] as $rate => $amounts) {
            echo '<tr>';
            echo '<td>' . $rate . '%</td>';
            echo '<td>' . number_format($amounts['taxable_amount'], 2) . '</td>';
            echo '<td>' . number_format($amounts['tax_amount'], 2) . '</td>';
            echo '<td>' . number_format($amounts['total_amount'], 2) . '</td>';
            echo '</tr>';
        }

        // ضريبة المشتريات
        echo '<tr><th colspan="4">ضريبة القيمة المضافة على المشتريات</th></tr>';
        echo '<tr><th>المعدل</th><th>المبلغ الخاضع</th><th>مبلغ الضريبة</th><th>الإجمالي</th></tr>';
        
        foreach ($vat_data['purchases_vat'] as $rate => $amounts) {
            echo '<tr>';
            echo '<td>' . $rate . '%</td>';
            echo '<td>' . number_format($amounts['taxable_amount'], 2) . '</td>';
            echo '<td>' . number_format($amounts['tax_amount'], 2) . '</td>';
            echo '<td>' . number_format($amounts['total_amount'], 2) . '</td>';
            echo '</tr>';
        }

        // الملخص
        echo '<tr><th colspan="4">ملخص ضريبة القيمة المضافة</th></tr>';
        echo '<tr><td>إجمالي ضريبة المبيعات</td><td colspan="3">' . number_format($vat_data['summary']['total_sales_vat'], 2) . '</td></tr>';
        echo '<tr><td>إجمالي ضريبة المشتريات</td><td colspan="3">' . number_format($vat_data['summary']['total_purchases_vat'], 2) . '</td></tr>';
        echo '<tr><td><strong>صافي ضريبة القيمة المضافة</strong></td><td colspan="3"><strong>' . number_format($vat_data['summary']['net_vat'], 2) . '</strong></td></tr>';
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('access', 'reports/vat_report')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['date_start'])) {
            $this->error['date_start'] = $this->language->get('error_date_start');
        }

        if (empty($this->request->post['date_end'])) {
            $this->error['date_end'] = $this->language->get('error_date_end');
        }

        if (!empty($this->request->post['date_start']) && !empty($this->request->post['date_end'])) {
            if (strtotime($this->request->post['date_start']) >= strtotime($this->request->post['date_end'])) {
                $this->error['date_end'] = $this->language->get('error_date_range');
            }
        }

        return !$this->error;
    }
}
