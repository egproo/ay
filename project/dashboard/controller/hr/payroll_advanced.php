<?php
/**
 * تحكم نظام الرواتب المتطور مع التكامل المحاسبي
 *
 * يوفر واجهة شاملة لإدارة الرواتب مع:
 * - إنشاء دورات الرواتب
 * - حساب الرواتب التلقائي
 * - الاعتماد والصرف
 * - التكامل مع المحاسبة
 * - تقارير الرواتب المتقدمة
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerHrPayrollAdvanced extends Controller {

    private $error = [];

    /**
     * الصفحة الرئيسية لإدارة الرواتب
     */
    public function index() {
        $this->load->language('hr/payroll_advanced');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('hr/payroll_advanced');

        $this->getList();
    }

    /**
     * إنشاء دورة رواتب جديدة
     */
    public function create() {
        $this->load->language('hr/payroll_advanced');

        $this->document->setTitle($this->language->get('text_create_cycle'));

        $this->load->model('hr/payroll_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateCreate()) {
            try {
                $cycle_id = $this->model_hr_payroll_advanced->createPayrollCycle($this->request->post);

                $this->session->data['success'] = $this->language->get('text_cycle_created');

                $this->response->redirect($this->url->link('hr/payroll_advanced/view', 'user_token=' . $this->session->data['user_token'] . '&cycle_id=' . $cycle_id, true));
            } catch (Exception $e) {
                $this->error['warning'] = $e->getMessage();
            }
        }

        $this->getCreateForm();
    }

    /**
     * عرض دورة الرواتب
     */
    public function view() {
        $this->load->language('hr/payroll_advanced');

        $this->document->setTitle($this->language->get('text_view_cycle'));

        $this->load->model('hr/payroll_advanced');

        if (isset($this->request->get['cycle_id'])) {
            $cycle_id = (int)$this->request->get['cycle_id'];

            $cycle = $this->model_hr_payroll_advanced->getPayrollCycle($cycle_id);

            if ($cycle) {
                $this->getViewForm($cycle_id);
            } else {
                $this->session->data['error'] = $this->language->get('error_cycle_not_found');
                $this->response->redirect($this->url->link('hr/payroll_advanced', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('hr/payroll_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * اعتماد دورة الرواتب
     */
    public function approve() {
        $this->load->language('hr/payroll_advanced');

        $this->load->model('hr/payroll_advanced');

        $json = [];

        if (isset($this->request->post['cycle_id'])) {
            $cycle_id = (int)$this->request->post['cycle_id'];

            try {
                $journal_id = $this->model_hr_payroll_advanced->approvePayrollCycle($cycle_id);

                $json['success'] = $this->language->get('text_cycle_approved');
                $json['journal_id'] = $journal_id;
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_cycle_id_required');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * صرف الرواتب
     */
    public function disburse() {
        $this->load->language('hr/payroll_advanced');

        $this->load->model('hr/payroll_advanced');

        $json = [];

        if (isset($this->request->post['cycle_id']) && isset($this->request->post['payment_method'])) {
            $cycle_id = (int)$this->request->post['cycle_id'];
            $payment_method = $this->request->post['payment_method'];

            try {
                $journal_id = $this->model_hr_payroll_advanced->disbursePay($cycle_id, $payment_method);

                $json['success'] = $this->language->get('text_cycle_disbursed');
                $json['journal_id'] = $journal_id;
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تصدير قسيمة راتب PDF
     */
    public function payslip() {
        $this->load->model('hr/payroll_advanced');

        if (isset($this->request->get['record_id'])) {
            $record_id = (int)$this->request->get['record_id'];

            try {
                $payslip_data = $this->model_hr_payroll_advanced->generatePayslipPDF($record_id);

                // هنا يمكن إنشاء PDF فعلي
                // مؤقتاً نعرض البيانات
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($payslip_data));
            } catch (Exception $e) {
                $this->session->data['error'] = $e->getMessage();
                $this->response->redirect($this->url->link('hr/payroll_advanced', 'user_token=' . $this->session->data['user_token'], true));
            }
        }
    }

    /**
     * تصدير الرواتب إلى Excel
     */
    public function export() {
        $this->load->model('hr/payroll_advanced');

        if (isset($this->request->get['cycle_id'])) {
            $cycle_id = (int)$this->request->get['cycle_id'];

            try {
                $export_data = $this->model_hr_payroll_advanced->exportPayrollToExcel($cycle_id);

                // هنا يمكن إنشاء ملف Excel فعلي
                // مؤقتاً نعرض البيانات
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($export_data));
            } catch (Exception $e) {
                $this->session->data['error'] = $e->getMessage();
                $this->response->redirect($this->url->link('hr/payroll_advanced', 'user_token=' . $this->session->data['user_token'], true));
            }
        }
    }

    /**
     * لوحة تحكم الرواتب
     */
    public function dashboard() {
        $this->load->language('hr/payroll_advanced');

        $this->document->setTitle($this->language->get('text_payroll_dashboard'));

        $this->load->model('hr/payroll_advanced');

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('hr/payroll_advanced', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_payroll_dashboard'),
            'href' => $this->url->link('hr/payroll_advanced/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        // إحصائيات الرواتب
        $data['statistics'] = $this->getPayrollStatistics();

        // الرسوم البيانية
        $data['charts_data'] = $this->getChartsData();

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('hr/payroll_dashboard', $data));
    }

    /**
     * عرض قائمة دورات الرواتب
     */
    protected function getList() {
        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['filter_date_start'])) {
            $filter_date_start = $this->request->get['filter_date_start'];
        } else {
            $filter_date_start = '';
        }

        if (isset($this->request->get['filter_date_end'])) {
            $filter_date_end = $this->request->get['filter_date_end'];
        } else {
            $filter_date_end = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'date_created';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . urlencode(html_entity_decode($this->request->get['filter_status'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . urlencode(html_entity_decode($this->request->get['filter_date_start'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . urlencode(html_entity_decode($this->request->get['filter_date_end'], ENT_QUOTES, 'UTF-8'));
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

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('hr/payroll_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true)
        ];

        $data['create'] = $this->url->link('hr/payroll_advanced/create', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['dashboard'] = $this->url->link('hr/payroll_advanced/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        $data['cycles'] = [];

        $filter_data = [
            'filter_status' => $filter_status,
            'filter_date_start' => $filter_date_start,
            'filter_date_end' => $filter_date_end,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        ];

        $cycles = $this->model_hr_payroll_advanced->getPayrollCycles($filter_data);

        foreach ($cycles as $cycle) {
            $data['cycles'][] = [
                'cycle_id' => $cycle['cycle_id'],
                'cycle_name' => $cycle['cycle_name'],
                'period_start' => date($this->language->get('date_format_short'), strtotime($cycle['period_start'])),
                'period_end' => date($this->language->get('date_format_short'), strtotime($cycle['period_end'])),
                'pay_date' => date($this->language->get('date_format_short'), strtotime($cycle['pay_date'])),
                'employee_count' => $cycle['employee_count'],
                'total_amount' => $this->currency->format($cycle['total_amount'], $this->config->get('config_currency')),
                'status' => $cycle['status'],
                'status_text' => $this->language->get('text_status_' . $cycle['status']),
                'created_by_name' => $cycle['created_by_name'],
                'date_created' => date($this->language->get('datetime_format'), strtotime($cycle['date_created'])),
                'view' => $this->url->link('hr/payroll_advanced/view', 'user_token=' . $this->session->data['user_token'] . '&cycle_id=' . $cycle['cycle_id'] . $url, true)
            ];
        }

        // إحصائيات سريعة
        $data['statistics'] = $this->getQuickStatistics();

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
            $data['selected'] = [];
        }

        $data['filter_status'] = $filter_status;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('hr/payroll_advanced_list', $data));
    }

    /**
     * نموذج إنشاء دورة رواتب
     */
    protected function getCreateForm() {
        $data['text_form'] = $this->language->get('text_create_cycle');

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('hr/payroll_advanced', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_create_cycle'),
            'href' => $this->url->link('hr/payroll_advanced/create', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['action'] = $this->url->link('hr/payroll_advanced/create', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('hr/payroll_advanced', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->post['cycle_name'])) {
            $data['cycle_name'] = $this->request->post['cycle_name'];
        } else {
            $data['cycle_name'] = '';
        }

        if (isset($this->request->post['period_start'])) {
            $data['period_start'] = $this->request->post['period_start'];
        } else {
            $data['period_start'] = date('Y-m-01'); // أول الشهر الحالي
        }

        if (isset($this->request->post['period_end'])) {
            $data['period_end'] = $this->request->post['period_end'];
        } else {
            $data['period_end'] = date('Y-m-t'); // آخر الشهر الحالي
        }

        if (isset($this->request->post['pay_date'])) {
            $data['pay_date'] = $this->request->post['pay_date'];
        } else {
            $data['pay_date'] = date('Y-m-d', strtotime('+5 days')); // بعد 5 أيام
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('hr/payroll_advanced_form', $data));
    }

    /**
     * نموذج عرض دورة الرواتب
     */
    protected function getViewForm($cycle_id) {
        $cycle = $this->model_hr_payroll_advanced->getPayrollCycle($cycle_id);
        $records = $this->model_hr_payroll_advanced->getPayrollRecords($cycle_id);
        $totals = $this->model_hr_payroll_advanced->getPayrollCycleTotals($cycle_id);

        $data['cycle'] = $cycle;
        $data['totals'] = $totals;

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('hr/payroll_advanced', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $cycle['cycle_name'],
            'href' => $this->url->link('hr/payroll_advanced/view', 'user_token=' . $this->session->data['user_token'] . '&cycle_id=' . $cycle_id, true)
        ];

        $data['records'] = [];

        foreach ($records as $record) {
            $data['records'][] = [
                'record_id' => $record['record_id'],
                'employee_name' => $record['employee_name'],
                'job_title' => $record['job_title'],
                'basic_salary' => $this->currency->format($record['basic_salary'], $this->config->get('config_currency')),
                'total_allowances' => $this->currency->format($record['total_allowances'], $this->config->get('config_currency')),
                'total_deductions' => $this->currency->format($record['total_deductions'], $this->config->get('config_currency')),
                'gross_salary' => $this->currency->format($record['gross_salary'], $this->config->get('config_currency')),
                'net_salary' => $this->currency->format($record['net_salary'], $this->config->get('config_currency')),
                'working_days' => $record['working_days'],
                'absent_days' => $record['absent_days'],
                'overtime_hours' => $record['overtime_hours'],
                'status' => $record['status'],
                'payslip' => $this->url->link('hr/payroll_advanced/payslip', 'user_token=' . $this->session->data['user_token'] . '&record_id=' . $record['record_id'], true)
            ];
        }

        // URLs للإجراءات
        $data['approve_url'] = $this->url->link('hr/payroll_advanced/approve', 'user_token=' . $this->session->data['user_token'], true);
        $data['disburse_url'] = $this->url->link('hr/payroll_advanced/disburse', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_url'] = $this->url->link('hr/payroll_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&cycle_id=' . $cycle_id, true);

        $data['back'] = $this->url->link('hr/payroll_advanced', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        } else {
            $data['error_warning'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('hr/payroll_advanced_view', $data));
    }

    /**
     * التحقق من صحة بيانات إنشاء دورة الرواتب
     */
    protected function validateCreate() {
        if (!$this->user->hasPermission('modify', 'hr/payroll_advanced')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['cycle_name']) < 3) || (utf8_strlen($this->request->post['cycle_name']) > 64)) {
            $this->error['cycle_name'] = $this->language->get('error_cycle_name');
        }

        if (empty($this->request->post['period_start'])) {
            $this->error['period_start'] = $this->language->get('error_period_start');
        }

        if (empty($this->request->post['period_end'])) {
            $this->error['period_end'] = $this->language->get('error_period_end');
        }

        if (empty($this->request->post['pay_date'])) {
            $this->error['pay_date'] = $this->language->get('error_pay_date');
        }

        // التحقق من أن تاريخ النهاية بعد تاريخ البداية
        if (!empty($this->request->post['period_start']) && !empty($this->request->post['period_end'])) {
            if (strtotime($this->request->post['period_end']) <= strtotime($this->request->post['period_start'])) {
                $this->error['period_end'] = $this->language->get('error_period_end_before_start');
            }
        }

        return !$this->error;
    }

    /**
     * الحصول على إحصائيات سريعة
     */
    private function getQuickStatistics() {
        $statistics = [];

        // إجمالي الرواتب هذا الشهر
        $current_month_cycles = $this->model_hr_payroll_advanced->getPayrollCycles([
            'filter_date_start' => date('Y-m-01'),
            'filter_date_end' => date('Y-m-t')
        ]);

        $total_this_month = 0;
        $cycles_this_month = 0;

        foreach ($current_month_cycles as $cycle) {
            $total_this_month += $cycle['total_amount'];
            $cycles_this_month++;
        }

        $statistics['total_this_month'] = $this->currency->format($total_this_month, $this->config->get('config_currency'));
        $statistics['cycles_this_month'] = $cycles_this_month;

        // الدورات المعلقة
        $pending_cycles = $this->model_hr_payroll_advanced->getPayrollCycles([
            'filter_status' => 'calculated'
        ]);

        $statistics['pending_cycles'] = count($pending_cycles);

        // الدورات المعتمدة وغير مصروفة
        $approved_cycles = $this->model_hr_payroll_advanced->getPayrollCycles([
            'filter_status' => 'approved'
        ]);

        $statistics['approved_cycles'] = count($approved_cycles);

        return $statistics;
    }

    /**
     * الحصول على إحصائيات الرواتب للوحة التحكم
     */
    private function getPayrollStatistics() {
        $statistics = [];

        // إحصائيات الـ12 شهر الماضية
        $monthly_stats = [];

        for ($i = 11; $i >= 0; $i--) {
            $month_start = date('Y-m-01', strtotime("-$i months"));
            $month_end = date('Y-m-t', strtotime("-$i months"));

            $month_cycles = $this->model_hr_payroll_advanced->getPayrollCycles([
                'filter_date_start' => $month_start,
                'filter_date_end' => $month_end,
                'filter_status' => 'paid'
            ]);

            $month_total = 0;
            foreach ($month_cycles as $cycle) {
                $month_total += $cycle['total_amount'];
            }

            $monthly_stats[] = [
                'month' => date('M Y', strtotime($month_start)),
                'total' => $month_total,
                'cycles' => count($month_cycles)
            ];
        }

        $statistics['monthly_stats'] = $monthly_stats;

        // إحصائيات حسب الحالة
        $status_stats = [];
        $statuses = ['draft', 'calculated', 'approved', 'paid'];

        foreach ($statuses as $status) {
            $status_cycles = $this->model_hr_payroll_advanced->getPayrollCycles([
                'filter_status' => $status
            ]);

            $status_total = 0;
            foreach ($status_cycles as $cycle) {
                $status_total += $cycle['total_amount'];
            }

            $status_stats[] = [
                'status' => $status,
                'count' => count($status_cycles),
                'total' => $status_total
            ];
        }

        $statistics['status_stats'] = $status_stats;

        return $statistics;
    }

    /**
     * الحصول على بيانات الرسوم البيانية
     */
    private function getChartsData() {
        $charts = [];

        // رسم بياني للرواتب الشهرية
        $monthly_data = [];
        $monthly_labels = [];

        for ($i = 11; $i >= 0; $i--) {
            $month_start = date('Y-m-01', strtotime("-$i months"));
            $month_end = date('Y-m-t', strtotime("-$i months"));

            $month_cycles = $this->model_hr_payroll_advanced->getPayrollCycles([
                'filter_date_start' => $month_start,
                'filter_date_end' => $month_end,
                'filter_status' => 'paid'
            ]);

            $month_total = 0;
            foreach ($month_cycles as $cycle) {
                $month_total += $cycle['total_amount'];
            }

            $monthly_labels[] = date('M Y', strtotime($month_start));
            $monthly_data[] = $month_total;
        }

        $charts['monthly_chart'] = [
            'labels' => $monthly_labels,
            'data' => $monthly_data
        ];

        // رسم بياني دائري للحالات
        $status_labels = [];
        $status_data = [];
        $status_colors = [
            'draft' => '#6c757d',
            'calculated' => '#ffc107',
            'approved' => '#17a2b8',
            'paid' => '#28a745'
        ];

        $statuses = ['draft', 'calculated', 'approved', 'paid'];

        foreach ($statuses as $status) {
            $status_cycles = $this->model_hr_payroll_advanced->getPayrollCycles([
                'filter_status' => $status
            ]);

            if (count($status_cycles) > 0) {
                $status_labels[] = $this->language->get('text_status_' . $status);
                $status_data[] = count($status_cycles);
            }
        }

        $charts['status_chart'] = [
            'labels' => $status_labels,
            'data' => $status_data,
            'colors' => array_values($status_colors)
        ];

        return $charts;
    }
}
