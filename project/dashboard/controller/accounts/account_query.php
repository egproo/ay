<?php
class ControllerAccountsAccountQuery extends Controller {

    public function index() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');
        $this->load->model('accounts/chart_account');

        $this->document->setTitle($this->language->get('heading_title'));

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/account_query', 'user_token=' . $this->session->data['user_token'], true)
        );

        // روابط Ajax
        $data['ajax_query_url'] = $this->url->link('accounts/account_query/query', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_balance_history_url'] = $this->url->link('accounts/account_query/balanceHistory', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_transactions_url'] = $this->url->link('accounts/account_query/transactions', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_export_url'] = $this->url->link('accounts/account_query/export', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_accounts_list_url'] = $this->url->link('accounts/account_query/accountsList', 'user_token=' . $this->session->data['user_token'], true);

        // جلب قائمة الحسابات للاختيار
        $data['accounts'] = $this->model_accounts_chart_account->getAccounts();

        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounts/account_query', $data));
    }

    // استعلام رصيد الحساب
    public function query() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        $json = array();

        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $account_id = isset($this->request->post['account_id']) ? (int)$this->request->post['account_id'] : 0;
            $date_from = isset($this->request->post['date_from']) ? $this->request->post['date_from'] : '';
            $date_to = isset($this->request->post['date_to']) ? $this->request->post['date_to'] : '';

            if (!$account_id) {
                $json['error'] = $this->language->get('error_account_required');
            } else {
                try {
                    // جلب معلومات الحساب
                    $account_info = $this->model_accounts_account_query->getAccountInfo($account_id);

                    if (!$account_info) {
                        $json['error'] = $this->language->get('error_account_not_found');
                    } else {
                        // حساب الرصيد
                        $balance_data = $this->model_accounts_account_query->calculateAccountBalance($account_id, $date_from, $date_to);

                        // جلب آخر المعاملات
                        $recent_transactions = $this->model_accounts_account_query->getRecentTransactions($account_id, 10);

                        // إحصائيات إضافية
                        $statistics = $this->model_accounts_account_query->getAccountStatistics($account_id, $date_from, $date_to);

                        $json['success'] = true;
                        $json['data'] = array(
                            'account_info' => $account_info,
                            'balance_data' => $balance_data,
                            'recent_transactions' => $recent_transactions,
                            'statistics' => $statistics
                        );
                    }
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_query_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // جلب تاريخ الأرصدة
    public function balanceHistory() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        $json = array();

        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $account_id = isset($this->request->post['account_id']) ? (int)$this->request->post['account_id'] : 0;
            $period = isset($this->request->post['period']) ? $this->request->post['period'] : 'month';

            if (!$account_id) {
                $json['error'] = $this->language->get('error_account_required');
            } else {
                try {
                    $history = $this->model_accounts_account_query->getBalanceHistory($account_id, $period);
                    $json['success'] = true;
                    $json['data'] = $history;
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_query_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // جلب المعاملات المفصلة
    public function transactions() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        $json = array();

        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $account_id = isset($this->request->post['account_id']) ? (int)$this->request->post['account_id'] : 0;
            $date_from = isset($this->request->post['date_from']) ? $this->request->post['date_from'] : '';
            $date_to = isset($this->request->post['date_to']) ? $this->request->post['date_to'] : '';
            $start = isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0;
            $length = isset($this->request->post['length']) ? (int)$this->request->post['length'] : 25;

            if (!$account_id) {
                $json['error'] = $this->language->get('error_account_required');
            } else {
                try {
                    $filter_data = array(
                        'account_id' => $account_id,
                        'date_from' => $date_from,
                        'date_to' => $date_to,
                        'start' => $start,
                        'limit' => $length
                    );

                    $transactions = $this->model_accounts_account_query->getTransactions($filter_data);
                    $total = $this->model_accounts_account_query->getTotalTransactions($filter_data);

                    $data = array();
                    foreach ($transactions as $transaction) {
                        $data[] = array(
                            'date' => date('Y-m-d', strtotime($transaction['date'])),
                            'reference' => $transaction['reference'],
                            'description' => $transaction['description'],
                            'debit' => number_format($transaction['debit'], 2),
                            'credit' => number_format($transaction['credit'], 2),
                            'balance' => number_format($transaction['running_balance'], 2),
                            'source' => $transaction['source_type']
                        );
                    }

                    $json['draw'] = isset($this->request->post['draw']) ? (int)$this->request->post['draw'] : 1;
                    $json['recordsTotal'] = $total;
                    $json['recordsFiltered'] = $total;
                    $json['data'] = $data;
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_query_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // تصدير البيانات
    public function export() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $account_id = isset($this->request->get['account_id']) ? (int)$this->request->get['account_id'] : 0;
        $date_from = isset($this->request->get['date_from']) ? $this->request->get['date_from'] : '';
        $date_to = isset($this->request->get['date_to']) ? $this->request->get['date_to'] : '';
        $format = isset($this->request->get['format']) ? $this->request->get['format'] : 'csv';

        if (!$account_id) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array('error' => $this->language->get('error_account_required'))));
            return;
        }

        try {
            $account_info = $this->model_accounts_account_query->getAccountInfo($account_id);
            $transactions = $this->model_accounts_account_query->getAllTransactions($account_id, $date_from, $date_to);

            if ($format == 'csv') {
                $filename = 'account_statement_' . $account_info['account_code'] . '_' . date('Y-m-d') . '.csv';

                $this->response->addHeader('Content-Type: text/csv; charset=utf-8');
                $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');

                $csv_data = $this->model_accounts_account_query->generateCSV($account_info, $transactions);
                $this->response->setOutput("\xEF\xBB\xBF" . $csv_data); // UTF-8 BOM
            } else {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(array('error' => $this->language->get('error_invalid_format'))));
            }
        } catch (Exception $e) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array('error' => $this->language->get('error_export_failed') . ': ' . $e->getMessage())));
        }
    }

    // جلب قائمة الحسابات للبحث
    public function accountsList() {
        $this->load->model('accounts/chart_account');

        $json = array();

        $search = isset($this->request->get['search']) ? $this->request->get['search'] : '';

        $filter_data = array(
            'filter_search' => $search,
            'start' => 0,
            'limit' => 20
        );

        $accounts = $this->model_accounts_chart_account->getAccounts($filter_data);

        foreach ($accounts as $account) {
            $json[] = array(
                'id' => $account['account_id'],
                'text' => $account['account_code'] . ' - ' . $account['account_name'],
                'code' => $account['account_code'],
                'name' => $account['account_name'],
                'type' => $account['account_type']
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // البحث المتقدم
    public function advancedSearch() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        $json = array();

        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $filter_data = array(
                'account_id' => isset($this->request->post['account_id']) ? (int)$this->request->post['account_id'] : 0,
                'date_from' => isset($this->request->post['date_from']) ? $this->request->post['date_from'] : '',
                'date_to' => isset($this->request->post['date_to']) ? $this->request->post['date_to'] : '',
                'reference' => isset($this->request->post['reference']) ? $this->request->post['reference'] : '',
                'description' => isset($this->request->post['description']) ? $this->request->post['description'] : '',
                'min_amount' => isset($this->request->post['min_amount']) ? (float)$this->request->post['min_amount'] : 0,
                'max_amount' => isset($this->request->post['max_amount']) ? (float)$this->request->post['max_amount'] : 0,
                'source_type' => isset($this->request->post['source_type']) ? $this->request->post['source_type'] : '',
                'start' => isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0,
                'limit' => isset($this->request->post['limit']) ? (int)$this->request->post['limit'] : 25
            );

            try {
                $results = $this->model_accounts_account_query->advancedSearch($filter_data);

                $data = array();
                foreach ($results as $result) {
                    $data[] = array(
                        'date' => date('Y-m-d', strtotime($result['date'])),
                        'reference' => $result['reference'],
                        'description' => $result['description'],
                        'account' => $result['account_code'] . ' - ' . $result['account_name'],
                        'debit' => number_format($result['debit'], 2),
                        'credit' => number_format($result['credit'], 2),
                        'source_type' => $result['source_type']
                    );
                }

                $json['success'] = true;
                $json['data'] = $data;
            } catch (Exception $e) {
                $json['error'] = $this->language->get('error_query_failed') . ': ' . $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // مقارنة الحسابات
    public function compareAccounts() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        $json = array();

        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $account_ids = isset($this->request->post['account_ids']) ? $this->request->post['account_ids'] : array();
            $date_from = isset($this->request->post['date_from']) ? $this->request->post['date_from'] : '';
            $date_to = isset($this->request->post['date_to']) ? $this->request->post['date_to'] : '';

            if (empty($account_ids) || count($account_ids) < 2) {
                $json['error'] = $this->language->get('error_accounts_required');
            } else {
                try {
                    $comparison_data = $this->model_accounts_account_query->compareAccounts($account_ids, $date_from, $date_to);
                    $json['success'] = true;
                    $json['data'] = $comparison_data;
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_query_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // تحليل الاتجاه
    public function trendAnalysis() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        $json = array();

        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $account_id = isset($this->request->post['account_id']) ? (int)$this->request->post['account_id'] : 0;
            $periods = isset($this->request->post['periods']) ? (int)$this->request->post['periods'] : 12;

            if (!$account_id) {
                $json['error'] = $this->language->get('error_account_required');
            } else {
                try {
                    $trend_data = $this->model_accounts_account_query->analyzeTrend($account_id, $periods);
                    $json['success'] = true;
                    $json['data'] = $trend_data;
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_query_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // تحليل النشاط
    public function activityAnalysis() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        $json = array();

        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $account_id = isset($this->request->post['account_id']) ? (int)$this->request->post['account_id'] : 0;
            $date_from = isset($this->request->post['date_from']) ? $this->request->post['date_from'] : '';
            $date_to = isset($this->request->post['date_to']) ? $this->request->post['date_to'] : '';

            if (!$account_id) {
                $json['error'] = $this->language->get('error_account_required');
            } else {
                try {
                    $activity_data = $this->model_accounts_account_query->analyzeActivity($account_id, $date_from, $date_to);
                    $json['success'] = true;
                    $json['data'] = $activity_data;
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_query_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // إنشاء تقرير شامل
    public function comprehensiveReport() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        $json = array();

        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $account_id = isset($this->request->post['account_id']) ? (int)$this->request->post['account_id'] : 0;
            $date_from = isset($this->request->post['date_from']) ? $this->request->post['date_from'] : '';
            $date_to = isset($this->request->post['date_to']) ? $this->request->post['date_to'] : '';

            if (!$account_id) {
                $json['error'] = $this->language->get('error_account_required');
            } else {
                try {
                    $report_data = $this->model_accounts_account_query->generateComprehensiveReport($account_id, $date_from, $date_to);
                    $json['success'] = true;
                    $json['data'] = $report_data;
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_query_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // حفظ الاستعلام المفضل
    public function saveFavorite() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        $json = array();

        if (!$this->user->hasPermission('modify', 'accounts/account_query')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $query_name = isset($this->request->post['query_name']) ? $this->request->post['query_name'] : '';
            $query_data = isset($this->request->post['query_data']) ? $this->request->post['query_data'] : array();

            if (!$query_name) {
                $json['error'] = $this->language->get('error_query_name_required');
            } else {
                try {
                    $favorite_id = $this->model_accounts_account_query->saveFavoriteQuery($this->user->getId(), $query_name, $query_data);
                    $json['success'] = $this->language->get('text_success_save_favorite');
                    $json['favorite_id'] = $favorite_id;
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_save_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // جلب الاستعلامات المفضلة
    public function getFavorites() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        $json = array();

        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            try {
                $favorites = $this->model_accounts_account_query->getFavoriteQueries($this->user->getId());
                $json['success'] = true;
                $json['data'] = $favorites;
            } catch (Exception $e) {
                $json['error'] = $this->language->get('error_query_failed') . ': ' . $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // تقرير شامل متقدم
    public function advancedReport() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        $json = array();

        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $account_id = isset($this->request->post['account_id']) ? (int)$this->request->post['account_id'] : 0;
            $date_from = isset($this->request->post['date_from']) ? $this->request->post['date_from'] : '';
            $date_to = isset($this->request->post['date_to']) ? $this->request->post['date_to'] : '';
            $format = isset($this->request->post['format']) ? $this->request->post['format'] : 'json';

            if (!$account_id) {
                $json['error'] = $this->language->get('error_account_required');
            } else {
                try {
                    $report_data = $this->model_accounts_account_query->generateAdvancedReport($account_id, $date_from, $date_to);

                    if ($format == 'pdf') {
                        $this->generateAdvancedPDFReport($report_data);
                        return;
                    } elseif ($format == 'excel') {
                        $this->generateAdvancedExcelReport($report_data);
                        return;
                    } else {
                        $json['success'] = true;
                        $json['data'] = $report_data;
                    }
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_query_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // إنشاء تقرير PDF متقدم
    private function generateAdvancedPDFReport($data) {
        require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetCreator('AYM ERP');
        $pdf->SetAuthor('AYM ERP');
        $pdf->SetTitle('Advanced Account Query Report');
        $pdf->SetSubject('Account Analysis Report');

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->AddPage();

        // عنوان التقرير
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 10, 'تقرير استعلام الحسابات المتقدم', 0, 1, 'C');
        $pdf->Ln(5);

        // معلومات الحساب
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 8, 'معلومات الحساب', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(50, 6, 'رمز الحساب:', 0, 0, 'L');
        $pdf->Cell(0, 6, $data['account_info']['account_code'], 0, 1, 'L');
        $pdf->Cell(50, 6, 'اسم الحساب:', 0, 0, 'L');
        $pdf->Cell(0, 6, $data['account_info']['account_name'], 0, 1, 'L');
        $pdf->Cell(50, 6, 'نوع الحساب:', 0, 0, 'L');
        $pdf->Cell(0, 6, $data['account_info']['account_type'], 0, 1, 'L');
        $pdf->Ln(10);

        // ملخص الأرصدة
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 8, 'ملخص الأرصدة', 0, 1, 'L');

        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->Cell(40, 8, 'البند', 1, 0, 'C');
        $pdf->Cell(40, 8, 'المبلغ', 1, 1, 'C');

        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(40, 6, 'الرصيد الافتتاحي', 1, 0, 'L');
        $pdf->Cell(40, 6, number_format($data['balance_data']['opening_balance'], 2), 1, 1, 'R');
        $pdf->Cell(40, 6, 'إجمالي المدين', 1, 0, 'L');
        $pdf->Cell(40, 6, number_format($data['balance_data']['period_debit'], 2), 1, 1, 'R');
        $pdf->Cell(40, 6, 'إجمالي الدائن', 1, 0, 'L');
        $pdf->Cell(40, 6, number_format($data['balance_data']['period_credit'], 2), 1, 1, 'R');
        $pdf->Cell(40, 6, 'الرصيد الختامي', 1, 0, 'L');
        $pdf->Cell(40, 6, number_format($data['balance_data']['closing_balance'], 2), 1, 1, 'R');

        $filename = 'advanced_account_report_' . $data['account_info']['account_code'] . '_' . date('Y-m-d') . '.pdf';

        $this->response->addHeader('Content-Type: application/pdf');
        $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
        $this->response->setOutput($pdf->Output('', 'S'));
    }

    // إنشاء تقرير Excel متقدم
    private function generateAdvancedExcelReport($data) {
        $csv_data = "تقرير استعلام الحسابات المتقدم\n";
        $csv_data .= "رمز الحساب," . $data['account_info']['account_code'] . "\n";
        $csv_data .= "اسم الحساب," . $data['account_info']['account_name'] . "\n";
        $csv_data .= "نوع الحساب," . $data['account_info']['account_type'] . "\n\n";

        $csv_data .= "ملخص الأرصدة\n";
        $csv_data .= "البند,المبلغ\n";
        $csv_data .= "الرصيد الافتتاحي," . $data['balance_data']['opening_balance'] . "\n";
        $csv_data .= "إجمالي المدين," . $data['balance_data']['period_debit'] . "\n";
        $csv_data .= "إجمالي الدائن," . $data['balance_data']['period_credit'] . "\n";
        $csv_data .= "الرصيد الختامي," . $data['balance_data']['closing_balance'] . "\n\n";

        if (isset($data['transactions']) && !empty($data['transactions'])) {
            $csv_data .= "المعاملات\n";
            $csv_data .= "التاريخ,المرجع,البيان,مدين,دائن,الرصيد\n";

            foreach ($data['transactions'] as $transaction) {
                $csv_data .= $transaction['date'] . ',';
                $csv_data .= '"' . $transaction['reference'] . '",';
                $csv_data .= '"' . $transaction['description'] . '",';
                $csv_data .= $transaction['debit'] . ',';
                $csv_data .= $transaction['credit'] . ',';
                $csv_data .= $transaction['running_balance'] . "\n";
            }
        }

        $filename = 'advanced_account_report_' . $data['account_info']['account_code'] . '_' . date('Y-m-d') . '.csv';

        $this->response->addHeader('Content-Type: text/csv; charset=utf-8');
        $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
        $this->response->setOutput("\xEF\xBB\xBF" . $csv_data);
    }

    // تحليل الأداء المالي
    public function performanceAnalysis() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        $json = array();

        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $account_id = isset($this->request->post['account_id']) ? (int)$this->request->post['account_id'] : 0;
            $date_from = isset($this->request->post['date_from']) ? $this->request->post['date_from'] : '';
            $date_to = isset($this->request->post['date_to']) ? $this->request->post['date_to'] : '';

            if (!$account_id) {
                $json['error'] = $this->language->get('error_account_required');
            } else {
                try {
                    $performance_data = $this->model_accounts_account_query->analyzePerformance($account_id, $date_from, $date_to);
                    $json['success'] = true;
                    $json['data'] = $performance_data;
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_query_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // تحليل المخاطر
    public function riskAnalysis() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        $json = array();

        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $account_id = isset($this->request->post['account_id']) ? (int)$this->request->post['account_id'] : 0;
            $periods = isset($this->request->post['periods']) ? (int)$this->request->post['periods'] : 12;

            if (!$account_id) {
                $json['error'] = $this->language->get('error_account_required');
            } else {
                try {
                    $risk_data = $this->model_accounts_account_query->analyzeRisk($account_id, $periods);
                    $json['success'] = true;
                    $json['data'] = $risk_data;
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_query_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // تحليل الموسمية
    public function seasonalityAnalysis() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        $json = array();

        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $account_id = isset($this->request->post['account_id']) ? (int)$this->request->post['account_id'] : 0;
            $years = isset($this->request->post['years']) ? (int)$this->request->post['years'] : 3;

            if (!$account_id) {
                $json['error'] = $this->language->get('error_account_required');
            } else {
                try {
                    $seasonality_data = $this->model_accounts_account_query->analyzeSeasonality($account_id, $years);
                    $json['success'] = true;
                    $json['data'] = $seasonality_data;
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_query_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // حذف الاستعلام المفضل
    public function deleteFavorite() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        $json = array();

        if (!$this->user->hasPermission('modify', 'accounts/account_query')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $favorite_id = isset($this->request->post['favorite_id']) ? (int)$this->request->post['favorite_id'] : 0;

            if (!$favorite_id) {
                $json['error'] = $this->language->get('error_favorite_id_required');
            } else {
                try {
                    $deleted = $this->model_accounts_account_query->deleteFavoriteQuery($favorite_id, $this->user->getId());

                    if ($deleted) {
                        $json['success'] = $this->language->get('text_success_delete_favorite');
                    } else {
                        $json['error'] = $this->language->get('error_delete_failed');
                    }
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_delete_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // تحميل الاستعلام المفضل
    public function loadFavorite() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        $json = array();

        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $favorite_id = isset($this->request->post['favorite_id']) ? (int)$this->request->post['favorite_id'] : 0;

            if (!$favorite_id) {
                $json['error'] = $this->language->get('error_favorite_id_required');
            } else {
                try {
                    $favorite_data = $this->model_accounts_account_query->getFavoriteQuery($favorite_id, $this->user->getId());

                    if ($favorite_data) {
                        $json['success'] = true;
                        $json['data'] = $favorite_data;
                    } else {
                        $json['error'] = $this->language->get('error_favorite_not_found');
                    }
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_query_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // تصدير متقدم بخيارات متعددة
    public function advancedExport() {
        $this->load->language('accounts/account_query');
        $this->load->model('accounts/account_query');

        if (!$this->user->hasPermission('access', 'accounts/account_query')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $account_id = isset($this->request->get['account_id']) ? (int)$this->request->get['account_id'] : 0;
        $date_from = isset($this->request->get['date_from']) ? $this->request->get['date_from'] : '';
        $date_to = isset($this->request->get['date_to']) ? $this->request->get['date_to'] : '';
        $format = isset($this->request->get['format']) ? $this->request->get['format'] : 'csv';
        $include_summary = isset($this->request->get['include_summary']) ? (bool)$this->request->get['include_summary'] : true;
        $include_statistics = isset($this->request->get['include_statistics']) ? (bool)$this->request->get['include_statistics'] : false;

        if (!$account_id) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array('error' => $this->language->get('error_account_required'))));
            return;
        }

        try {
            $export_data = $this->model_accounts_account_query->generateExportData($account_id, $date_from, $date_to, $include_summary, $include_statistics);

            if ($format == 'json') {
                $filename = 'account_export_' . $export_data['account_info']['account_code'] . '_' . date('Y-m-d') . '.json';

                $this->response->addHeader('Content-Type: application/json');
                $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
                $this->response->setOutput(json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            } elseif ($format == 'xml') {
                $filename = 'account_export_' . $export_data['account_info']['account_code'] . '_' . date('Y-m-d') . '.xml';

                $xml_data = $this->model_accounts_account_query->generateXML($export_data);

                $this->response->addHeader('Content-Type: application/xml');
                $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
                $this->response->setOutput($xml_data);

            } else {
                // Default to CSV
                $filename = 'account_export_' . $export_data['account_info']['account_code'] . '_' . date('Y-m-d') . '.csv';

                $csv_data = $this->model_accounts_account_query->generateAdvancedCSV($export_data);

                $this->response->addHeader('Content-Type: text/csv; charset=utf-8');
                $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
                $this->response->setOutput("\xEF\xBB\xBF" . $csv_data);
            }

        } catch (Exception $e) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array('error' => $this->language->get('error_export_failed') . ': ' . $e->getMessage())));
        }
    }
}
