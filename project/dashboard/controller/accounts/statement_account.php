<?php
/**
 * تحكم كشوف الحسابات المحاسبية
 * يدعم عرض كشف حساب مفصل مع الأرصدة
 */
class ControllerAccountsStatementAccount extends Controller {
    
    private $error = array();
    
    public function index() {
        $this->load->language('accounts/statement_account');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('accounts/statement_account');
        $this->load->model('accounts/chartaccount');
        
        $this->getList();
    }
    
    public function view() {
        $this->load->language('accounts/statement_account');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('accounts/statement_account');
        $this->load->model('accounts/chartaccount');
        
        $this->getStatement();
    }
    
    public function export() {
        $this->load->language('accounts/statement_account');
        $this->load->model('accounts/statement_account');
        
        if (isset($this->request->get['account_id']) && isset($this->request->get['date_start']) && isset($this->request->get['date_end'])) {
            $account_id = (int)$this->request->get['account_id'];
            $date_start = $this->request->get['date_start'];
            $date_end = $this->request->get['date_end'];
            
            $account = $this->model_accounts_chartaccount->getAccount($account_id);
            $statement_data = $this->model_accounts_statement_account->getAccountStatement($account_id, $date_start, $date_end);
            
            // إنشاء ملف Excel أو PDF
            $this->exportToExcel($account, $statement_data, $date_start, $date_end);
        }
    }
    
    protected function getList() {
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/statement_account', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['view_statement'] = $this->url->link('accounts/statement_account/view', 'user_token=' . $this->session->data['user_token'], true);
        
        // الحصول على قائمة الحسابات للاختيار
        $data['accounts'] = $this->model_accounts_chartaccount->getAccountsForJournal();
        
        // تواريخ افتراضية
        $data['date_start'] = date('Y-m-01'); // أول الشهر الحالي
        $data['date_end'] = date('Y-m-d'); // اليوم
        
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
        
        $this->response->setOutput($this->load->view('accounts/statement_account_form', $data));
    }
    
    protected function getStatement() {
        if (isset($this->request->get['account_id'])) {
            $account_id = (int)$this->request->get['account_id'];
        } else {
            $account_id = 0;
        }
        
        if (isset($this->request->get['date_start'])) {
            $date_start = $this->request->get['date_start'];
        } else {
            $date_start = date('Y-m-01');
        }
        
        if (isset($this->request->get['date_end'])) {
            $date_end = $this->request->get['date_end'];
        } else {
            $date_end = date('Y-m-d');
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/statement_account', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_view_statement'),
            'href' => $this->url->link('accounts/statement_account/view', 'user_token=' . $this->session->data['user_token'] . '&account_id=' . $account_id . '&date_start=' . $date_start . '&date_end=' . $date_end, true)
        );
        
        // الحصول على بيانات الحساب
        $account = $this->model_accounts_chartaccount->getAccount($account_id);
        
        if ($account) {
            $data['account'] = $account;
            
            // الحصول على أوصاف الحساب
            $account_descriptions = $this->model_accounts_chartaccount->getAccountDescriptions($account_id);
            if (isset($account_descriptions[$this->config->get('config_language_id')])) {
                $data['account']['name'] = $account_descriptions[$this->config->get('config_language_id')]['name'];
            }
            
            // الحصول على كشف الحساب
            $statement_data = $this->model_accounts_statement_account->getAccountStatement($account_id, $date_start, $date_end);
            
            $data['statement'] = $statement_data;
            $data['date_start'] = $date_start;
            $data['date_end'] = $date_end;
            $data['date_start_formatted'] = date($this->language->get('date_format_short'), strtotime($date_start));
            $data['date_end_formatted'] = date($this->language->get('date_format_short'), strtotime($date_end));
            
            // روابط التصدير والطباعة
            $data['export_excel'] = $this->url->link('accounts/statement_account/export', 'user_token=' . $this->session->data['user_token'] . '&account_id=' . $account_id . '&date_start=' . $date_start . '&date_end=' . $date_end . '&format=excel', true);
            $data['export_pdf'] = $this->url->link('accounts/statement_account/export', 'user_token=' . $this->session->data['user_token'] . '&account_id=' . $account_id . '&date_start=' . $date_start . '&date_end=' . $date_end . '&format=pdf', true);
            $data['print'] = $this->url->link('accounts/statement_account/print', 'user_token=' . $this->session->data['user_token'] . '&account_id=' . $account_id . '&date_start=' . $date_start . '&date_end=' . $date_end, true);
            
            $data['back'] = $this->url->link('accounts/statement_account', 'user_token=' . $this->session->data['user_token'], true);
            
            if (isset($this->error['warning'])) {
                $data['error_warning'] = $this->error['warning'];
            } else {
                $data['error_warning'] = '';
            }
            
            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');
            
            $this->response->setOutput($this->load->view('accounts/statement_account_view', $data));
        } else {
            $this->session->data['error'] = $this->language->get('error_account_not_found');
            $this->response->redirect($this->url->link('accounts/statement_account', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    public function print() {
        $this->load->language('accounts/statement_account');
        $this->load->model('accounts/statement_account');
        $this->load->model('accounts/chartaccount');
        
        if (isset($this->request->get['account_id']) && isset($this->request->get['date_start']) && isset($this->request->get['date_end'])) {
            $account_id = (int)$this->request->get['account_id'];
            $date_start = $this->request->get['date_start'];
            $date_end = $this->request->get['date_end'];
            
            // الحصول على بيانات الحساب
            $account = $this->model_accounts_chartaccount->getAccount($account_id);
            
            if ($account) {
                // الحصول على أوصاف الحساب
                $account_descriptions = $this->model_accounts_chartaccount->getAccountDescriptions($account_id);
                if (isset($account_descriptions[$this->config->get('config_language_id')])) {
                    $account['name'] = $account_descriptions[$this->config->get('config_language_id')]['name'];
                }
                
                // الحصول على كشف الحساب
                $statement_data = $this->model_accounts_statement_account->getAccountStatement($account_id, $date_start, $date_end);
                
                $data['account'] = $account;
                $data['statement'] = $statement_data;
                $data['date_start'] = $date_start;
                $data['date_end'] = $date_end;
                $data['date_start_formatted'] = date($this->language->get('date_format_short'), strtotime($date_start));
                $data['date_end_formatted'] = date($this->language->get('date_format_short'), strtotime($date_end));
                
                $this->response->setOutput($this->load->view('accounts/statement_account_print', $data));
            }
        }
    }
    
    public function autocomplete() {
        $json = array();
        
        if (isset($this->request->get['filter_name'])) {
            $this->load->model('accounts/chartaccount');
            
            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'start' => 0,
                'limit' => 5
            );
            
            $results = $this->model_accounts_chartaccount->getAccountsForJournal($filter_data);
            
            foreach ($results as $result) {
                $json[] = array(
                    'account_id' => $result['account_id'],
                    'name' => strip_tags(html_entity_decode($result['account_code'] . ' - ' . $result['name'], ENT_QUOTES, 'UTF-8'))
                );
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    private function exportToExcel($account, $statement_data, $date_start, $date_end) {
        // هنا يمكن إضافة كود تصدير Excel
        // مثل استخدام مكتبة PhpSpreadsheet
        
        $filename = 'account_statement_' . $account['account_code'] . '_' . date('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // إنشاء محتوى Excel بسيط
        $output = "Account Code\tAccount Name\tDate\tDescription\tDebit\tCredit\tBalance\n";
        
        foreach ($statement_data['transactions'] as $transaction) {
            $output .= $account['account_code'] . "\t";
            $output .= $account['name'] . "\t";
            $output .= $transaction['journal_date'] . "\t";
            $output .= $transaction['description'] . "\t";
            $output .= $transaction['debit_amount'] . "\t";
            $output .= $transaction['credit_amount'] . "\t";
            $output .= $transaction['running_balance'] . "\n";
        }
        
        echo $output;
        exit;
    }
}
?>
