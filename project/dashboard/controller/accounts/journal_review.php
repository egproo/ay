<?php
/**
 * تحكم مراجعة واعتماد القيود المحاسبية
 * يدعم دورة عمل الاعتماد والمراجعة
 */
class ControllerAccountsJournalReview extends Controller {
    
    private $error = array();
    
    public function index() {
        $this->load->language('accounts/journal_review');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('accounts/journal_review');
        
        $this->getList();
    }
    
    public function approve() {
        $this->load->language('accounts/journal_review');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('accounts/journal_review');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateApprove()) {
            $journal_id = (int)$this->request->post['journal_id'];
            $approval_notes = $this->request->post['approval_notes'] ?? '';
            
            $this->model_accounts_journal_review->approveJournal($journal_id, $approval_notes);
            
            $this->session->data['success'] = $this->language->get('text_success_approved');
            
            $url = '';
            
            if (isset($this->request->get['filter_journal_number'])) {
                $url .= '&filter_journal_number=' . urlencode(html_entity_decode($this->request->get['filter_journal_number'], ENT_QUOTES, 'UTF-8'));
            }
            
            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }
            
            if (isset($this->request->get['filter_date_start'])) {
                $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
            }
            
            if (isset($this->request->get['filter_date_end'])) {
                $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
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
            
            $this->response->redirect($this->url->link('accounts/journal_review', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getApprove();
    }
    
    public function reject() {
        $this->load->language('accounts/journal_review');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('accounts/journal_review');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateReject()) {
            $journal_id = (int)$this->request->post['journal_id'];
            $rejection_reason = $this->request->post['rejection_reason'];
            
            $this->model_accounts_journal_review->rejectJournal($journal_id, $rejection_reason);
            
            $this->session->data['success'] = $this->language->get('text_success_rejected');
            
            $this->response->redirect($this->url->link('accounts/journal_review', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->getReject();
    }
    
    public function view() {
        $this->load->language('accounts/journal_review');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('accounts/journal_review');
        
        $this->getView();
    }
    
    protected function getList() {
        if (isset($this->request->get['filter_journal_number'])) {
            $filter_journal_number = $this->request->get['filter_journal_number'];
        } else {
            $filter_journal_number = '';
        }
        
        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = 'pending';
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
            $sort = 'je.journal_date';
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
        
        if (isset($this->request->get['filter_journal_number'])) {
            $url .= '&filter_journal_number=' . urlencode(html_entity_decode($this->request->get['filter_journal_number'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }
        
        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }
        
        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }
        
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/journal_review', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        $data['approve'] = $this->url->link('accounts/journal_review/approve', 'user_token=' . $this->session->data['user_token'], true);
        $data['reject'] = $this->url->link('accounts/journal_review/reject', 'user_token=' . $this->session->data['user_token'], true);
        
        $data['journals'] = array();
        
        $filter_data = array(
            'filter_journal_number' => $filter_journal_number,
            'filter_status' => $filter_status,
            'filter_date_start' => $filter_date_start,
            'filter_date_end' => $filter_date_end,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );
        
        $journal_total = $this->model_accounts_journal_review->getTotalJournals($filter_data);
        
        $results = $this->model_accounts_journal_review->getJournals($filter_data);
        
        foreach ($results as $result) {
            $data['journals'][] = array(
                'journal_id' => $result['journal_id'],
                'journal_number' => $result['journal_number'],
                'journal_date' => date($this->language->get('date_format_short'), strtotime($result['journal_date'])),
                'description' => $result['description'],
                'total_amount' => $this->currency->format($result['total_debit'], $this->config->get('config_currency')),
                'status' => $result['status'],
                'created_by' => $result['created_by_name'],
                'date_added' => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
                'view' => $this->url->link('accounts/journal_review/view', 'user_token=' . $this->session->data['user_token'] . '&journal_id=' . $result['journal_id'] . $url, true),
                'approve' => $this->url->link('accounts/journal_review/approve', 'user_token=' . $this->session->data['user_token'] . '&journal_id=' . $result['journal_id'] . $url, true),
                'reject' => $this->url->link('accounts/journal_review/reject', 'user_token=' . $this->session->data['user_token'] . '&journal_id=' . $result['journal_id'] . $url, true)
            );
        }
        
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
            $data['selected'] = array();
        }
        
        $url = '';
        
        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }
        
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }
        
        $data['sort_journal_number'] = $this->url->link('accounts/journal_review', 'user_token=' . $this->session->data['user_token'] . '&sort=je.journal_number' . $url, true);
        $data['sort_journal_date'] = $this->url->link('accounts/journal_review', 'user_token=' . $this->session->data['user_token'] . '&sort=je.journal_date' . $url, true);
        $data['sort_description'] = $this->url->link('accounts/journal_review', 'user_token=' . $this->session->data['user_token'] . '&sort=je.description' . $url, true);
        $data['sort_status'] = $this->url->link('accounts/journal_review', 'user_token=' . $this->session->data['user_token'] . '&sort=je.status' . $url, true);
        $data['sort_date_added'] = $this->url->link('accounts/journal_review', 'user_token=' . $this->session->data['user_token'] . '&sort=je.date_added' . $url, true);
        
        $url = '';
        
        if (isset($this->request->get['filter_journal_number'])) {
            $url .= '&filter_journal_number=' . urlencode(html_entity_decode($this->request->get['filter_journal_number'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }
        
        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }
        
        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }
        
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        
        $pagination = new Pagination();
        $pagination->total = $journal_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('accounts/journal_review', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
        
        $data['pagination'] = $pagination->render();
        
        $data['results'] = sprintf($this->language->get('text_pagination'), ($journal_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($journal_total - $this->config->get('config_limit_admin'))) ? $journal_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $journal_total, ceil($journal_total / $this->config->get('config_limit_admin')));
        
        $data['filter_journal_number'] = $filter_journal_number;
        $data['filter_status'] = $filter_status;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;
        
        $data['sort'] = $sort;
        $data['order'] = $order;
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('accounts/journal_review_list', $data));
    }
    
    protected function validateApprove() {
        if (!$this->user->hasPermission('modify', 'accounts/journal_review')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (!isset($this->request->post['journal_id']) || empty($this->request->post['journal_id'])) {
            $this->error['warning'] = $this->language->get('error_journal_id');
        }
        
        return !$this->error;
    }
    
    protected function validateReject() {
        if (!$this->user->hasPermission('modify', 'accounts/journal_review')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (!isset($this->request->post['journal_id']) || empty($this->request->post['journal_id'])) {
            $this->error['warning'] = $this->language->get('error_journal_id');
        }
        
        if (empty($this->request->post['rejection_reason'])) {
            $this->error['rejection_reason'] = $this->language->get('error_rejection_reason');
        }
        
        return !$this->error;
    }
}
?>
