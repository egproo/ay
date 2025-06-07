<?php
class ControllerAccountingJournal extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounting/journal');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('accounting/accounting_manager');

        $this->getList();
    }

    public function view() {
        $this->load->language('accounting/journal');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('accounting/accounting_manager');

        if (isset($this->request->get['journal_id'])) {
            $journal_id = $this->request->get['journal_id'];
        } else {
            $journal_id = 0;
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounting/journal', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_journal_details'),
            'href' => $this->url->link('accounting/journal/view', 'user_token=' . $this->session->data['user_token'] . '&journal_id=' . $journal_id, true)
        );

        $data['back'] = $this->url->link('accounting/journal', 'user_token=' . $this->session->data['user_token'], true);

        // Get journal details
        $journal_info = $this->model_accounting_accounting_manager->getJournalById($journal_id);

        if ($journal_info) {
            $data['journal_id'] = $journal_info['journal_id'];
            $data['reference_type'] = $journal_info['reference_type'];
            $data['reference_id'] = $journal_info['reference_id'];
            $data['period_id'] = $journal_info['period_id'];
            $data['description'] = $journal_info['description'];
            $data['date_added'] = date($this->language->get('date_format_short'), strtotime($journal_info['date_added']));
            $data['user_name'] = $journal_info['username'];
            $data['status'] = $journal_info['status'];

            // Get period information
            if ($journal_info['period_id']) {
                $this->load->model('accounting/period');
                $period_info = $this->model_accounting_period->getPeriod($journal_info['period_id']);
                if ($period_info) {
                    $data['period_name'] = $period_info['name'];
                    $data['period_status'] = $this->getPeriodStatusText($period_info['status']);
                } else {
                    $data['period_name'] = '';
                    $data['period_status'] = '';
                }
            } else {
                $data['period_name'] = '';
                $data['period_status'] = '';
            }

            // Get journal entries
            $data['entries'] = $this->model_accounting_accounting_manager->getJournalEntries($journal_id);

            // Calculate totals
            $data['total_debit'] = 0;
            $data['total_credit'] = 0;

            foreach ($data['entries'] as $entry) {
                $data['total_debit'] += $entry['debit'];
                $data['total_credit'] += $entry['credit'];
            }

            // Get reference details
            if ($journal_info['reference_type'] == 'inventory_movement') {
                $this->load->model('catalog/inventory_manager');
                $data['reference_details'] = $this->model_catalog_inventory_manager->getMovementDetails($journal_info['reference_id']);
                $data['reference_link'] = $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $data['reference_details']['product_id'] . '#tab-movement', true);
                $data['reference_text'] = $this->language->get('text_inventory_movement');
            } else {
                $data['reference_details'] = array();
                $data['reference_link'] = '';
                $data['reference_text'] = $journal_info['reference_type'];
            }
        } else {
            $data['journal_id'] = 0;
            $data['reference_type'] = '';
            $data['reference_id'] = 0;
            $data['period_id'] = 0;
            $data['description'] = '';
            $data['date_added'] = '';
            $data['user_name'] = '';
            $data['status'] = 0;
            $data['period_name'] = '';
            $data['period_status'] = '';
            $data['entries'] = array();
            $data['total_debit'] = 0;
            $data['total_credit'] = 0;
            $data['reference_details'] = array();
            $data['reference_link'] = '';
            $data['reference_text'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounting/journal_view', $data));
    }

    protected function getList() {
        if (isset($this->request->get['filter_reference_type'])) {
            $filter_reference_type = $this->request->get['filter_reference_type'];
        } else {
            $filter_reference_type = '';
        }

        if (isset($this->request->get['filter_date_from'])) {
            $filter_date_from = $this->request->get['filter_date_from'];
        } else {
            $filter_date_from = '';
        }

        if (isset($this->request->get['filter_date_to'])) {
            $filter_date_to = $this->request->get['filter_date_to'];
        } else {
            $filter_date_to = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'j.date_added';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_reference_type'])) {
            $url .= '&filter_reference_type=' . urlencode(html_entity_decode($this->request->get['filter_reference_type'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_date_from'])) {
            $url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
        }

        if (isset($this->request->get['filter_date_to'])) {
            $url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
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

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounting/journal', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['journals'] = array();

        $filter_data = array(
            'filter_reference_type' => $filter_reference_type,
            'filter_date_from'      => $filter_date_from,
            'filter_date_to'        => $filter_date_to,
            'sort'                  => $sort,
            'order'                 => $order,
            'start'                 => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'                 => $this->config->get('config_limit_admin')
        );

        $journal_total = $this->model_accounting_accounting_manager->getTotalJournals($filter_data);

        $results = $this->model_accounting_accounting_manager->getJournals($filter_data);

        foreach ($results as $result) {
            $data['journals'][] = array(
                'journal_id'     => $result['journal_id'],
                'reference_type' => $result['reference_type'],
                'reference_id'   => $result['reference_id'],
                'description'    => $result['description'],
                'date_added'     => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'user_name'      => $result['username'],
                'status'         => $result['status'],
                'view'           => $this->url->link('accounting/journal/view', 'user_token=' . $this->session->data['user_token'] . '&journal_id=' . $result['journal_id'] . $url, true)
            );
        }

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
            $data['selected'] = array();
        }

        $url = '';

        if (isset($this->request->get['filter_reference_type'])) {
            $url .= '&filter_reference_type=' . urlencode(html_entity_decode($this->request->get['filter_reference_type'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_date_from'])) {
            $url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
        }

        if (isset($this->request->get['filter_date_to'])) {
            $url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_reference_type'] = $this->url->link('accounting/journal', 'user_token=' . $this->session->data['user_token'] . '&sort=j.reference_type' . $url, true);
        $data['sort_reference_id'] = $this->url->link('accounting/journal', 'user_token=' . $this->session->data['user_token'] . '&sort=j.reference_id' . $url, true);
        $data['sort_description'] = $this->url->link('accounting/journal', 'user_token=' . $this->session->data['user_token'] . '&sort=j.description' . $url, true);
        $data['sort_date_added'] = $this->url->link('accounting/journal', 'user_token=' . $this->session->data['user_token'] . '&sort=j.date_added' . $url, true);
        $data['sort_user_name'] = $this->url->link('accounting/journal', 'user_token=' . $this->session->data['user_token'] . '&sort=u.username' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_reference_type'])) {
            $url .= '&filter_reference_type=' . urlencode(html_entity_decode($this->request->get['filter_reference_type'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_date_from'])) {
            $url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
        }

        if (isset($this->request->get['filter_date_to'])) {
            $url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
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
        $pagination->url = $this->url->link('accounting/journal', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($journal_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($journal_total - $this->config->get('config_limit_admin'))) ? $journal_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $journal_total, ceil($journal_total / $this->config->get('config_limit_admin')));

        $data['filter_reference_type'] = $filter_reference_type;
        $data['filter_date_from'] = $filter_date_from;
        $data['filter_date_to'] = $filter_date_to;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounting/journal_list', $data));
    }

    /**
     * Get period status text
     *
     * @param int $status Status ID
     * @return string Status text
     */
    private function getPeriodStatusText($status) {
        $this->load->language('accounting/period');

        switch ($status) {
            case 0:
                return $this->language->get('text_open');
            case 1:
                return $this->language->get('text_closed');
            case 2:
                return $this->language->get('text_locked');
            default:
                return '';
        }
    }
}
