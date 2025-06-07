<?php
/**
 * تحكم القيود المحاسبية المتقدم والمتكامل
 * يدعم إنشاء وتعديل وطباعة وتصدير القيود مع التحقق المتقدم
 */
class ControllerAccountsJournalEntry extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/journal_entry');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/journal_entry');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/accounts/journal_entry.css');
        $this->document->addScript('view/javascript/accounts/journal_entry.js');
        $this->document->addScript('view/javascript/jquery/accounting.min.js');
        $this->document->addScript('view/javascript/jquery/select2.min.js');
        $this->document->addStyle('view/javascript/jquery/select2.min.css');

        $this->getList();
    }

    public function add() {
        $this->load->language('accounts/journal_entry');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_add'));
        $this->load->model('accounts/journal_entry');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $journal_id = $this->model_accounts_journal_entry->addJournalEntry($this->request->post);

                $this->session->data['success'] = $this->language->get('text_success_add');

                // إعادة توجيه حسب الإجراء المطلوب
                if (isset($this->request->post['save_and_new'])) {
                    $this->response->redirect($this->url->link('accounts/journal_entry/add', 'user_token=' . $this->session->data['user_token'], true));
                } elseif (isset($this->request->post['save_and_print'])) {
                    $this->response->redirect($this->url->link('accounts/journal_entry/print', 'user_token=' . $this->session->data['user_token'] . '&journal_id=' . $journal_id, true));
                } else {
                    $this->response->redirect($this->url->link('accounts/journal_entry', 'user_token=' . $this->session->data['user_token'], true));
                }
            } catch (Exception $e) {
                $this->error['warning'] = $e->getMessage();
            }
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('accounts/journal_entry');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_edit'));
        $this->load->model('accounts/journal_entry');
        $this->load->controller('accounts/journal_permissions');
        $this->load->model('accounts/audit_trail');

        $journal_id = $this->request->get['journal_id'];
        $journal_data = $this->model_accounts_journal_entry->getJournalEntry($journal_id);

        if (!$journal_data) {
            $this->session->data['error'] = 'القيد غير موجود';
            $this->response->redirect($this->url->link('accounts/journal_entry', 'user_token=' . $this->session->data['user_token'], true));
        }

        // التحقق من صلاحية التعديل
        $permission_check = $this->controller_accounts_journal_permissions->canEditJournal($journal_id, $journal_data);

        if (!$permission_check['allowed']) {
            $this->session->data['error'] = $permission_check['reason'];
            $this->response->redirect($this->url->link('accounts/journal_entry', 'user_token=' . $this->session->data['user_token'], true));
        }

        // عرض تحذيرات إذا وجدت
        if (!empty($permission_check['restrictions'])) {
            $this->session->data['warning'] = implode('<br>', $permission_check['restrictions']);
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                // حفظ البيانات القديمة لسجل المراجعة
                $old_data = $journal_data;

                $this->model_accounts_journal_entry->editJournalEntry($journal_id, $this->request->post);

                // تسجيل في سجل المراجعة
                $this->model_accounts_audit_trail->logJournalChange(
                    $journal_id,
                    'update',
                    $old_data,
                    $this->request->post
                );

                $this->session->data['success'] = $this->language->get('text_success_edit');

                // إعادة توجيه حسب الإجراء المطلوب
                if (isset($this->request->post['save_and_new'])) {
                    $this->response->redirect($this->url->link('accounts/journal_entry/add', 'user_token=' . $this->session->data['user_token'], true));
                } elseif (isset($this->request->post['save_and_print'])) {
                    $this->response->redirect($this->url->link('accounts/journal_entry/print', 'user_token=' . $this->session->data['user_token'] . '&journal_id=' . $journal_id, true));
                } else {
                    $this->response->redirect($this->url->link('accounts/journal_entry', 'user_token=' . $this->session->data['user_token'], true));
                }
            } catch (Exception $e) {
                $this->error['warning'] = $e->getMessage();

                // تسجيل محاولة التعديل الفاشلة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'update_failed',
                    'table_name' => 'journal_entry',
                    'record_id' => $journal_id,
                    'description' => 'فشل في تعديل القيد: ' . $e->getMessage(),
                    'transaction_amount' => $journal_data['total_debit']
                ]);
            }
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('accounts/journal_entry');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/journal_entry');
        $this->load->controller('accounts/journal_permissions');
        $this->load->model('accounts/audit_trail');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            $deleted_count = 0;
            $failed_deletions = [];

            foreach ($this->request->post['selected'] as $journal_id) {
                try {
                    // الحصول على بيانات القيد قبل الحذف
                    $journal_data = $this->model_accounts_journal_entry->getJournalEntry($journal_id);

                    if (!$journal_data) {
                        $failed_deletions[] = "القيد رقم {$journal_id} غير موجود";
                        continue;
                    }

                    // التحقق من صلاحية الحذف
                    $permission_check = $this->controller_accounts_journal_permissions->canDeleteJournal($journal_id, $journal_data);

                    if (!$permission_check['allowed']) {
                        $failed_deletions[] = "القيد رقم {$journal_id}: " . $permission_check['reason'];
                        continue;
                    }

                    // طلب موافقة إضافية للقيود الكبيرة أو القديمة
                    if ($permission_check['requires_approval']) {
                        if (!$this->hasDeleteApproval($journal_id)) {
                            $failed_deletions[] = "القيد رقم {$journal_id}: يتطلب موافقة إضافية للحذف";
                            continue;
                        }
                    }

                    // تسجيل في سجل المراجعة قبل الحذف
                    $this->model_accounts_audit_trail->logJournalChange(
                        $journal_id,
                        'delete',
                        $journal_data,
                        null
                    );

                    // حذف القيد
                    $this->model_accounts_journal_entry->deleteJournalEntry($journal_id);
                    $deleted_count++;

                } catch (Exception $e) {
                    $failed_deletions[] = "القيد رقم {$journal_id}: " . $e->getMessage();

                    // تسجيل محاولة الحذف الفاشلة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'delete_failed',
                        'table_name' => 'journal_entry',
                        'record_id' => $journal_id,
                        'description' => 'فشل في حذف القيد: ' . $e->getMessage(),
                        'transaction_amount' => $journal_data['total_debit'] ?? 0
                    ]);
                }
            }

            // إعداد رسائل النتيجة
            if ($deleted_count > 0) {
                $this->session->data['success'] = "تم حذف {$deleted_count} قيد بنجاح";
            }

            if (!empty($failed_deletions)) {
                $this->session->data['error'] = "فشل في حذف بعض القيود:\n" . implode("\n", $failed_deletions);
            }

            $this->response->redirect($this->url->link('accounts/journal_entry', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getList();
    }

    public function post() {
        $this->load->language('accounts/journal_entry');
        $this->load->model('accounts/journal_entry');
        $this->load->controller('accounts/journal_permissions');
        $this->load->model('accounts/audit_trail');

        $json = array();

        if (!$this->user->hasPermission('modify', 'accounts/journal_entry')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['journal_id'])) {
                $journal_id = $this->request->post['journal_id'];

                try {
                    // الحصول على بيانات القيد
                    $journal_data = $this->model_accounts_journal_entry->getJournalEntry($journal_id);

                    if (!$journal_data) {
                        $json['error'] = 'القيد غير موجود';
                    } else {
                        // التحقق من صلاحية الترحيل
                        $permission_check = $this->controller_accounts_journal_permissions->canPostJournal($journal_id, $journal_data);

                        if (!$permission_check['allowed']) {
                            $json['error'] = $permission_check['reason'];
                        } else {
                            // عرض تحذيرات إن وجدت
                            if (!empty($permission_check['warnings'])) {
                                $json['warnings'] = $permission_check['warnings'];
                            }

                            // حفظ البيانات القديمة لسجل المراجعة
                            $old_data = $journal_data;

                            // ترحيل القيد
                            $this->model_accounts_journal_entry->postJournalEntry($journal_id);

                            // تسجيل في سجل المراجعة
                            $new_data = $this->model_accounts_journal_entry->getJournalEntry($journal_id);
                            $this->model_accounts_audit_trail->logJournalChange(
                                $journal_id,
                                'post',
                                $old_data,
                                $new_data
                            );

                            $json['success'] = $this->language->get('text_success_post');

                            // إرسال إشعار للمدير المالي للمبالغ الكبيرة
                            if ($journal_data['total_debit'] > 100000) {
                                $this->sendLargeAmountNotification($journal_id, $journal_data);
                            }
                        }
                    }
                } catch (Exception $e) {
                    $json['error'] = $e->getMessage();

                    // تسجيل محاولة الترحيل الفاشلة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'post_failed',
                        'table_name' => 'journal_entry',
                        'record_id' => $journal_id,
                        'description' => 'فشل في ترحيل القيد: ' . $e->getMessage(),
                        'transaction_amount' => $journal_data['total_debit'] ?? 0
                    ]);
                }
            } else {
                $json['error'] = $this->language->get('error_journal_id');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function unpost() {
        $this->load->language('accounts/journal_entry');
        $this->load->model('accounts/journal_entry');

        $json = array();

        if (!$this->user->hasPermission('modify', 'accounts/journal_entry')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['journal_id'])) {
                try {
                    $this->model_accounts_journal_entry->unpostJournalEntry($this->request->post['journal_id']);
                    $json['success'] = $this->language->get('text_success_unpost');
                } catch (Exception $e) {
                    $json['error'] = $e->getMessage();
                }
            } else {
                $json['error'] = $this->language->get('error_journal_id');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function duplicate() {
        $this->load->language('accounts/journal_entry');
        $this->load->model('accounts/journal_entry');

        if (isset($this->request->get['journal_id']) && $this->validateDuplicate()) {
            $journal = $this->model_accounts_journal_entry->getJournalEntry($this->request->get['journal_id']);

            if ($journal) {
                // إعداد بيانات القيد المكرر
                $duplicate_data = array(
                    'journal_date' => date('Y-m-d'),
                    'description' => $journal['description'] . ' (نسخة)',
                    'reference_type' => $journal['reference_type'],
                    'reference_number' => '',
                    'status' => 'draft',
                    'lines' => array()
                );

                // نسخ البنود
                foreach ($journal['lines'] as $line) {
                    $duplicate_data['lines'][] = array(
                        'account_id' => $line['account_id'],
                        'debit_amount' => $line['debit_amount'],
                        'credit_amount' => $line['credit_amount'],
                        'description' => $line['description']
                    );
                }

                try {
                    $new_journal_id = $this->model_accounts_journal_entry->addJournalEntry($duplicate_data);
                    $this->session->data['success'] = $this->language->get('text_success_duplicate');
                    $this->response->redirect($this->url->link('accounts/journal_entry/edit', 'user_token=' . $this->session->data['user_token'] . '&journal_id=' . $new_journal_id, true));
                } catch (Exception $e) {
                    $this->session->data['error'] = $e->getMessage();
                }
            }
        }

        $this->response->redirect($this->url->link('accounts/journal_entry', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function print() {
        $this->load->language('accounts/journal_entry');
        $this->load->model('accounts/journal_entry');

        if (isset($this->request->get['journal_id'])) {
            $journal_id = $this->request->get['journal_id'];
            $journal = $this->model_accounts_journal_entry->getJournalEntry($journal_id);

            if ($journal) {
                $data['journal'] = $journal;
                $data['company_name'] = $this->config->get('config_name');
                $data['print_date'] = date($this->language->get('date_format_long'));

                $this->response->setOutput($this->load->view('accounts/journal_entry_print', $data));
                return;
            }
        } elseif (isset($this->request->get['journal_ids'])) {
            // طباعة متعددة
            $journal_ids = explode(',', $this->request->get['journal_ids']);
            $journals = array();

            foreach ($journal_ids as $journal_id) {
                $journal = $this->model_accounts_journal_entry->getJournalEntry($journal_id);
                if ($journal) {
                    $journals[] = $journal;
                }
            }

            if (!empty($journals)) {
                $data['journals'] = $journals;
                $data['company_name'] = $this->config->get('config_name');
                $data['print_date'] = date($this->language->get('date_format_long'));

                $this->response->setOutput($this->load->view('accounts/journal_entry_print_multiple', $data));
                return;
            }
        }

        $this->response->redirect($this->url->link('accounts/journal_entry', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function export() {
        $this->load->language('accounts/journal_entry');
        $this->load->model('accounts/journal_entry');

        $format = $this->request->get['format'] ?? 'excel';
        $date_start = $this->request->get['date_start'] ?? '';
        $date_end = $this->request->get['date_end'] ?? '';
        $status = $this->request->get['status'] ?? '';

        $filter_data = array();
        if ($date_start) $filter_data['filter_date_start'] = $date_start;
        if ($date_end) $filter_data['filter_date_end'] = $date_end;
        if ($status) $filter_data['filter_status'] = $status;

        $journals = $this->model_accounts_journal_entry->getJournalEntries($filter_data);

        switch ($format) {
            case 'excel':
                $this->exportToExcel($journals);
                break;
            case 'pdf':
                $this->exportToPdf($journals);
                break;
            case 'csv':
                $this->exportToCsv($journals);
                break;
            default:
                $this->exportToExcel($journals);
        }
    }

    public function getAccountInfo() {
        $this->load->model('accounts/chartaccount');

        $json = array();

        if (isset($this->request->get['account_id'])) {
            $account = $this->model_accounts_chartaccount->getAccount($this->request->get['account_id']);

            if ($account) {
                $json = array(
                    'account_id' => $account['account_id'],
                    'account_code' => $account['account_code'],
                    'account_name' => $account['name'],
                    'account_type' => $account['account_type'],
                    'account_nature' => $account['account_nature'],
                    'current_balance' => $account['current_balance'] ?? 0,
                    'allow_posting' => $account['allow_posting'] ?? 1
                );
            } else {
                $json['error'] = $this->language->get('error_account_not_found');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function searchAccounts() {
        $this->load->model('accounts/chartaccount');

        $json = array();

        if (isset($this->request->get['term'])) {
            $filter_data = array(
                'filter_search' => $this->request->get['term'],
                'filter_allow_posting' => 1,
                'limit' => 20
            );

            $accounts = $this->model_accounts_chartaccount->getAccounts($filter_data);

            foreach ($accounts as $account) {
                $json[] = array(
                    'id' => $account['account_id'],
                    'text' => $account['account_code'] . ' - ' . $account['name'],
                    'account_code' => $account['account_code'],
                    'account_name' => $account['name'],
                    'account_type' => $account['account_type'],
                    'current_balance' => $account['current_balance'] ?? 0
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function validateBalance() {
        $json = array();

        if (isset($this->request->post['lines'])) {
            $total_debit = 0;
            $total_credit = 0;

            foreach ($this->request->post['lines'] as $line) {
                $total_debit += (float)($line['debit_amount'] ?? 0);
                $total_credit += (float)($line['credit_amount'] ?? 0);
            }

            $difference = abs($total_debit - $total_credit);

            $json = array(
                'total_debit' => $total_debit,
                'total_credit' => $total_credit,
                'difference' => $difference,
                'is_balanced' => $difference < 0.01,
                'total_debit_formatted' => $this->currency->format($total_debit, $this->config->get('config_currency')),
                'total_credit_formatted' => $this->currency->format($total_credit, $this->config->get('config_currency')),
                'difference_formatted' => $this->currency->format($difference, $this->config->get('config_currency'))
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getTemplates() {
        $this->load->model('accounts/journal_template');

        $json = array();

        $templates = $this->model_accounts_journal_template->getTemplates();

        foreach ($templates as $template) {
            $json[] = array(
                'template_id' => $template['template_id'],
                'name' => $template['name'],
                'description' => $template['description'],
                'lines' => $template['lines']
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function saveAsTemplate() {
        $this->load->language('accounts/journal_entry');
        $this->load->model('accounts/journal_template');

        $json = array();

        if (!$this->user->hasPermission('modify', 'accounts/journal_entry')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['template_name']) && isset($this->request->post['lines'])) {
                try {
                    $template_data = array(
                        'name' => $this->request->post['template_name'],
                        'description' => $this->request->post['template_description'] ?? '',
                        'lines' => $this->request->post['lines']
                    );

                    $template_id = $this->model_accounts_journal_template->addTemplate($template_data);
                    $json['success'] = $this->language->get('text_success_template_save');
                    $json['template_id'] = $template_id;
                } catch (Exception $e) {
                    $json['error'] = $e->getMessage();
                }
            } else {
                $json['error'] = $this->language->get('error_template_data');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function getJournalStats() {
        $stats = array(
            'total_journals' => 0,
            'draft_journals' => 0,
            'posted_journals' => 0,
            'total_debit' => 0,
            'total_credit' => 0
        );

        $query = $this->db->query("SELECT
            COUNT(*) as total_journals,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_journals,
            SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted_journals,
            SUM(total_debit) as total_debit,
            SUM(total_credit) as total_credit
            FROM " . DB_PREFIX . "journal_entry");

        if ($query->num_rows) {
            $stats = $query->row;
        }

        return $stats;
    }

    private function exportToExcel($journals) {
        $filename = 'journal_entries_' . date('Y-m-d') . '.xls';

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo '<table border="1">';
        echo '<tr>';
        echo '<th>رقم القيد</th>';
        echo '<th>تاريخ القيد</th>';
        echo '<th>الوصف</th>';
        echo '<th>إجمالي المدين</th>';
        echo '<th>إجمالي الدائن</th>';
        echo '<th>الحالة</th>';
        echo '<th>المنشئ</th>';
        echo '</tr>';

        foreach ($journals as $journal) {
            echo '<tr>';
            echo '<td>' . $journal['journal_number'] . '</td>';
            echo '<td>' . date('Y-m-d', strtotime($journal['journal_date'])) . '</td>';
            echo '<td>' . $journal['description'] . '</td>';
            echo '<td>' . number_format($journal['total_debit'], 2) . '</td>';
            echo '<td>' . number_format($journal['total_credit'], 2) . '</td>';
            echo '<td>' . $journal['status'] . '</td>';
            echo '<td>' . $journal['created_by_name'] . '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }

    private function exportToPdf($journals) {
        require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('ERP System');
        $pdf->SetAuthor($this->config->get('config_name'));
        $pdf->SetTitle('القيود المحاسبية');

        $pdf->SetFont('aealarabiya', '', 12);
        $pdf->AddPage();

        $pdf->Cell(0, 10, 'القيود المحاسبية', 0, 1, 'C');
        $pdf->Cell(0, 10, $this->config->get('config_name'), 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFont('aealarabiya', 'B', 10);
        $pdf->Cell(30, 8, 'رقم القيد', 1, 0, 'C');
        $pdf->Cell(25, 8, 'التاريخ', 1, 0, 'C');
        $pdf->Cell(60, 8, 'الوصف', 1, 0, 'C');
        $pdf->Cell(25, 8, 'المدين', 1, 0, 'C');
        $pdf->Cell(25, 8, 'الدائن', 1, 0, 'C');
        $pdf->Cell(25, 8, 'الحالة', 1, 1, 'C');

        $pdf->SetFont('aealarabiya', '', 9);
        foreach ($journals as $journal) {
            $pdf->Cell(30, 6, $journal['journal_number'], 1, 0, 'C');
            $pdf->Cell(25, 6, date('Y-m-d', strtotime($journal['journal_date'])), 1, 0, 'C');
            $pdf->Cell(60, 6, $journal['description'], 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($journal['total_debit'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($journal['total_credit'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, $journal['status'], 1, 1, 'C');
        }

        $pdf->Output('journal_entries_' . date('Y-m-d') . '.pdf', 'D');
    }

    private function exportToCsv($journals) {
        $filename = 'journal_entries_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');

        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        $headers = array('رقم القيد', 'تاريخ القيد', 'الوصف', 'إجمالي المدين', 'إجمالي الدائن', 'الحالة', 'المنشئ');
        fputcsv($output, $headers);

        foreach ($journals as $journal) {
            $row = array(
                $journal['journal_number'],
                date('Y-m-d', strtotime($journal['journal_date'])),
                $journal['description'],
                number_format($journal['total_debit'], 2),
                number_format($journal['total_credit'], 2),
                $journal['status'],
                $journal['created_by_name']
            );

            fputcsv($output, $row);
        }

        fclose($output);
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'accounts/journal_entry')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['journal_date'])) {
            $this->error['journal_date'] = $this->language->get('error_journal_date');
        }

        if (empty($this->request->post['description'])) {
            $this->error['description'] = $this->language->get('error_description');
        }

        if (empty($this->request->post['lines']) || count($this->request->post['lines']) < 2) {
            $this->error['lines'] = $this->language->get('error_lines_minimum');
        } else {
            $total_debit = 0;
            $total_credit = 0;

            foreach ($this->request->post['lines'] as $line) {
                if (empty($line['account_id'])) {
                    $this->error['lines'] = $this->language->get('error_account_required');
                    break;
                }

                $debit = (float)($line['debit_amount'] ?? 0);
                $credit = (float)($line['credit_amount'] ?? 0);

                if ($debit == 0 && $credit == 0) {
                    $this->error['lines'] = $this->language->get('error_amount_required');
                    break;
                }

                if ($debit > 0 && $credit > 0) {
                    $this->error['lines'] = $this->language->get('error_both_amounts');
                    break;
                }

                $total_debit += $debit;
                $total_credit += $credit;
            }

            if (abs($total_debit - $total_credit) > 0.01) {
                $this->error['lines'] = $this->language->get('error_unbalanced');
            }
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'accounts/journal_entry')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function validateDuplicate() {
        if (!$this->user->hasPermission('modify', 'accounts/journal_entry')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['journal_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['journal_date'])) {
            $data['error_journal_date'] = $this->error['journal_date'];
        } else {
            $data['error_journal_date'] = '';
        }

        if (isset($this->error['description'])) {
            $data['error_description'] = $this->error['description'];
        } else {
            $data['error_description'] = '';
        }

        if (isset($this->error['lines'])) {
            $data['error_lines'] = $this->error['lines'];
        } else {
            $data['error_lines'] = '';
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
            'href' => $this->url->link('accounts/journal_entry', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['journal_id'])) {
            $data['action'] = $this->url->link('accounts/journal_entry/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('accounts/journal_entry/edit', 'user_token=' . $this->session->data['user_token'] . '&journal_id=' . $this->request->get['journal_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('accounts/journal_entry', 'user_token=' . $this->session->data['user_token'] . $url, true);

        // URLs للـ AJAX
        $data['get_account_info_url'] = $this->url->link('accounts/journal_entry/getAccountInfo', 'user_token=' . $this->session->data['user_token'], true);
        $data['search_accounts_url'] = $this->url->link('accounts/journal_entry/searchAccounts', 'user_token=' . $this->session->data['user_token'], true);
        $data['validate_balance_url'] = $this->url->link('accounts/journal_entry/validateBalance', 'user_token=' . $this->session->data['user_token'], true);
        $data['get_templates_url'] = $this->url->link('accounts/journal_entry/getTemplates', 'user_token=' . $this->session->data['user_token'], true);
        $data['save_template_url'] = $this->url->link('accounts/journal_entry/saveAsTemplate', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->get['journal_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $journal_info = $this->model_accounts_journal_entry->getJournalEntry($this->request->get['journal_id']);
        }

        // بيانات النموذج
        $fields = ['journal_number', 'journal_date', 'description', 'reference_type', 'reference_id',
                   'reference_number', 'status', 'cost_center_id', 'project_id', 'department_id'];

        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($journal_info)) {
                $data[$field] = $journal_info[$field];
            } else {
                $data[$field] = ($field == 'journal_date') ? date('Y-m-d') :
                               (($field == 'status') ? 'draft' : '');
            }
        }

        // بنود القيد
        if (isset($this->request->post['lines'])) {
            $data['lines'] = $this->request->post['lines'];
        } elseif (!empty($journal_info)) {
            $data['lines'] = $journal_info['lines'];
        } else {
            $data['lines'] = array();
        }

        // الحصول على مراكز التكلفة والمشاريع والأقسام
        $this->load->model('accounts/cost_center');
        $this->load->model('accounts/project');
        $this->load->model('accounts/department');

        $data['cost_centers'] = $this->model_accounts_cost_center->getCostCenters();
        $data['projects'] = $this->model_accounts_project->getProjects();
        $data['departments'] = $this->model_accounts_department->getDepartments();

        // أنواع المراجع
        $data['reference_types'] = array(
            '' => $this->language->get('text_select'),
            'sales_order' => $this->language->get('text_sales_order'),
            'purchase_order' => $this->language->get('text_purchase_order'),
            'customer_payment' => $this->language->get('text_customer_payment'),
            'supplier_payment' => $this->language->get('text_supplier_payment'),
            'inventory_movement' => $this->language->get('text_inventory_movement'),
            'manual' => $this->language->get('text_manual')
        );

        // حالات القيد
        $data['statuses'] = array(
            'draft' => $this->language->get('text_draft'),
            'posted' => $this->language->get('text_posted'),
            'approved' => $this->language->get('text_approved'),
            'cancelled' => $this->language->get('text_cancelled')
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounts/journal_entry_form', $data));
    }

    /**
     * التحقق من موافقة الحذف
     */
    private function hasDeleteApproval($journal_id) {
        $this->load->model('accounts/approval');
        return $this->model_accounts_approval->hasDeleteApproval($this->user->getId(), $journal_id);
    }

    /**
     * إرسال إشعار للمبالغ الكبيرة
     */
    private function sendLargeAmountNotification($journal_id, $journal_data) {
        $this->load->model('notification/notification');

        $message = "تنبيه: تم ترحيل قيد بمبلغ كبير\n";
        $message .= "رقم القيد: {$journal_data['journal_number']}\n";
        $message .= "المبلغ: " . $this->currency->format($journal_data['total_debit'], $this->config->get('config_currency')) . "\n";
        $message .= "الوصف: {$journal_data['description']}\n";
        $message .= "المستخدم: " . $this->user->getUserName() . "\n";
        $message .= "التاريخ: " . date('Y-m-d H:i:s');

        // إرسال للمدير المالي
        $financial_managers = $this->getFinancialManagers();

        foreach ($financial_managers as $manager) {
            $this->model_notification_notification->send([
                'user_id' => $manager['user_id'],
                'title' => 'تنبيه: ترحيل قيد بمبلغ كبير',
                'message' => $message,
                'type' => 'large_amount_alert',
                'priority' => 'high',
                'module' => 'journal_entry',
                'reference_id' => $journal_id
            ]);
        }
    }

    /**
     * الحصول على المديرين الماليين
     */
    private function getFinancialManagers() {
        $query = $this->db->query("
            SELECT DISTINCT u.user_id, u.username, u.email
            FROM " . DB_PREFIX . "user u
            JOIN " . DB_PREFIX . "user_group ug ON u.user_group_id = ug.user_group_id
            WHERE ug.name IN ('financial_manager', 'cfo')
            AND u.status = 1
        ");

        return $query->rows;
    }

    /**
     * التحقق من التكامل المحاسبي
     */
    public function validateAccountingIntegrity() {
        $this->load->model('accounts/journal_entry');
        $this->load->model('accounts/chartaccount');

        $json = array();

        try {
            // التحقق من توازن جميع القيود المرحلة
            $unbalanced_journals = $this->model_accounts_journal_entry->getUnbalancedJournals();

            if (!empty($unbalanced_journals)) {
                $json['errors'][] = 'توجد قيود غير متوازنة: ' . implode(', ', $unbalanced_journals);
            }

            // التحقق من تطابق أرصدة الحسابات
            $account_balance_errors = $this->model_accounts_chartaccount->validateAccountBalances();

            if (!empty($account_balance_errors)) {
                $json['errors'][] = 'أخطاء في أرصدة الحسابات: ' . implode(', ', $account_balance_errors);
            }

            // التحقق من الميزانية العمومية
            $trial_balance = $this->model_accounts_journal_entry->getTrialBalance();
            $total_debit = array_sum(array_column($trial_balance, 'debit'));
            $total_credit = array_sum(array_column($trial_balance, 'credit'));

            if (abs($total_debit - $total_credit) > 0.01) {
                $json['errors'][] = 'الميزانية العمومية غير متوازنة';
            }

            if (empty($json['errors'])) {
                $json['success'] = 'التكامل المحاسبي سليم';
            }

        } catch (Exception $e) {
            $json['error'] = 'خطأ في التحقق من التكامل: ' . $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تقرير سجل المراجعة للقيد
     */
    public function auditTrail() {
        $this->load->language('accounts/journal_entry');
        $this->load->model('accounts/audit_trail');

        if (isset($this->request->get['journal_id'])) {
            $journal_id = $this->request->get['journal_id'];
            $audit_trail = $this->model_accounts_audit_trail->getJournalAuditTrail($journal_id);

            $data['audit_trail'] = $audit_trail;
            $data['journal_id'] = $journal_id;

            $this->response->setOutput($this->load->view('accounts/journal_entry_audit_trail', $data));
        } else {
            $this->response->redirect($this->url->link('accounts/journal_entry', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * نظام الموافقات المتقدم
     */
    public function requestApproval() {
        $this->load->language('accounts/journal_entry');
        $this->load->model('accounts/approval');

        $json = array();

        if (isset($this->request->post['journal_id'])) {
            $journal_id = $this->request->post['journal_id'];
            $approval_type = $this->request->post['approval_type'] ?? 'standard';
            $reason = $this->request->post['reason'] ?? '';

            try {
                $approval_id = $this->model_accounts_approval->requestApproval([
                    'module' => 'journal_entry',
                    'record_id' => $journal_id,
                    'approval_type' => $approval_type,
                    'reason' => $reason,
                    'requested_by' => $this->user->getId(),
                    'request_date' => date('Y-m-d H:i:s')
                ]);

                $json['success'] = 'تم إرسال طلب الموافقة بنجاح';
                $json['approval_id'] = $approval_id;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف القيد مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}