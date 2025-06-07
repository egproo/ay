<?php
/**
 * تحكم قائمة التدفقات النقدية المحسن
 * يدعم إنشاء قائمة التدفقات النقدية بالطريقة المباشرة وغير المباشرة
 */
class ControllerReportsCashFlow extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('reports/cash_flow');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('reports/cash_flow');
        $this->getReport();
    }

    public function generate() {
        $this->load->language('reports/cash_flow');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('reports/cash_flow');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->getReport();
        } else {
            $this->getForm();
        }
    }

    protected function getReport() {
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('reports/cash_flow', 'user_token=' . $this->session->data['user_token'], true)
        );

        // معاملات التقرير
        if (isset($this->request->post['date_start'])) {
            $date_start = $this->request->post['date_start'];
        } else {
            $date_start = date('Y-01-01');
        }

        if (isset($this->request->post['date_end'])) {
            $date_end = $this->request->post['date_end'];
        } else {
            $date_end = date('Y-m-d');
        }

        if (isset($this->request->post['method'])) {
            $method = $this->request->post['method'];
        } else {
            $method = 'direct';
        }

        // إنشاء التقرير
        $cash_flow_data = $this->model_reports_cash_flow->generateCashFlowStatement($date_start, $date_end, $method);

        $data['cash_flow'] = $cash_flow_data;
        $data['date_start'] = $date_start;
        $data['date_end'] = $date_end;
        $data['method'] = $method;
        $data['date_start_formatted'] = date($this->language->get('date_format_short'), strtotime($date_start));
        $data['date_end_formatted'] = date($this->language->get('date_format_short'), strtotime($date_end));

        // روابط التصدير
        $data['export_pdf'] = $this->url->link('reports/cash_flow/exportPdf', 'user_token=' . $this->session->data['user_token'] . '&date_start=' . $date_start . '&date_end=' . $date_end . '&method=' . $method, true);
        $data['export_excel'] = $this->url->link('reports/cash_flow/exportExcel', 'user_token=' . $this->session->data['user_token'] . '&date_start=' . $date_start . '&date_end=' . $date_end . '&method=' . $method, true);

        $data['generate'] = $this->url->link('reports/cash_flow/generate', 'user_token=' . $this->session->data['user_token'], true);

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

        $this->response->setOutput($this->load->view('reports/cash_flow', $data));
    }

    protected function getForm() {
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('reports/cash_flow', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('reports/cash_flow/generate', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('reports/cash_flow', 'user_token=' . $this->session->data['user_token'], true);

        // بيانات النموذج
        if (isset($this->request->post['date_start'])) {
            $data['date_start'] = $this->request->post['date_start'];
        } else {
            $data['date_start'] = date('Y-01-01');
        }

        if (isset($this->request->post['date_end'])) {
            $data['date_end'] = $this->request->post['date_end'];
        } else {
            $data['date_end'] = date('Y-m-d');
        }

        if (isset($this->request->post['method'])) {
            $data['method'] = $this->request->post['method'];
        } else {
            $data['method'] = 'direct';
        }

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

        $this->response->setOutput($this->load->view('reports/cash_flow_form', $data));
    }

    public function exportPdf() {
        $this->load->language('reports/cash_flow');
        $this->load->model('reports/cash_flow');

        $date_start = $this->request->get['date_start'] ?? date('Y-01-01');
        $date_end = $this->request->get['date_end'] ?? date('Y-m-d');
        $method = $this->request->get['method'] ?? 'direct';

        $cash_flow_data = $this->model_reports_cash_flow->generateCashFlowStatement($date_start, $date_end, $method);

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
        $this->addCashFlowToPdf($pdf, $cash_flow_data);

        $pdf->Output('cash_flow_' . date('Y-m-d') . '.pdf', 'D');
    }

    public function exportExcel() {
        $this->load->language('reports/cash_flow');
        $this->load->model('reports/cash_flow');

        $date_start = $this->request->get['date_start'] ?? date('Y-01-01');
        $date_end = $this->request->get['date_end'] ?? date('Y-m-d');
        $method = $this->request->get['method'] ?? 'direct';

        $cash_flow_data = $this->model_reports_cash_flow->generateCashFlowStatement($date_start, $date_end, $method);

        // إنشاء Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="cash_flow_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');

        echo '<table border="1">';
        echo '<tr><th colspan="2">' . $this->language->get('heading_title') . '</th></tr>';
        echo '<tr><th colspan="2">من ' . date('d/m/Y', strtotime($date_start)) . ' إلى ' . date('d/m/Y', strtotime($date_end)) . '</th></tr>';
        echo '<tr><th>البيان</th><th>المبلغ</th></tr>';

        $this->addCashFlowToExcel($cash_flow_data);

        echo '</table>';
    }

    private function addCashFlowToPdf($pdf, $cash_flow_data) {
        $pdf->SetFont('Arial', 'B', 12);
        
        // الأنشطة التشغيلية
        $pdf->Cell(0, 8, 'التدفقات النقدية من الأنشطة التشغيلية', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        
        foreach ($cash_flow_data['operating'] as $item) {
            $pdf->Cell(120, 6, $item['description'], 0, 0);
            $pdf->Cell(70, 6, number_format($item['amount'], 2), 0, 1, 'R');
        }
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(120, 6, 'صافي التدفق النقدي من الأنشطة التشغيلية', 0, 0);
        $pdf->Cell(70, 6, number_format($cash_flow_data['operating_total'], 2), 0, 1, 'R');
        $pdf->Ln(5);

        // الأنشطة الاستثمارية
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'التدفقات النقدية من الأنشطة الاستثمارية', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        
        foreach ($cash_flow_data['investing'] as $item) {
            $pdf->Cell(120, 6, $item['description'], 0, 0);
            $pdf->Cell(70, 6, number_format($item['amount'], 2), 0, 1, 'R');
        }
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(120, 6, 'صافي التدفق النقدي من الأنشطة الاستثمارية', 0, 0);
        $pdf->Cell(70, 6, number_format($cash_flow_data['investing_total'], 2), 0, 1, 'R');
        $pdf->Ln(5);

        // الأنشطة التمويلية
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'التدفقات النقدية من الأنشطة التمويلية', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        
        foreach ($cash_flow_data['financing'] as $item) {
            $pdf->Cell(120, 6, $item['description'], 0, 0);
            $pdf->Cell(70, 6, number_format($item['amount'], 2), 0, 1, 'R');
        }
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(120, 6, 'صافي التدفق النقدي من الأنشطة التمويلية', 0, 0);
        $pdf->Cell(70, 6, number_format($cash_flow_data['financing_total'], 2), 0, 1, 'R');
        $pdf->Ln(10);

        // الإجماليات
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(120, 8, 'صافي التغير في النقدية', 0, 0);
        $pdf->Cell(70, 8, number_format($cash_flow_data['net_change'], 2), 0, 1, 'R');
        
        $pdf->Cell(120, 8, 'النقدية في بداية الفترة', 0, 0);
        $pdf->Cell(70, 8, number_format($cash_flow_data['opening_cash'], 2), 0, 1, 'R');
        
        $pdf->Cell(120, 8, 'النقدية في نهاية الفترة', 0, 0);
        $pdf->Cell(70, 8, number_format($cash_flow_data['closing_cash'], 2), 0, 1, 'R');
    }

    private function addCashFlowToExcel($cash_flow_data) {
        // الأنشطة التشغيلية
        echo '<tr><td colspan="2"><strong>التدفقات النقدية من الأنشطة التشغيلية</strong></td></tr>';
        foreach ($cash_flow_data['operating'] as $item) {
            echo '<tr><td>' . $item['description'] . '</td><td>' . number_format($item['amount'], 2) . '</td></tr>';
        }
        echo '<tr><td><strong>صافي التدفق النقدي من الأنشطة التشغيلية</strong></td><td><strong>' . number_format($cash_flow_data['operating_total'], 2) . '</strong></td></tr>';

        // الأنشطة الاستثمارية
        echo '<tr><td colspan="2"><strong>التدفقات النقدية من الأنشطة الاستثمارية</strong></td></tr>';
        foreach ($cash_flow_data['investing'] as $item) {
            echo '<tr><td>' . $item['description'] . '</td><td>' . number_format($item['amount'], 2) . '</td></tr>';
        }
        echo '<tr><td><strong>صافي التدفق النقدي من الأنشطة الاستثمارية</strong></td><td><strong>' . number_format($cash_flow_data['investing_total'], 2) . '</strong></td></tr>';

        // الأنشطة التمويلية
        echo '<tr><td colspan="2"><strong>التدفقات النقدية من الأنشطة التمويلية</strong></td></tr>';
        foreach ($cash_flow_data['financing'] as $item) {
            echo '<tr><td>' . $item['description'] . '</td><td>' . number_format($item['amount'], 2) . '</td></tr>';
        }
        echo '<tr><td><strong>صافي التدفق النقدي من الأنشطة التمويلية</strong></td><td><strong>' . number_format($cash_flow_data['financing_total'], 2) . '</strong></td></tr>';

        // الإجماليات
        echo '<tr><td><strong>صافي التغير في النقدية</strong></td><td><strong>' . number_format($cash_flow_data['net_change'], 2) . '</strong></td></tr>';
        echo '<tr><td><strong>النقدية في بداية الفترة</strong></td><td><strong>' . number_format($cash_flow_data['opening_cash'], 2) . '</strong></td></tr>';
        echo '<tr><td><strong>النقدية في نهاية الفترة</strong></td><td><strong>' . number_format($cash_flow_data['closing_cash'], 2) . '</strong></td></tr>';
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('access', 'reports/cash_flow')) {
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
