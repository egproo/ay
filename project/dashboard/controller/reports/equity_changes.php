<?php
/**
 * تحكم قائمة التغير في حقوق الملكية المحسن
 * يدعم إنشاء قائمة التغير في حقوق الملكية وفقاً للمعايير المحاسبية
 */
class ControllerReportsEquityChanges extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('reports/equity_changes');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('reports/equity_changes');
        $this->getReport();
    }

    public function generate() {
        $this->load->language('reports/equity_changes');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('reports/equity_changes');

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
            'href' => $this->url->link('reports/equity_changes', 'user_token=' . $this->session->data['user_token'], true)
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

        if (isset($this->request->post['include_retained_earnings'])) {
            $include_retained_earnings = $this->request->post['include_retained_earnings'];
        } else {
            $include_retained_earnings = 1;
        }

        // إنشاء التقرير
        $equity_data = $this->model_reports_equity_changes->generateEquityChangesStatement($date_start, $date_end, $include_retained_earnings);

        $data['equity_changes'] = $equity_data;
        $data['date_start'] = $date_start;
        $data['date_end'] = $date_end;
        $data['include_retained_earnings'] = $include_retained_earnings;
        $data['date_start_formatted'] = date($this->language->get('date_format_short'), strtotime($date_start));
        $data['date_end_formatted'] = date($this->language->get('date_format_short'), strtotime($date_end));

        // روابط التصدير
        $data['export_pdf'] = $this->url->link('reports/equity_changes/exportPdf', 'user_token=' . $this->session->data['user_token'] . '&date_start=' . $date_start . '&date_end=' . $date_end . '&include_retained_earnings=' . $include_retained_earnings, true);
        $data['export_excel'] = $this->url->link('reports/equity_changes/exportExcel', 'user_token=' . $this->session->data['user_token'] . '&date_start=' . $date_start . '&date_end=' . $date_end . '&include_retained_earnings=' . $include_retained_earnings, true);

        $data['generate'] = $this->url->link('reports/equity_changes/generate', 'user_token=' . $this->session->data['user_token'], true);

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

        $this->response->setOutput($this->load->view('reports/equity_changes', $data));
    }

    protected function getForm() {
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('reports/equity_changes', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('reports/equity_changes/generate', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('reports/equity_changes', 'user_token=' . $this->session->data['user_token'], true);

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

        if (isset($this->request->post['include_retained_earnings'])) {
            $data['include_retained_earnings'] = $this->request->post['include_retained_earnings'];
        } else {
            $data['include_retained_earnings'] = 1;
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

        $this->response->setOutput($this->load->view('reports/equity_changes_form', $data));
    }

    public function exportPdf() {
        $this->load->language('reports/equity_changes');
        $this->load->model('reports/equity_changes');

        $date_start = $this->request->get['date_start'] ?? date('Y-01-01');
        $date_end = $this->request->get['date_end'] ?? date('Y-m-d');
        $include_retained_earnings = $this->request->get['include_retained_earnings'] ?? 1;

        $equity_data = $this->model_reports_equity_changes->generateEquityChangesStatement($date_start, $date_end, $include_retained_earnings);

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
        $this->addEquityChangesToPdf($pdf, $equity_data);

        $pdf->Output('equity_changes_' . date('Y-m-d') . '.pdf', 'D');
    }

    public function exportExcel() {
        $this->load->language('reports/equity_changes');
        $this->load->model('reports/equity_changes');

        $date_start = $this->request->get['date_start'] ?? date('Y-01-01');
        $date_end = $this->request->get['date_end'] ?? date('Y-m-d');
        $include_retained_earnings = $this->request->get['include_retained_earnings'] ?? 1;

        $equity_data = $this->model_reports_equity_changes->generateEquityChangesStatement($date_start, $date_end, $include_retained_earnings);

        // إنشاء Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="equity_changes_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');

        echo '<table border="1">';
        echo '<tr><th colspan="' . (count($equity_data['equity_accounts']) + 2) . '">' . $this->language->get('heading_title') . '</th></tr>';
        echo '<tr><th colspan="' . (count($equity_data['equity_accounts']) + 2) . '">من ' . date('d/m/Y', strtotime($date_start)) . ' إلى ' . date('d/m/Y', strtotime($date_end)) . '</th></tr>';

        $this->addEquityChangesToExcel($equity_data);

        echo '</table>';
    }

    private function addEquityChangesToPdf($pdf, $equity_data) {
        $pdf->SetFont('Arial', 'B', 10);
        
        // رؤوس الأعمدة
        $pdf->Cell(60, 8, 'البيان', 1, 0, 'C');
        foreach ($equity_data['equity_accounts'] as $account) {
            $pdf->Cell(30, 8, $account['name'], 1, 0, 'C');
        }
        $pdf->Cell(30, 8, 'الإجمالي', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 9);
        
        // الرصيد الافتتاحي
        $pdf->Cell(60, 6, 'الرصيد في بداية الفترة', 1, 0);
        foreach ($equity_data['equity_accounts'] as $account) {
            $pdf->Cell(30, 6, number_format($account['opening_balance'], 2), 1, 0, 'R');
        }
        $pdf->Cell(30, 6, number_format($equity_data['total_opening'], 2), 1, 1, 'R');

        // التغيرات خلال الفترة
        foreach ($equity_data['changes'] as $change) {
            $pdf->Cell(60, 6, $change['description'], 1, 0);
            foreach ($equity_data['equity_accounts'] as $account) {
                $amount = $change['amounts'][$account['account_id']] ?? 0;
                $pdf->Cell(30, 6, number_format($amount, 2), 1, 0, 'R');
            }
            $pdf->Cell(30, 6, number_format($change['total'], 2), 1, 1, 'R');
        }

        // الرصيد الختامي
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(60, 6, 'الرصيد في نهاية الفترة', 1, 0);
        foreach ($equity_data['equity_accounts'] as $account) {
            $pdf->Cell(30, 6, number_format($account['closing_balance'], 2), 1, 0, 'R');
        }
        $pdf->Cell(30, 6, number_format($equity_data['total_closing'], 2), 1, 1, 'R');
    }

    private function addEquityChangesToExcel($equity_data) {
        // رؤوس الأعمدة
        echo '<tr><th>البيان</th>';
        foreach ($equity_data['equity_accounts'] as $account) {
            echo '<th>' . $account['name'] . '</th>';
        }
        echo '<th>الإجمالي</th></tr>';

        // الرصيد الافتتاحي
        echo '<tr><td>الرصيد في بداية الفترة</td>';
        foreach ($equity_data['equity_accounts'] as $account) {
            echo '<td>' . number_format($account['opening_balance'], 2) . '</td>';
        }
        echo '<td>' . number_format($equity_data['total_opening'], 2) . '</td></tr>';

        // التغيرات خلال الفترة
        foreach ($equity_data['changes'] as $change) {
            echo '<tr><td>' . $change['description'] . '</td>';
            foreach ($equity_data['equity_accounts'] as $account) {
                $amount = $change['amounts'][$account['account_id']] ?? 0;
                echo '<td>' . number_format($amount, 2) . '</td>';
            }
            echo '<td>' . number_format($change['total'], 2) . '</td></tr>';
        }

        // الرصيد الختامي
        echo '<tr><td><strong>الرصيد في نهاية الفترة</strong></td>';
        foreach ($equity_data['equity_accounts'] as $account) {
            echo '<td><strong>' . number_format($account['closing_balance'], 2) . '</strong></td>';
        }
        echo '<td><strong>' . number_format($equity_data['total_closing'], 2) . '</strong></td></tr>';
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('access', 'reports/equity_changes')) {
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
