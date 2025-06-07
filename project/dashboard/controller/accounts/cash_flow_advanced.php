<?php
/**
 * تحكم قائمة التدفقات النقدية المتقدمة والمتكاملة
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ControllerAccountsCashFlowAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/cash_flow');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/cash_flow_advanced');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/accounts/cash_flow.css');
        $this->document->addScript('view/javascript/accounts/cash_flow.js');
        $this->document->addScript('view/javascript/jquery/accounting.min.js');
        $this->document->addScript('view/javascript/jquery/select2.min.js');
        $this->document->addStyle('view/javascript/jquery/select2.min.css');
        $this->document->addScript('view/javascript/jquery/daterangepicker.min.js');
        $this->document->addStyle('view/javascript/jquery/daterangepicker.css');
        $this->document->addScript('view/javascript/jquery/chart.min.js');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'cash_flow',
            'record_id' => 0,
            'description' => 'عرض شاشة قائمة التدفقات النقدية',
            'module' => 'cash_flow'
        ]);

        $this->getForm();
    }

    public function generate() {
        $this->load->language('accounts/cash_flow');
        $this->load->model('accounts/cash_flow_advanced');
        $this->load->model('accounts/audit_trail');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $filter_data = $this->prepareFilterData();

                // تسجيل إنشاء قائمة التدفقات النقدية في سجل المراجعة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'generate_cash_flow',
                    'table_name' => 'cash_flow',
                    'record_id' => 0,
                    'description' => 'إنشاء قائمة التدفقات النقدية للفترة: ' . $filter_data['date_start'] . ' إلى ' . $filter_data['date_end'],
                    'module' => 'cash_flow',
                    'business_date' => $filter_data['date_end']
                ]);

                // إنشاء قائمة التدفقات النقدية
                $cash_flow_data = $this->model_accounts_cash_flow_advanced->generateCashFlowStatement($filter_data);

                // التحقق من وجود بيانات
                if (empty($cash_flow_data['operating_activities']) && empty($cash_flow_data['investing_activities']) && empty($cash_flow_data['financing_activities'])) {
                    $this->session->data['warning'] = 'لا توجد تدفقات نقدية في الفترة المحددة';
                }

                $this->session->data['cash_flow_data'] = $cash_flow_data;
                $this->session->data['filter_data'] = $filter_data;

                $this->response->redirect($this->url->link('accounts/cash_flow_advanced/view', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في إنشاء قائمة التدفقات النقدية: ' . $e->getMessage();

                // تسجيل الخطأ
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'generate_cash_flow_failed',
                    'table_name' => 'cash_flow',
                    'record_id' => 0,
                    'description' => 'فشل في إنشاء قائمة التدفقات النقدية: ' . $e->getMessage(),
                    'module' => 'cash_flow'
                ]);
            }
        }

        $this->getForm();
    }

    public function view() {
        $this->load->language('accounts/cash_flow');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_view'));

        if (!isset($this->session->data['cash_flow_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات قائمة تدفقات نقدية للعرض';
            $this->response->redirect($this->url->link('accounts/cash_flow_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->prepareViewData();

        $this->response->setOutput($this->load->view('accounts/cash_flow_view', $data));
    }

    public function export() {
        $this->load->language('accounts/cash_flow');
        $this->load->model('accounts/cash_flow_advanced');
        $this->load->model('accounts/audit_trail');

        if (!isset($this->session->data['cash_flow_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات للتصدير';
            $this->response->redirect($this->url->link('accounts/cash_flow_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $format = $this->request->get['format'] ?? 'excel';
        $cash_flow_data = $this->session->data['cash_flow_data'];
        $filter_data = $this->session->data['filter_data'];

        // تسجيل التصدير
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'export_cash_flow',
            'table_name' => 'cash_flow',
            'record_id' => 0,
            'description' => "تصدير قائمة التدفقات النقدية بصيغة {$format}",
            'module' => 'cash_flow'
        ]);

        switch ($format) {
            case 'excel':
                $this->exportToExcel($cash_flow_data, $filter_data);
                break;
            case 'pdf':
                $this->exportToPdf($cash_flow_data, $filter_data);
                break;
            case 'csv':
                $this->exportToCsv($cash_flow_data, $filter_data);
                break;
            default:
                $this->exportToExcel($cash_flow_data, $filter_data);
        }
    }

    public function print() {
        $this->load->language('accounts/cash_flow');

        if (!isset($this->session->data['cash_flow_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات للطباعة';
            $this->response->redirect($this->url->link('accounts/cash_flow_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->prepareViewData();
        $data['print_mode'] = true;

        $this->response->setOutput($this->load->view('accounts/cash_flow_print', $data));
    }

    public function compareCashFlows() {
        $this->load->language('accounts/cash_flow');
        $this->load->model('accounts/cash_flow_advanced');

        $json = array();

        if (isset($this->request->post['period1']) && isset($this->request->post['period2'])) {
            try {
                $period1 = $this->request->post['period1'];
                $period2 = $this->request->post['period2'];

                $comparison_data = $this->model_accounts_cash_flow_advanced->compareCashFlows($period1, $period2);

                $json['success'] = true;
                $json['data'] = $comparison_data;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'بيانات المقارنة غير مكتملة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getCashFlowRatios() {
        $this->load->model('accounts/cash_flow_advanced');

        $json = array();

        if (isset($this->session->data['cash_flow_data'])) {
            try {
                $cash_flow_data = $this->session->data['cash_flow_data'];

                $ratios = $this->model_accounts_cash_flow_advanced->calculateCashFlowRatios($cash_flow_data);

                $json['success'] = true;
                $json['ratios'] = $ratios;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات قائمة تدفقات نقدية لحساب النسب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getCashFlowAnalysis() {
        $this->load->model('accounts/cash_flow_advanced');

        $json = array();

        if (isset($this->session->data['cash_flow_data'])) {
            try {
                $cash_flow_data = $this->session->data['cash_flow_data'];
                $filter_data = $this->session->data['filter_data'];

                $analysis = $this->model_accounts_cash_flow_advanced->analyzeCashFlow($cash_flow_data, $filter_data);

                $json['success'] = true;
                $json['analysis'] = $analysis;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات قائمة تدفقات نقدية للتحليل';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getCashPositionForecast() {
        $this->load->model('accounts/cash_flow_advanced');

        $json = array();

        if (isset($this->session->data['cash_flow_data'])) {
            try {
                $cash_flow_data = $this->session->data['cash_flow_data'];
                $filter_data = $this->session->data['filter_data'];

                $forecast = $this->model_accounts_cash_flow_advanced->forecastCashPosition($cash_flow_data, $filter_data);

                $json['success'] = true;
                $json['forecast'] = $forecast;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات قائمة تدفقات نقدية للتنبؤ';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getCashFlowTrends() {
        $this->load->model('accounts/cash_flow_advanced');

        $json = array();

        if (isset($this->session->data['cash_flow_data'])) {
            try {
                $cash_flow_data = $this->session->data['cash_flow_data'];
                $filter_data = $this->session->data['filter_data'];

                $trends = $this->model_accounts_cash_flow_advanced->analyzeCashFlowTrends($cash_flow_data, $filter_data);

                $json['success'] = true;
                $json['trends'] = $trends;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات قائمة تدفقات نقدية لتحليل الاتجاهات';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function getForm() {
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/cash_flow_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        // URLs للإجراءات
        $data['action'] = $this->url->link('accounts/cash_flow_advanced/generate', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['compare_url'] = $this->url->link('accounts/cash_flow_advanced/compareCashFlows', 'user_token=' . $this->session->data['user_token'], true);
        $data['ratios_url'] = $this->url->link('accounts/cash_flow_advanced/getCashFlowRatios', 'user_token=' . $this->session->data['user_token'], true);
        $data['analysis_url'] = $this->url->link('accounts/cash_flow_advanced/getCashFlowAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['forecast_url'] = $this->url->link('accounts/cash_flow_advanced/getCashPositionForecast', 'user_token=' . $this->session->data['user_token'], true);
        $data['trends_url'] = $this->url->link('accounts/cash_flow_advanced/getCashFlowTrends', 'user_token=' . $this->session->data['user_token'], true);

        // بيانات النموذج
        $fields = ['date_start', 'date_end', 'method', 'include_zero_flows', 'show_comparative',
                   'comparative_date_start', 'comparative_date_end', 'currency', 'cash_accounts'];

        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } else {
                $default_values = [
                    'date_start' => date('Y-m-01'),
                    'date_end' => date('Y-m-t'),
                    'method' => 'direct',
                    'include_zero_flows' => 0,
                    'show_comparative' => 0,
                    'comparative_date_start' => date('Y-m-01', strtotime('-1 year')),
                    'comparative_date_end' => date('Y-m-t', strtotime('-1 year')),
                    'currency' => $this->config->get('config_currency'),
                    'cash_accounts' => array()
                ];
                $data[$field] = $default_values[$field] ?? '';
            }
        }

        // طرق إعداد قائمة التدفقات النقدية
        $data['methods'] = array(
            'direct' => 'الطريقة المباشرة',
            'indirect' => 'الطريقة غير المباشرة'
        );

        // الحصول على الحسابات النقدية
        $this->load->model('accounts/chart_account');
        $data['cash_accounts_list'] = $this->model_accounts_chart_account->getCashAccounts();

        // العملات المتاحة
        $this->load->model('localisation/currency');
        $data['currencies'] = $this->model_localisation_currency->getCurrencies();

        // الرسائل
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

        if (isset($this->session->data['warning'])) {
            $data['warning'] = $this->session->data['warning'];
            unset($this->session->data['warning']);
        } else {
            $data['warning'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounts/cash_flow_advanced_form', $data));
    }

    protected function prepareFilterData() {
        return array(
            'date_start' => $this->request->post['date_start'],
            'date_end' => $this->request->post['date_end'],
            'method' => $this->request->post['method'] ?? 'direct',
            'include_zero_flows' => isset($this->request->post['include_zero_flows']) ? 1 : 0,
            'show_comparative' => isset($this->request->post['show_comparative']) ? 1 : 0,
            'comparative_date_start' => $this->request->post['comparative_date_start'] ?? '',
            'comparative_date_end' => $this->request->post['comparative_date_end'] ?? '',
            'currency' => $this->request->post['currency'] ?? $this->config->get('config_currency'),
            'cash_accounts' => $this->request->post['cash_accounts'] ?? array()
        );
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('access', 'accounts/cash_flow')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['date_start'])) {
            $this->error['date_start'] = 'تاريخ البداية مطلوب';
        }

        if (empty($this->request->post['date_end'])) {
            $this->error['date_end'] = 'تاريخ النهاية مطلوب';
        }

        if (!empty($this->request->post['date_start']) && !empty($this->request->post['date_end'])) {
            if (strtotime($this->request->post['date_start']) > strtotime($this->request->post['date_end'])) {
                $this->error['date_range'] = 'تاريخ البداية يجب أن يكون قبل تاريخ النهاية';
            }
        }

        if (isset($this->request->post['show_comparative']) && $this->request->post['show_comparative']) {
            if (empty($this->request->post['comparative_date_start'])) {
                $this->error['comparative_date_start'] = 'تاريخ بداية المقارنة مطلوب';
            }
            if (empty($this->request->post['comparative_date_end'])) {
                $this->error['comparative_date_end'] = 'تاريخ نهاية المقارنة مطلوب';
            }
        }

        return !$this->error;
    }

    protected function prepareViewData() {
        $data = array();

        $data['cash_flow_data'] = $this->session->data['cash_flow_data'];
        $data['filter_data'] = $this->session->data['filter_data'];

        // معلومات الشركة
        $data['company_name'] = $this->config->get('config_name');
        $data['company_address'] = $this->config->get('config_address');

        // معلومات التقرير
        $data['report_title'] = $this->language->get('heading_title');
        $data['report_date'] = date($this->language->get('date_format_long'));
        $data['generated_by'] = $this->user->getUserName();

        // URLs
        $data['export_excel'] = $this->url->link('accounts/cash_flow_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=excel', true);
        $data['export_pdf'] = $this->url->link('accounts/cash_flow_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=pdf', true);
        $data['print_url'] = $this->url->link('accounts/cash_flow_advanced/print', 'user_token=' . $this->session->data['user_token'], true);
        $data['back_url'] = $this->url->link('accounts/cash_flow_advanced', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['ratios_url'] = $this->url->link('accounts/cash_flow_advanced/getCashFlowRatios', 'user_token=' . $this->session->data['user_token'], true);
        $data['analysis_url'] = $this->url->link('accounts/cash_flow_advanced/getCashFlowAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['forecast_url'] = $this->url->link('accounts/cash_flow_advanced/getCashPositionForecast', 'user_token=' . $this->session->data['user_token'], true);
        $data['trends_url'] = $this->url->link('accounts/cash_flow_advanced/getCashFlowTrends', 'user_token=' . $this->session->data['user_token'], true);

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/cash_flow_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        return $data;
    }
}