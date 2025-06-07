<?php
/**
 * تحكم تحليل الربحية المتقدم
 * يدعم تحليل الربحية حسب المنتج، العميل، الفرع، الفترة الزمنية، وغيرها
 */
class ControllerReportsProfitabilityAnalysis extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('reports/profitability_analysis');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('reports/profitability_analysis');
        $this->getReport();
    }

    public function generate() {
        $this->load->language('reports/profitability_analysis');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('reports/profitability_analysis');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->getReport();
        } else {
            $this->getForm();
        }
    }

    public function byProduct() {
        $this->load->language('reports/profitability_analysis');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('reports/profitability_analysis');
        $this->getProductProfitability();
    }

    public function byCustomer() {
        $this->load->language('reports/profitability_analysis');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('reports/profitability_analysis');
        $this->getCustomerProfitability();
    }

    public function byCategory() {
        $this->load->language('reports/profitability_analysis');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('reports/profitability_analysis');
        $this->getCategoryProfitability();
    }

    public function byPeriod() {
        $this->load->language('reports/profitability_analysis');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('reports/profitability_analysis');
        $this->getPeriodProfitability();
    }

    protected function getReport() {
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('reports/profitability_analysis', 'user_token=' . $this->session->data['user_token'], true)
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

        if (isset($this->request->post['analysis_type'])) {
            $analysis_type = $this->request->post['analysis_type'];
        } else {
            $analysis_type = 'overview';
        }

        if (isset($this->request->post['group_by'])) {
            $group_by = $this->request->post['group_by'];
        } else {
            $group_by = 'product';
        }

        // إنشاء التقرير
        $profitability_data = $this->model_reports_profitability_analysis->generateProfitabilityAnalysis($date_start, $date_end, $analysis_type, $group_by);

        $data['profitability'] = $profitability_data;
        $data['date_start'] = $date_start;
        $data['date_end'] = $date_end;
        $data['analysis_type'] = $analysis_type;
        $data['group_by'] = $group_by;
        $data['date_start_formatted'] = date($this->language->get('date_format_short'), strtotime($date_start));
        $data['date_end_formatted'] = date($this->language->get('date_format_short'), strtotime($date_end));

        // روابط التصدير
        $data['export_pdf'] = $this->url->link('reports/profitability_analysis/exportPdf', 'user_token=' . $this->session->data['user_token'] . '&date_start=' . $date_start . '&date_end=' . $date_end . '&analysis_type=' . $analysis_type . '&group_by=' . $group_by, true);
        $data['export_excel'] = $this->url->link('reports/profitability_analysis/exportExcel', 'user_token=' . $this->session->data['user_token'] . '&date_start=' . $date_start . '&date_end=' . $date_end . '&analysis_type=' . $analysis_type . '&group_by=' . $group_by, true);

        // روابط التحليلات المختلفة
        $data['by_product'] = $this->url->link('reports/profitability_analysis/byProduct', 'user_token=' . $this->session->data['user_token'] . '&date_start=' . $date_start . '&date_end=' . $date_end, true);
        $data['by_customer'] = $this->url->link('reports/profitability_analysis/byCustomer', 'user_token=' . $this->session->data['user_token'] . '&date_start=' . $date_start . '&date_end=' . $date_end, true);
        $data['by_category'] = $this->url->link('reports/profitability_analysis/byCategory', 'user_token=' . $this->session->data['user_token'] . '&date_start=' . $date_start . '&date_end=' . $date_end, true);
        $data['by_period'] = $this->url->link('reports/profitability_analysis/byPeriod', 'user_token=' . $this->session->data['user_token'] . '&date_start=' . $date_start . '&date_end=' . $date_end, true);

        $data['generate'] = $this->url->link('reports/profitability_analysis/generate', 'user_token=' . $this->session->data['user_token'], true);

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

        $this->response->setOutput($this->load->view('reports/profitability_analysis', $data));
    }

    protected function getProductProfitability() {
        $date_start = $this->request->get['date_start'] ?? date('Y-m-01');
        $date_end = $this->request->get['date_end'] ?? date('Y-m-d');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('reports/profitability_analysis', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_product_profitability'),
            'href' => $this->url->link('reports/profitability_analysis/byProduct', 'user_token=' . $this->session->data['user_token'], true)
        );

        $product_profitability = $this->model_reports_profitability_analysis->getProductProfitability($date_start, $date_end);

        $data['products'] = $product_profitability;
        $data['date_start'] = $date_start;
        $data['date_end'] = $date_end;
        $data['date_start_formatted'] = date($this->language->get('date_format_short'), strtotime($date_start));
        $data['date_end_formatted'] = date($this->language->get('date_format_short'), strtotime($date_end));

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('reports/profitability_by_product', $data));
    }

    protected function getCustomerProfitability() {
        $date_start = $this->request->get['date_start'] ?? date('Y-m-01');
        $date_end = $this->request->get['date_end'] ?? date('Y-m-d');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('reports/profitability_analysis', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_customer_profitability'),
            'href' => $this->url->link('reports/profitability_analysis/byCustomer', 'user_token=' . $this->session->data['user_token'], true)
        );

        $customer_profitability = $this->model_reports_profitability_analysis->getCustomerProfitability($date_start, $date_end);

        $data['customers'] = $customer_profitability;
        $data['date_start'] = $date_start;
        $data['date_end'] = $date_end;
        $data['date_start_formatted'] = date($this->language->get('date_format_short'), strtotime($date_start));
        $data['date_end_formatted'] = date($this->language->get('date_format_short'), strtotime($date_end));

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('reports/profitability_by_customer', $data));
    }

    protected function getCategoryProfitability() {
        $date_start = $this->request->get['date_start'] ?? date('Y-m-01');
        $date_end = $this->request->get['date_end'] ?? date('Y-m-d');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('reports/profitability_analysis', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_category_profitability'),
            'href' => $this->url->link('reports/profitability_analysis/byCategory', 'user_token=' . $this->session->data['user_token'], true)
        );

        $category_profitability = $this->model_reports_profitability_analysis->getCategoryProfitability($date_start, $date_end);

        $data['categories'] = $category_profitability;
        $data['date_start'] = $date_start;
        $data['date_end'] = $date_end;
        $data['date_start_formatted'] = date($this->language->get('date_format_short'), strtotime($date_start));
        $data['date_end_formatted'] = date($this->language->get('date_format_short'), strtotime($date_end));

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('reports/profitability_by_category', $data));
    }

    protected function getPeriodProfitability() {
        $date_start = $this->request->get['date_start'] ?? date('Y-01-01');
        $date_end = $this->request->get['date_end'] ?? date('Y-m-d');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('reports/profitability_analysis', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_period_profitability'),
            'href' => $this->url->link('reports/profitability_analysis/byPeriod', 'user_token=' . $this->session->data['user_token'], true)
        );

        $period_profitability = $this->model_reports_profitability_analysis->getPeriodProfitability($date_start, $date_end);

        $data['periods'] = $period_profitability;
        $data['date_start'] = $date_start;
        $data['date_end'] = $date_end;
        $data['date_start_formatted'] = date($this->language->get('date_format_short'), strtotime($date_start));
        $data['date_end_formatted'] = date($this->language->get('date_format_short'), strtotime($date_end));

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('reports/profitability_by_period', $data));
    }

    protected function getForm() {
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('reports/profitability_analysis', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('reports/profitability_analysis/generate', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('reports/profitability_analysis', 'user_token=' . $this->session->data['user_token'], true);

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

        if (isset($this->request->post['analysis_type'])) {
            $data['analysis_type'] = $this->request->post['analysis_type'];
        } else {
            $data['analysis_type'] = 'overview';
        }

        if (isset($this->request->post['group_by'])) {
            $data['group_by'] = $this->request->post['group_by'];
        } else {
            $data['group_by'] = 'product';
        }

        // خيارات التحليل
        $data['analysis_types'] = array(
            'overview' => $this->language->get('text_overview'),
            'detailed' => $this->language->get('text_detailed'),
            'comparative' => $this->language->get('text_comparative'),
            'trend' => $this->language->get('text_trend')
        );

        $data['group_by_options'] = array(
            'product' => $this->language->get('text_by_product'),
            'customer' => $this->language->get('text_by_customer'),
            'category' => $this->language->get('text_by_category'),
            'period' => $this->language->get('text_by_period'),
            'salesperson' => $this->language->get('text_by_salesperson'),
            'location' => $this->language->get('text_by_location')
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

        $this->response->setOutput($this->load->view('reports/profitability_analysis_form', $data));
    }

    public function exportPdf() {
        $this->load->language('reports/profitability_analysis');
        $this->load->model('reports/profitability_analysis');

        $date_start = $this->request->get['date_start'] ?? date('Y-m-01');
        $date_end = $this->request->get['date_end'] ?? date('Y-m-d');
        $analysis_type = $this->request->get['analysis_type'] ?? 'overview';
        $group_by = $this->request->get['group_by'] ?? 'product';

        $profitability_data = $this->model_reports_profitability_analysis->generateProfitabilityAnalysis($date_start, $date_end, $analysis_type, $group_by);

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
        $this->addProfitabilityToPdf($pdf, $profitability_data);

        $pdf->Output('profitability_analysis_' . date('Y-m-d') . '.pdf', 'D');
    }

    public function exportExcel() {
        $this->load->language('reports/profitability_analysis');
        $this->load->model('reports/profitability_analysis');

        $date_start = $this->request->get['date_start'] ?? date('Y-m-01');
        $date_end = $this->request->get['date_end'] ?? date('Y-m-d');
        $analysis_type = $this->request->get['analysis_type'] ?? 'overview';
        $group_by = $this->request->get['group_by'] ?? 'product';

        $profitability_data = $this->model_reports_profitability_analysis->generateProfitabilityAnalysis($date_start, $date_end, $analysis_type, $group_by);

        // إنشاء Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="profitability_analysis_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');

        echo '<table border="1">';
        echo '<tr><th colspan="6">' . $this->language->get('heading_title') . '</th></tr>';
        echo '<tr><th colspan="6">من ' . date('d/m/Y', strtotime($date_start)) . ' إلى ' . date('d/m/Y', strtotime($date_end)) . '</th></tr>';

        $this->addProfitabilityToExcel($profitability_data);

        echo '</table>';
    }

    private function addProfitabilityToPdf($pdf, $profitability_data) {
        $pdf->SetFont('Arial', 'B', 10);
        
        // رؤوس الأعمدة
        $pdf->Cell(40, 8, 'البيان', 1, 0, 'C');
        $pdf->Cell(25, 8, 'المبيعات', 1, 0, 'C');
        $pdf->Cell(25, 8, 'التكلفة', 1, 0, 'C');
        $pdf->Cell(25, 8, 'الربح', 1, 0, 'C');
        $pdf->Cell(25, 8, 'هامش الربح', 1, 0, 'C');
        $pdf->Cell(25, 8, 'الكمية', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 9);
        
        foreach ($profitability_data['items'] as $item) {
            $pdf->Cell(40, 6, $item['name'], 1, 0);
            $pdf->Cell(25, 6, number_format($item['revenue'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($item['cost'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($item['profit'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($item['margin'], 2) . '%', 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($item['quantity'], 0), 1, 1, 'R');
        }

        // الإجماليات
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(40, 6, 'الإجمالي', 1, 0);
        $pdf->Cell(25, 6, number_format($profitability_data['totals']['revenue'], 2), 1, 0, 'R');
        $pdf->Cell(25, 6, number_format($profitability_data['totals']['cost'], 2), 1, 0, 'R');
        $pdf->Cell(25, 6, number_format($profitability_data['totals']['profit'], 2), 1, 0, 'R');
        $pdf->Cell(25, 6, number_format($profitability_data['totals']['margin'], 2) . '%', 1, 0, 'R');
        $pdf->Cell(25, 6, number_format($profitability_data['totals']['quantity'], 0), 1, 1, 'R');
    }

    private function addProfitabilityToExcel($profitability_data) {
        // رؤوس الأعمدة
        echo '<tr><th>البيان</th><th>المبيعات</th><th>التكلفة</th><th>الربح</th><th>هامش الربح</th><th>الكمية</th></tr>';

        foreach ($profitability_data['items'] as $item) {
            echo '<tr>';
            echo '<td>' . $item['name'] . '</td>';
            echo '<td>' . number_format($item['revenue'], 2) . '</td>';
            echo '<td>' . number_format($item['cost'], 2) . '</td>';
            echo '<td>' . number_format($item['profit'], 2) . '</td>';
            echo '<td>' . number_format($item['margin'], 2) . '%</td>';
            echo '<td>' . number_format($item['quantity'], 0) . '</td>';
            echo '</tr>';
        }

        // الإجماليات
        echo '<tr>';
        echo '<td><strong>الإجمالي</strong></td>';
        echo '<td><strong>' . number_format($profitability_data['totals']['revenue'], 2) . '</strong></td>';
        echo '<td><strong>' . number_format($profitability_data['totals']['cost'], 2) . '</strong></td>';
        echo '<td><strong>' . number_format($profitability_data['totals']['profit'], 2) . '</strong></td>';
        echo '<td><strong>' . number_format($profitability_data['totals']['margin'], 2) . '%</strong></td>';
        echo '<td><strong>' . number_format($profitability_data['totals']['quantity'], 0) . '</strong></td>';
        echo '</tr>';
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('access', 'reports/profitability_analysis')) {
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
