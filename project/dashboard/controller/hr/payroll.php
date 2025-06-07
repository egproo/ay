<?php
class ControllerHrPayroll extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('hr/payroll');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('hr/payroll');

        $data['user_token'] = $this->session->data['user_token'];

        // الروابط لأجاكس
        $data['ajax_list_url']     = $this->url->link('hr/payroll/list', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_save_url']     = $this->url->link('hr/payroll/save', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_get_url']      = $this->url->link('hr/payroll/getForm', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_delete_url']   = $this->url->link('hr/payroll/delete', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_entries_url']  = $this->url->link('hr/payroll/entriesList', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_mark_paid_url']= $this->url->link('hr/payroll/markPaid', 'user_token=' . $this->session->data['user_token'], true);

        // روابط الميزات المتقدمة
        $data['ajax_generate_url'] = $this->url->link('hr/payroll/generatePayroll', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_statistics_url'] = $this->url->link('hr/payroll/getStatistics', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_export_url']   = $this->url->link('hr/payroll/exportData', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_search_url']   = $this->url->link('hr/payroll/advancedSearch', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_tax_settings_url'] = $this->url->link('hr/payroll/updateTaxSettings', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_bulk_update_url'] = $this->url->link('hr/payroll/bulkUpdatePaymentStatus', 'user_token=' . $this->session->data['user_token'], true);

        // عناوين وشروح
        $data['heading_title']     = $this->language->get('heading_title');
        $data['text_filter']       = $this->language->get('text_filter');
        $data['text_enter_period_name'] = $this->language->get('text_enter_period_name');
        $data['text_all_statuses'] = $this->language->get('text_all_statuses');
        $data['text_status_open']  = $this->language->get('text_status_open');
        $data['text_status_closed']= $this->language->get('text_status_closed');
        $data['button_filter']     = $this->language->get('button_filter');
        $data['button_reset']      = $this->language->get('button_reset');
        $data['button_add_period'] = $this->language->get('button_add_period');
        $data['button_close']      = $this->language->get('button_close');
        $data['button_save']       = $this->language->get('button_save');

        $data['text_payroll_list'] = $this->language->get('text_payroll_list');
        $data['text_add_period']   = $this->language->get('text_add_period');
        $data['text_edit_period']  = $this->language->get('text_edit_period');
        $data['text_ajax_error']   = $this->language->get('text_ajax_error');
        $data['text_confirm_delete']= $this->language->get('text_confirm_delete');
        $data['text_view_entries'] = $this->language->get('text_view_entries');
        $data['text_view_entries_for_period'] = $this->language->get('text_view_entries_for_period');

        $data['column_period_name']= $this->language->get('column_period_name');
        $data['column_start_date'] = $this->language->get('column_start_date');
        $data['column_end_date']   = $this->language->get('column_end_date');
        $data['column_status']     = $this->language->get('column_status');
        $data['column_actions']    = $this->language->get('column_actions');

        $data['text_start_date']   = $this->language->get('text_start_date');
        $data['text_end_date']     = $this->language->get('text_end_date');
        $data['text_enter_period_name']= $this->language->get('text_enter_period_name');

        // لأجل الـEntries
        $data['text_view_entries'] = $this->language->get('text_view_entries');
        $data['column_employee']   = $this->language->get('column_employee');
        $data['column_base_salary']= $this->language->get('column_base_salary');
        $data['column_allowances'] = $this->language->get('column_allowances');
        $data['column_deductions'] = $this->language->get('column_deductions');
        $data['column_net_salary'] = $this->language->get('column_net_salary');
        $data['column_payment_status']= $this->language->get('column_payment_status');

        $data['button_close']      = $this->language->get('button_close');
        $data['text_ajax_error']   = $this->language->get('text_ajax_error');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('hr/payroll_list', $data));
    }

    public function list() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $filter_name = isset($this->request->post['filter_name']) ? $this->request->post['filter_name'] : '';
        $filter_date_start = isset($this->request->post['filter_date_start']) ? $this->request->post['filter_date_start'] : '';
        $filter_date_end = isset($this->request->post['filter_date_end']) ? $this->request->post['filter_date_end'] : '';
        $filter_status = isset($this->request->post['filter_status']) ? $this->request->post['filter_status'] : '';

        $start  = isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0;
        $length = isset($this->request->post['length']) ? (int)$this->request->post['length'] : 10;
        $draw   = isset($this->request->post['draw']) ? (int)$this->request->post['draw'] : 1;
        $order_column = isset($this->request->post['order'][0]['column']) ? (int)$this->request->post['order'][0]['column'] : 0;
        $order_dir = isset($this->request->post['order'][0]['dir']) ? $this->request->post['order'][0]['dir'] : 'asc';

        // أعمدة DataTable
        $columns = array('period_name','start_date','end_date','status');
        $order_by = isset($columns[$order_column]) ? $columns[$order_column] : 'start_date';

        $filter_data = array(
            'filter_name'       => $filter_name,
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end,
            'filter_status'     => $filter_status,
            'start'             => $start,
            'limit'             => $length,
            'order'             => $order_dir,
            'sort'              => $order_by
        );

        $total = $this->model_hr_payroll->getTotalPayrollPeriods($filter_data);
        $results = $this->model_hr_payroll->getPayrollPeriods($filter_data);

        $data = array();
        foreach ($results as $result) {
            // Actions
            $actions = '';
            if ($this->user->hasPermission('modify', 'hr/payroll')) {
                $actions .= '<button class="btn btn-info btn-sm btn-view-entries" data-id="'. $result['payroll_period_id'] .'" data-periodname="'.htmlspecialchars($result['period_name'], ENT_QUOTES).'"><i class="fa fa-eye"></i> '.$this->language->get('button_view_entries').'</button> ';
                $actions .= '<button class="btn btn-primary btn-sm btn-edit" data-id="'. $result['payroll_period_id'] .'"><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm btn-delete" data-id="'. $result['payroll_period_id'] .'"><i class="fa fa-trash"></i></button>';
            } else {
                $actions .= '<button class="btn btn-info btn-sm" disabled><i class="fa fa-eye"></i></button> ';
                $actions .= '<button class="btn btn-primary btn-sm" disabled><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm" disabled><i class="fa fa-trash"></i></button>';
            }

            $data[] = array(
                'period_name' => $result['period_name'],
                'start_date'  => $result['start_date'],
                'end_date'    => $result['end_date'],
                'status'      => ($result['status'] == 'open') ? $this->language->get('text_status_open') : $this->language->get('text_status_closed'),
                'actions'     => $actions
            );
        }

        $json = array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $data
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getForm() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $json = array();

        if (isset($this->request->post['payroll_period_id'])) {
            $payroll_period_id = (int)$this->request->post['payroll_period_id'];
            $info = $this->model_hr_payroll->getPayrollPeriodById($payroll_period_id);

            if ($info) {
                $json['data'] = $info;
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function save() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/payroll')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $payroll_period_id = isset($this->request->post['payroll_period_id']) ? (int)$this->request->post['payroll_period_id'] : 0;

            $data = array(
                'period_name' => $this->request->post['period_name'],
                'start_date'  => $this->request->post['start_date'],
                'end_date'    => $this->request->post['end_date'],
                'status'      => $this->request->post['status']
            );

            if (empty($data['period_name']) || empty($data['start_date']) || empty($data['end_date'])) {
                $json['error'] = $this->language->get('error_required');
            } else {
                if ($payroll_period_id) {
                    $this->model_hr_payroll->editPayrollPeriod($payroll_period_id, $data);
                    $json['success'] = $this->language->get('text_success_edit');
                } else {
                    $this->model_hr_payroll->addPayrollPeriod($data);
                    $json['success'] = $this->language->get('text_success_add');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function delete() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/payroll')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['payroll_period_id'])) {
                $payroll_period_id = (int)$this->request->post['payroll_period_id'];
                $this->model_hr_payroll->deletePayrollPeriod($payroll_period_id);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function entriesList() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $payroll_period_id = isset($this->request->post['payroll_period_id']) ? (int)$this->request->post['payroll_period_id'] : 0;

        $start  = isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0;
        $length = isset($this->request->post['length']) ? (int)$this->request->post['length'] : 10;
        $draw   = isset($this->request->post['draw']) ? (int)$this->request->post['draw'] : 1;
        $order_column = isset($this->request->post['order'][0]['column']) ? (int)$this->request->post['order'][0]['column'] : 0;
        $order_dir = isset($this->request->post['order'][0]['dir']) ? $this->request->post['order'][0]['dir'] : 'asc';

        $columns = array('employee_name','base_salary','allowances','deductions','net_salary','payment_status');
        $order_by = isset($columns[$order_column]) ? $columns[$order_column] : 'employee_name';

        $entries_total = $this->model_hr_payroll->getTotalPayrollEntries($payroll_period_id);
        $entries = $this->model_hr_payroll->getPayrollEntries($payroll_period_id, $start, $length, $order_by, $order_dir);

        $data = array();
        foreach ($entries as $entry) {
            $actions = '';
            if ($this->user->hasPermission('modify', 'hr/payroll') && $entry['payment_status'] == 'pending') {
                $actions .= '<button class="btn btn-success btn-sm btn-mark-paid" data-id="'. $entry['payment_invoice_id'] .'"><i class="fa fa-check"></i> '.$this->language->get('button_mark_paid').'</button>';
            }

            $data[] = array(
                'employee_name' => $entry['employee_name'],
                'base_salary'   => $entry['base_salary'],
                'allowances'    => $entry['allowances'],
                'deductions'    => $entry['deductions'],
                'net_salary'    => $entry['net_salary'],
                'payment_status'=> $entry['payment_status'],
                'actions'       => $actions
            );
        }

        $json = array(
            "draw"            => $draw,
            "recordsTotal"    => $entries_total,
            "recordsFiltered" => $entries_total,
            "data"            => $data
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function markPaid() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/payroll')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['payment_invoice_id'])) {
                $payment_invoice_id = (int)$this->request->post['payment_invoice_id'];
                $this->model_hr_payroll->markEntryPaid($payment_invoice_id);
                $json['success'] = $this->language->get('text_success_mark_paid');
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // إنشاء كشوف الرواتب تلقائياً لجميع الموظفين
    public function generatePayroll() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/payroll')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['payroll_period_id'])) {
                $payroll_period_id = (int)$this->request->post['payroll_period_id'];

                // تحميل إعدادات الضرائب
                $this->model_hr_payroll->loadTaxSettings();

                $generated_count = $this->model_hr_payroll->generatePayrollForPeriod($payroll_period_id);

                if ($generated_count > 0) {
                    $json['success'] = sprintf($this->language->get('text_success_generate'), $generated_count);
                } else {
                    $json['error'] = $this->language->get('error_no_employees');
                }
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // جلب إحصائيات الرواتب
    public function getStatistics() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $json = array();

        if (isset($this->request->post['payroll_period_id'])) {
            $payroll_period_id = (int)$this->request->post['payroll_period_id'];
            $statistics = $this->model_hr_payroll->getPayrollStatistics($payroll_period_id);

            if ($statistics) {
                $json['data'] = $statistics;
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // تصدير البيانات
    public function exportData() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        if (!$this->user->hasPermission('access', 'hr/payroll')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $payroll_period_id = isset($this->request->get['period_id']) ? (int)$this->request->get['period_id'] : 0;
        $format = isset($this->request->get['format']) ? $this->request->get['format'] : 'csv';

        if ($payroll_period_id) {
            $data = $this->model_hr_payroll->exportPayrollData($payroll_period_id, $format);

            if ($data) {
                $period_info = $this->model_hr_payroll->getPayrollPeriodById($payroll_period_id);
                $filename = 'payroll_' . $period_info['period_name'] . '_' . date('Y-m-d');

                if ($format == 'csv') {
                    $this->response->addHeader('Content-Type: text/csv; charset=utf-8');
                    $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                    $this->response->setOutput("\xEF\xBB\xBF" . $data); // UTF-8 BOM for Excel
                } else {
                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode(array('error' => $this->language->get('error_export_failed'))));
                }
            } else {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(array('error' => $this->language->get('error_export_failed'))));
            }
        } else {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array('error' => $this->language->get('error_invalid_request'))));
        }
    }

    // البحث المتقدم
    public function advancedSearch() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $json = array();

        $search_data = array(
            'employee_name' => isset($this->request->post['employee_name']) ? $this->request->post['employee_name'] : '',
            'period_id' => isset($this->request->post['period_id']) ? (int)$this->request->post['period_id'] : 0,
            'payment_status' => isset($this->request->post['payment_status']) ? $this->request->post['payment_status'] : '',
            'min_salary' => isset($this->request->post['min_salary']) ? (float)$this->request->post['min_salary'] : 0,
            'max_salary' => isset($this->request->post['max_salary']) ? (float)$this->request->post['max_salary'] : 0
        );

        $results = $this->model_hr_payroll->searchPayrollEntries($search_data);

        $data = array();
        foreach ($results as $result) {
            $data[] = array(
                'employee_name' => $result['employee_name'],
                'job_title' => $result['job_title'],
                'period_name' => $result['period_name'],
                'base_salary' => number_format($result['base_salary'], 2),
                'allowances' => number_format($result['allowances'], 2),
                'deductions' => number_format($result['deductions'], 2),
                'net_salary' => number_format($result['net_salary'], 2),
                'payment_status' => $result['payment_status']
            );
        }

        $json['data'] = $data;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // تحديث إعدادات الضرائب
    public function updateTaxSettings() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/payroll')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $tax_rate = isset($this->request->post['tax_rate']) ? (float)$this->request->post['tax_rate'] : 0;
            $social_insurance_rate = isset($this->request->post['social_insurance_rate']) ? (float)$this->request->post['social_insurance_rate'] : 0;
            $medical_insurance_rate = isset($this->request->post['medical_insurance_rate']) ? (float)$this->request->post['medical_insurance_rate'] : 0;

            // التحقق من صحة البيانات
            if ($tax_rate < 0 || $tax_rate > 100 || $social_insurance_rate < 0 || $social_insurance_rate > 100 || $medical_insurance_rate < 0 || $medical_insurance_rate > 100) {
                $json['error'] = $this->language->get('error_invalid_tax_rate');
            } else {
                $this->model_hr_payroll->updateTaxSettings($tax_rate / 100, $social_insurance_rate / 100, $medical_insurance_rate / 100);
                $json['success'] = $this->language->get('text_success_settings');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // تحديث حالة دفع متعددة
    public function bulkUpdatePaymentStatus() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/payroll')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $entry_ids = isset($this->request->post['entry_ids']) ? $this->request->post['entry_ids'] : array();
            $status = isset($this->request->post['status']) ? $this->request->post['status'] : '';

            if (!empty($entry_ids) && in_array($status, ['pending', 'paid'])) {
                $updated_count = $this->model_hr_payroll->bulkUpdatePaymentStatus($entry_ids, $status);

                if ($updated_count > 0) {
                    $json['success'] = sprintf($this->language->get('text_success_bulk_update'), $updated_count);
                } else {
                    $json['error'] = $this->language->get('error_not_found');
                }
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // تقرير الرواتب المفصل
    public function detailedReport() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        if (!$this->user->hasPermission('access', 'hr/payroll')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $period_id = isset($this->request->get['period_id']) ? (int)$this->request->get['period_id'] : 0;
        $format = isset($this->request->get['format']) ? $this->request->get['format'] : 'pdf';

        if ($period_id) {
            $report_data = $this->model_hr_payroll->generateDetailedReport($period_id);

            if ($report_data) {
                $period_info = $this->model_hr_payroll->getPayrollPeriodById($period_id);
                $filename = 'detailed_payroll_report_' . $period_info['period_name'] . '_' . date('Y-m-d');

                if ($format == 'pdf') {
                    $this->generatePDFReport($report_data, $filename);
                } elseif ($format == 'excel') {
                    $this->generateExcelReport($report_data, $filename);
                } else {
                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode(array('error' => $this->language->get('error_invalid_format'))));
                }
            } else {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(array('error' => $this->language->get('error_report_failed'))));
            }
        } else {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array('error' => $this->language->get('error_invalid_request'))));
        }
    }

    // إنشاء تقرير PDF
    private function generatePDFReport($data, $filename) {
        require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');

        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

        // إعدادات PDF
        $pdf->SetCreator('AYM ERP');
        $pdf->SetAuthor('AYM ERP');
        $pdf->SetTitle('Detailed Payroll Report');
        $pdf->SetSubject('Payroll Report');

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->AddPage();

        // عنوان التقرير
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 10, 'تقرير كشوف الرواتب المفصل', 0, 1, 'C');
        $pdf->Cell(0, 8, 'فترة: ' . $data['period_info']['period_name'], 0, 1, 'C');
        $pdf->Ln(10);

        // جدول البيانات
        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->Cell(40, 8, 'الموظف', 1, 0, 'C');
        $pdf->Cell(25, 8, 'الراتب الأساسي', 1, 0, 'C');
        $pdf->Cell(25, 8, 'البدلات', 1, 0, 'C');
        $pdf->Cell(25, 8, 'الاستقطاعات', 1, 0, 'C');
        $pdf->Cell(25, 8, 'صافي الراتب', 1, 0, 'C');
        $pdf->Cell(25, 8, 'حالة الدفع', 1, 1, 'C');

        $pdf->SetFont('dejavusans', '', 9);
        foreach ($data['entries'] as $entry) {
            $pdf->Cell(40, 6, $entry['employee_name'], 1, 0, 'L');
            $pdf->Cell(25, 6, number_format($entry['base_salary'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($entry['allowances'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($entry['deductions'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($entry['net_salary'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, $entry['payment_status'], 1, 1, 'C');
        }

        // الإجماليات
        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->Cell(40, 8, 'الإجماليات', 1, 0, 'C');
        $pdf->Cell(25, 8, number_format($data['totals']['total_base'], 2), 1, 0, 'R');
        $pdf->Cell(25, 8, number_format($data['totals']['total_allowances'], 2), 1, 0, 'R');
        $pdf->Cell(25, 8, number_format($data['totals']['total_deductions'], 2), 1, 0, 'R');
        $pdf->Cell(25, 8, number_format($data['totals']['total_net'], 2), 1, 0, 'R');
        $pdf->Cell(25, 8, '', 1, 1, 'C');

        $this->response->addHeader('Content-Type: application/pdf');
        $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        $this->response->setOutput($pdf->Output('', 'S'));
    }

    // إنشاء تقرير Excel
    private function generateExcelReport($data, $filename) {
        // استخدام مكتبة PhpSpreadsheet أو SimpleXLSXGen
        $csv_data = "الموظف,الراتب الأساسي,البدلات,الاستقطاعات,صافي الراتب,حالة الدفع\n";

        foreach ($data['entries'] as $entry) {
            $csv_data .= '"' . $entry['employee_name'] . '",' .
                        $entry['base_salary'] . ',' .
                        $entry['allowances'] . ',' .
                        $entry['deductions'] . ',' .
                        $entry['net_salary'] . ',' .
                        '"' . $entry['payment_status'] . '"' . "\n";
        }

        $this->response->addHeader('Content-Type: text/csv; charset=utf-8');
        $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        $this->response->setOutput("\xEF\xBB\xBF" . $csv_data);
    }

    // تقرير مقارنة الفترات
    public function comparisonReport() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $json = array();

        if (isset($this->request->post['period1_id']) && isset($this->request->post['period2_id'])) {
            $period1_id = (int)$this->request->post['period1_id'];
            $period2_id = (int)$this->request->post['period2_id'];

            $comparison_data = $this->model_hr_payroll->comparePayrollPeriods($period1_id, $period2_id);

            if ($comparison_data) {
                $json['data'] = $comparison_data;
            } else {
                $json['error'] = $this->language->get('error_comparison_failed');
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // تقرير أعلى الرواتب
    public function topEarnersReport() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $json = array();

        $period_id = isset($this->request->post['period_id']) ? (int)$this->request->post['period_id'] : 0;
        $limit = isset($this->request->post['limit']) ? (int)$this->request->post['limit'] : 10;

        if ($period_id) {
            $top_earners = $this->model_hr_payroll->getTopEarners($period_id, $limit);

            if ($top_earners) {
                $json['data'] = $top_earners;
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // تقرير التكلفة الشهرية
    public function monthlyCostReport() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $json = array();

        $year = isset($this->request->post['year']) ? (int)$this->request->post['year'] : date('Y');

        $monthly_costs = $this->model_hr_payroll->getMonthlyCosts($year);

        if ($monthly_costs) {
            $json['data'] = $monthly_costs;
        } else {
            $json['error'] = $this->language->get('error_not_found');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // حساب الراتب يدوياً
    public function calculateSalary() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/payroll')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $employee_id = isset($this->request->post['employee_id']) ? (int)$this->request->post['employee_id'] : 0;
            $period_id = isset($this->request->post['period_id']) ? (int)$this->request->post['period_id'] : 0;
            $custom_data = isset($this->request->post['custom_data']) ? $this->request->post['custom_data'] : array();

            if ($employee_id && $period_id) {
                $calculated_salary = $this->model_hr_payroll->calculateEmployeeSalary($employee_id, $period_id, $custom_data);

                if ($calculated_salary) {
                    $json['data'] = $calculated_salary;
                } else {
                    $json['error'] = $this->language->get('error_calculation_failed');
                }
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // إرسال كشف الراتب بالبريد الإلكتروني
    public function sendPayslipEmail() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');
        $this->load->model('hr/employee');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/payroll')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $entry_id = isset($this->request->post['entry_id']) ? (int)$this->request->post['entry_id'] : 0;

            if ($entry_id) {
                $entry_info = $this->model_hr_payroll->getPayrollEntryById($entry_id);
                $employee_info = $this->model_hr_employee->getEmployee($entry_info['employee_id']);

                if ($entry_info && $employee_info && !empty($employee_info['email'])) {
                    // إنشاء كشف الراتب PDF
                    $payslip_pdf = $this->generatePayslipPDF($entry_info, $employee_info);

                    // إرسال البريد الإلكتروني
                    $mail = new Mail();
                    $mail->protocol = $this->config->get('config_mail_protocol');
                    $mail->parameter = $this->config->get('config_mail_parameter');
                    $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
                    $mail->smtp_username = $this->config->get('config_mail_smtp_username');
                    $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
                    $mail->smtp_port = $this->config->get('config_mail_smtp_port');
                    $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

                    $mail->setTo($employee_info['email']);
                    $mail->setFrom($this->config->get('config_email'));
                    $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
                    $mail->setSubject('كشف الراتب - ' . $entry_info['period_name']);
                    $mail->setText('مرفق كشف الراتب الخاص بك للفترة: ' . $entry_info['period_name']);
                    $mail->addAttachment($payslip_pdf, 'payslip_' . $employee_info['firstname'] . '_' . $employee_info['lastname'] . '.pdf');

                    $mail->send();

                    $json['success'] = $this->language->get('text_success_email_sent');
                } else {
                    $json['error'] = $this->language->get('error_employee_email_not_found');
                }
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // إنشاء كشف راتب PDF
    private function generatePayslipPDF($entry_info, $employee_info) {
        require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetCreator('AYM ERP');
        $pdf->SetAuthor('AYM ERP');
        $pdf->SetTitle('Payslip');
        $pdf->SetSubject('Employee Payslip');

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(TRUE, 20);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->AddPage();

        // عنوان كشف الراتب
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 10, 'كشف الراتب', 0, 1, 'C');
        $pdf->Ln(5);

        // معلومات الموظف
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 8, 'معلومات الموظف', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(50, 6, 'الاسم:', 0, 0, 'L');
        $pdf->Cell(0, 6, $employee_info['firstname'] . ' ' . $employee_info['lastname'], 0, 1, 'L');
        $pdf->Cell(50, 6, 'المسمى الوظيفي:', 0, 0, 'L');
        $pdf->Cell(0, 6, $employee_info['job_title'], 0, 1, 'L');
        $pdf->Cell(50, 6, 'فترة الراتب:', 0, 0, 'L');
        $pdf->Cell(0, 6, $entry_info['period_name'], 0, 1, 'L');
        $pdf->Ln(10);

        // تفاصيل الراتب
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 8, 'تفاصيل الراتب', 0, 1, 'L');

        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->Cell(80, 8, 'البند', 1, 0, 'C');
        $pdf->Cell(40, 8, 'المبلغ', 1, 1, 'C');

        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(80, 6, 'الراتب الأساسي', 1, 0, 'L');
        $pdf->Cell(40, 6, number_format($entry_info['base_salary'], 2), 1, 1, 'R');
        $pdf->Cell(80, 6, 'البدلات', 1, 0, 'L');
        $pdf->Cell(40, 6, number_format($entry_info['allowances'], 2), 1, 1, 'R');
        $pdf->Cell(80, 6, 'الاستقطاعات', 1, 0, 'L');
        $pdf->Cell(40, 6, number_format($entry_info['deductions'], 2), 1, 1, 'R');

        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->Cell(80, 8, 'صافي الراتب', 1, 0, 'C');
        $pdf->Cell(40, 8, number_format($entry_info['net_salary'], 2), 1, 1, 'R');

        return $pdf->Output('', 'S');
    }

    // تحديث سجل راتب موظف
    public function updatePayrollEntry() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/payroll')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $entry_id = isset($this->request->post['entry_id']) ? (int)$this->request->post['entry_id'] : 0;
            $entry_data = isset($this->request->post['entry_data']) ? $this->request->post['entry_data'] : array();

            if ($entry_id && !empty($entry_data)) {
                $updated = $this->model_hr_payroll->updatePayrollEntry($entry_id, $entry_data);

                if ($updated) {
                    $json['success'] = $this->language->get('text_success_update_entry');
                } else {
                    $json['error'] = $this->language->get('error_update_failed');
                }
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // حذف سجل راتب موظف
    public function deletePayrollEntry() {
        $this->load->language('hr/payroll');
        $this->load->model('hr/payroll');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/payroll')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $entry_id = isset($this->request->post['entry_id']) ? (int)$this->request->post['entry_id'] : 0;

            if ($entry_id) {
                $deleted = $this->model_hr_payroll->deletePayrollEntry($entry_id);

                if ($deleted) {
                    $json['success'] = $this->language->get('text_success_delete_entry');
                } else {
                    $json['error'] = $this->language->get('error_delete_failed');
                }
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
