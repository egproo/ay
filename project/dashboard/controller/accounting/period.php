<?php
class ControllerAccountingPeriod extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounting/period');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('accounting/period');

        $this->getList();
    }

    public function add() {
        $this->load->language('accounting/period');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('accounting/period');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_accounting_period->addPeriod($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

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

            $this->response->redirect($this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('accounting/period');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('accounting/period');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_accounting_period->editPeriod($this->request->get['period_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

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

            $this->response->redirect($this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('accounting/period');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('accounting/period');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $period_id) {
                $result = $this->model_accounting_period->deletePeriod($period_id);
                
                if (!$result) {
                    $this->error['warning'] = $this->language->get('error_delete');
                }
            }

            if (!isset($this->error['warning'])) {
                $this->session->data['success'] = $this->language->get('text_success');
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

            $this->response->redirect($this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function close() {
        $this->load->language('accounting/period');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('accounting/period');

        if (isset($this->request->get['period_id']) && $this->validateClose()) {
            if ($this->request->server['REQUEST_METHOD'] == 'POST') {
                $result = $this->model_accounting_period->closePeriod($this->request->get['period_id'], $this->request->post);
                
                if ($result) {
                    $this->session->data['success'] = $this->language->get('text_close_success');
                } else {
                    $this->error['warning'] = $this->language->get('error_close');
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

                $this->response->redirect($this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'] . $url, true));
            }
            
            $this->getCloseForm();
        } else {
            $this->getList();
        }
    }

    public function reopen() {
        $this->load->language('accounting/period');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('accounting/period');

        if (isset($this->request->get['period_id']) && $this->validateReopen()) {
            $result = $this->model_accounting_period->reopenPeriod($this->request->get['period_id']);
            
            if ($result) {
                $this->session->data['success'] = $this->language->get('text_reopen_success');
            } else {
                $this->error['warning'] = $this->language->get('error_reopen');
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

            $this->response->redirect($this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function lock() {
        $this->load->language('accounting/period');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('accounting/period');

        if (isset($this->request->get['period_id']) && $this->validateLock()) {
            $result = $this->model_accounting_period->lockPeriod($this->request->get['period_id']);
            
            if ($result) {
                $this->session->data['success'] = $this->language->get('text_lock_success');
            } else {
                $this->error['warning'] = $this->language->get('error_lock');
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

            $this->response->redirect($this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    protected function getList() {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'start_date';
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
            'href' => $this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('accounting/period/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('accounting/period/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['periods'] = array();

        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $period_total = $this->model_accounting_period->getTotalPeriods();

        $results = $this->model_accounting_period->getPeriods($filter_data);

        foreach ($results as $result) {
            $data['periods'][] = array(
                'period_id'    => $result['period_id'],
                'name'         => $result['name'],
                'description'  => $result['description'],
                'start_date'   => date($this->language->get('date_format_short'), strtotime($result['start_date'])),
                'end_date'     => date($this->language->get('date_format_short'), strtotime($result['end_date'])),
                'status'       => $this->getStatusText($result['status']),
                'status_id'    => $result['status'],
                'edit'         => $this->url->link('accounting/period/edit', 'user_token=' . $this->session->data['user_token'] . '&period_id=' . $result['period_id'] . $url, true),
                'close'        => $this->url->link('accounting/period/close', 'user_token=' . $this->session->data['user_token'] . '&period_id=' . $result['period_id'] . $url, true),
                'reopen'       => $this->url->link('accounting/period/reopen', 'user_token=' . $this->session->data['user_token'] . '&period_id=' . $result['period_id'] . $url, true),
                'lock'         => $this->url->link('accounting/period/lock', 'user_token=' . $this->session->data['user_token'] . '&period_id=' . $result['period_id'] . $url, true)
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

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_name'] = $this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
        $data['sort_start_date'] = $this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'] . '&sort=start_date' . $url, true);
        $data['sort_end_date'] = $this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'] . '&sort=end_date' . $url, true);
        $data['sort_status'] = $this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $period_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($period_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($period_total - $this->config->get('config_limit_admin'))) ? $period_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $period_total, ceil($period_total / $this->config->get('config_limit_admin')));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounting/period_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['period_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['name'])) {
            $data['error_name'] = $this->error['name'];
        } else {
            $data['error_name'] = '';
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

        if (isset($this->error['date_range'])) {
            $data['error_date_range'] = $this->error['date_range'];
        } else {
            $data['error_date_range'] = '';
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
            'href' => $this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['period_id'])) {
            $data['action'] = $this->url->link('accounting/period/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('accounting/period/edit', 'user_token=' . $this->session->data['user_token'] . '&period_id=' . $this->request->get['period_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['period_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $period_info = $this->model_accounting_period->getPeriod($this->request->get['period_id']);
        }

        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($period_info)) {
            $data['name'] = $period_info['name'];
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['description'])) {
            $data['description'] = $this->request->post['description'];
        } elseif (!empty($period_info)) {
            $data['description'] = $period_info['description'];
        } else {
            $data['description'] = '';
        }

        if (isset($this->request->post['start_date'])) {
            $data['start_date'] = $this->request->post['start_date'];
        } elseif (!empty($period_info)) {
            $data['start_date'] = $period_info['start_date'];
        } else {
            $data['start_date'] = '';
        }

        if (isset($this->request->post['end_date'])) {
            $data['end_date'] = $this->request->post['end_date'];
        } elseif (!empty($period_info)) {
            $data['end_date'] = $period_info['end_date'];
        } else {
            $data['end_date'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($period_info)) {
            $data['status'] = $period_info['status'];
        } else {
            $data['status'] = 0;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounting/period_form', $data));
    }

    protected function getCloseForm() {
        $this->load->language('accounting/period');

        $data['text_form'] = $this->language->get('text_close');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
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
            'href' => $this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_close'),
            'href' => $this->url->link('accounting/period/close', 'user_token=' . $this->session->data['user_token'] . '&period_id=' . $this->request->get['period_id'] . $url, true)
        );

        $data['action'] = $this->url->link('accounting/period/close', 'user_token=' . $this->session->data['user_token'] . '&period_id=' . $this->request->get['period_id'] . $url, true);

        $data['cancel'] = $this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $period_info = $this->model_accounting_period->getPeriod($this->request->get['period_id']);

        if ($period_info) {
            $data['period_id'] = $period_info['period_id'];
            $data['name'] = $period_info['name'];
            $data['start_date'] = date($this->language->get('date_format_short'), strtotime($period_info['start_date']));
            $data['end_date'] = date($this->language->get('date_format_short'), strtotime($period_info['end_date']));
        } else {
            $data['period_id'] = 0;
            $data['name'] = '';
            $data['start_date'] = '';
            $data['end_date'] = '';
        }

        if (isset($this->request->post['closing_notes'])) {
            $data['closing_notes'] = $this->request->post['closing_notes'];
        } else {
            $data['closing_notes'] = '';
        }

        if (isset($this->request->post['create_closing_entries'])) {
            $data['create_closing_entries'] = $this->request->post['create_closing_entries'];
        } else {
            $data['create_closing_entries'] = 1;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounting/period_close_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'accounting/period')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 128)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if (empty($this->request->post['start_date'])) {
            $this->error['start_date'] = $this->language->get('error_start_date');
        }

        if (empty($this->request->post['end_date'])) {
            $this->error['end_date'] = $this->language->get('error_end_date');
        }

        if (!empty($this->request->post['start_date']) && !empty($this->request->post['end_date'])) {
            if (strtotime($this->request->post['start_date']) > strtotime($this->request->post['end_date'])) {
                $this->error['date_range'] = $this->language->get('error_date_range');
            }
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'accounting/period')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function validateClose() {
        if (!$this->user->hasPermission('modify', 'accounting/period')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function validateReopen() {
        if (!$this->user->hasPermission('modify', 'accounting/period')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function validateLock() {
        if (!$this->user->hasPermission('modify', 'accounting/period')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    private function getStatusText($status) {
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
