<?php
/**
 * تحكم إغلاق الفترة المحاسبية
 * يدعم إغلاق الحسابات المؤقتة وترحيل الأرباح والخسائر
 */
class ControllerAccountsPeriodClosing extends Controller {
    
    private $error = array();
    
    public function index() {
        $this->load->language('accounts/period_closing');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('accounts/period_closing');
        
        $this->getList();
    }
    
    public function close() {
        $this->load->language('accounts/period_closing');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('accounts/period_closing');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateClose()) {
            $period_data = array(
                'period_name' => $this->request->post['period_name'],
                'start_date' => $this->request->post['start_date'],
                'end_date' => $this->request->post['end_date'],
                'closing_notes' => $this->request->post['closing_notes'] ?? ''
            );
            
            $result = $this->model_accounts_period_closing->closePeriod($period_data);
            
            if ($result['success']) {
                $this->session->data['success'] = $this->language->get('text_success_closed');
            } else {
                $this->session->data['error'] = $result['error'];
            }
            
            $this->response->redirect($this->url->link('accounts/period_closing', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->getClose();
    }
    
    public function reopen() {
        $this->load->language('accounts/period_closing');
        
        $this->load->model('accounts/period_closing');
        
        if (isset($this->request->get['period_id'])) {
            $period_id = (int)$this->request->get['period_id'];
            
            if ($this->validateReopen()) {
                $result = $this->model_accounts_period_closing->reopenPeriod($period_id);
                
                if ($result['success']) {
                    $this->session->data['success'] = $this->language->get('text_success_reopened');
                } else {
                    $this->session->data['error'] = $result['error'];
                }
            }
        }
        
        $this->response->redirect($this->url->link('accounts/period_closing', 'user_token=' . $this->session->data['user_token'], true));
    }
    
    public function preview() {
        $this->load->language('accounts/period_closing');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('accounts/period_closing');
        
        $this->getPreview();
    }
    
    protected function getList() {
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/period_closing', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['close'] = $this->url->link('accounts/period_closing/close', 'user_token=' . $this->session->data['user_token'], true);
        $data['preview'] = $this->url->link('accounts/period_closing/preview', 'user_token=' . $this->session->data['user_token'], true);
        
        // الحصول على الفترات المحاسبية
        $data['periods'] = array();
        
        $results = $this->model_accounts_period_closing->getAccountingPeriods();
        
        foreach ($results as $result) {
            $data['periods'][] = array(
                'period_id' => $result['period_id'],
                'period_name' => $result['period_name'],
                'start_date' => date($this->language->get('date_format_short'), strtotime($result['start_date'])),
                'end_date' => date($this->language->get('date_format_short'), strtotime($result['end_date'])),
                'status' => $result['status'],
                'closed_by' => $result['closed_by_name'],
                'closed_date' => $result['closed_date'] ? date($this->language->get('datetime_format'), strtotime($result['closed_date'])) : '',
                'net_income' => $this->currency->format($result['net_income'], $this->config->get('config_currency')),
                'view' => $this->url->link('accounts/period_closing/view', 'user_token=' . $this->session->data['user_token'] . '&period_id=' . $result['period_id'], true),
                'reopen' => $this->url->link('accounts/period_closing/reopen', 'user_token=' . $this->session->data['user_token'] . '&period_id=' . $result['period_id'], true)
            );
        }
        
        // الحصول على الفترة الحالية المفتوحة
        $current_period = $this->model_accounts_period_closing->getCurrentPeriod();
        $data['current_period'] = $current_period;
        
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
        
        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('accounts/period_closing_list', $data));
    }
    
    protected function getClose() {
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/period_closing', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_close_period'),
            'href' => $this->url->link('accounts/period_closing/close', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['action'] = $this->url->link('accounts/period_closing/close', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('accounts/period_closing', 'user_token=' . $this->session->data['user_token'], true);
        
        // الحصول على معاينة الإغلاق
        $preview_data = $this->model_accounts_period_closing->getClosingPreview();
        $data['preview'] = $preview_data;
        
        // تواريخ افتراضية
        if (isset($this->request->post['period_name'])) {
            $data['period_name'] = $this->request->post['period_name'];
        } else {
            $data['period_name'] = 'فترة ' . date('Y');
        }
        
        if (isset($this->request->post['start_date'])) {
            $data['start_date'] = $this->request->post['start_date'];
        } else {
            $data['start_date'] = date('Y-01-01');
        }
        
        if (isset($this->request->post['end_date'])) {
            $data['end_date'] = $this->request->post['end_date'];
        } else {
            $data['end_date'] = date('Y-12-31');
        }
        
        if (isset($this->request->post['closing_notes'])) {
            $data['closing_notes'] = $this->request->post['closing_notes'];
        } else {
            $data['closing_notes'] = '';
        }
        
        if (isset($this->error['period_name'])) {
            $data['error_period_name'] = $this->error['period_name'];
        } else {
            $data['error_period_name'] = '';
        }
        
        if (isset($this->error['start_date'])) {
            $data['error_start_date'] = $this->error['start_date'];
        } else {
            $data['error_start_date'] = '';
        }
        
        if (isset($this->error['end_date'])) {
            $data['error_end_date'] = $this->error['end_date'];
        } else {
            $data['error_end_date'] = '';
        }
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('accounts/period_closing_form', $data));
    }
    
    protected function getPreview() {
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/period_closing', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_preview_closing'),
            'href' => $this->url->link('accounts/period_closing/preview', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // الحصول على معاينة الإغلاق
        $preview_data = $this->model_accounts_period_closing->getClosingPreview();
        $data['preview'] = $preview_data;
        
        $data['back'] = $this->url->link('accounts/period_closing', 'user_token=' . $this->session->data['user_token'], true);
        $data['close'] = $this->url->link('accounts/period_closing/close', 'user_token=' . $this->session->data['user_token'], true);
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('accounts/period_closing_preview', $data));
    }
    
    protected function validateClose() {
        if (!$this->user->hasPermission('modify', 'accounts/period_closing')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if ((utf8_strlen($this->request->post['period_name']) < 3) || (utf8_strlen($this->request->post['period_name']) > 64)) {
            $this->error['period_name'] = $this->language->get('error_period_name');
        }
        
        if (empty($this->request->post['start_date'])) {
            $this->error['start_date'] = $this->language->get('error_start_date');
        }
        
        if (empty($this->request->post['end_date'])) {
            $this->error['end_date'] = $this->language->get('error_end_date');
        }
        
        if (!empty($this->request->post['start_date']) && !empty($this->request->post['end_date'])) {
            if (strtotime($this->request->post['start_date']) >= strtotime($this->request->post['end_date'])) {
                $this->error['end_date'] = $this->language->get('error_date_range');
            }
        }
        
        return !$this->error;
    }
    
    protected function validateReopen() {
        if (!$this->user->hasPermission('modify', 'accounts/period_closing')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
}
?>
