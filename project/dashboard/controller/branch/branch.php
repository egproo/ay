<?php
class ControllerBranchBranch extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('branch/branch');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('branch/branch');
        $this->getList();
    }

    public function add() {
        $this->load->language('branch/branch');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('branch/branch');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
    
            // تحقق من وجود الفرع الرئيسي قبل السماح بإضافة فروع جديدة
            $main_branch = $this->model_branch_branch->getMainBranch();
            if (empty($main_branch)) {
                $this->session->data['error'] = $this->language->get('error_no_main_branch');
                $this->response->redirect($this->url->link('branch/branch', 'user_token=' . $this->session->data['user_token'], true));
            }else{

        if (empty($this->request->post['eta_branch_id'] || $this->request->post['eta_branch_id'] == '0') && $this->request->post['type'] == 'store') {
            $this->error['eta_branch_id'] = $this->language->get('error_eta_branch_id_required');
        } else {
            $this->model_branch_branch->addBranch($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('branch/branch', 'user_token=' . $this->session->data['user_token'], true));
        }
            
            }

        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('branch/branch');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('branch/branch');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_branch_branch->editBranch($this->request->get['branch_id'], $this->request->post);
            $this->response->redirect($this->url->link('branch/branch', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('branch/branch');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('branch/branch');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $branch_id) {
                $this->model_branch_branch->deleteBranch($branch_id);
            }
            $this->response->redirect($this->url->link('branch/branch', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getList();
    }

    protected function getList() {
        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = null;
        }

        if (isset($this->request->get['filter_type'])) {
            $filter_type = $this->request->get['filter_type'];
        } else {
            $filter_type = null;
        }

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}
		
        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_type'])) {
            $url .= '&filter_type=' . $this->request->get['filter_type'];
        }

        $data['branches'] = array();

        $filter_data = array(
            'filter_name'  => $filter_name,
            'filter_type'  => $filter_type,
            'start'        => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'        => $this->config->get('config_limit_admin')
        );

        $branch_total = $this->model_branch_branch->getTotalBranches($filter_data);
        $results = $this->model_branch_branch->getBranches($filter_data);

        foreach ($results as $result) {
            $data['branches'][] = array(
                'branch_id' => $result['branch_id'],
                'name'      => $result['name'],
                'type'      => $result['type'],
                'telephone' => $result['telephone'],
                'email'     => $result['email'],
                'address'   => $result['address_2'] . ', ' . $result['address_1'] . ', ' . $result['city'] . ', ' . $result['zone'],
                'eta_branch_id' => $result['eta_branch_id'],
                'edit'      => $this->url->link('branch/branch/edit', 'user_token=' . $this->session->data['user_token'] . '&branch_id=' . $result['branch_id'] . $url, true),
                'delete'    => $this->url->link('branch/branch/delete', 'user_token=' . $this->session->data['user_token'] . '&branch_id=' . $result['branch_id'] . $url, true)
            );
        }

        $data['add'] = $this->url->link('branch/branch/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('branch/branch/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['setting'] = $this->url->link('setting/setting', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['user_token'] = $this->session->data['user_token'];


        $pagination = new Pagination();
        $pagination->total = $branch_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('branch/branch', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($branch_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($branch_total - $this->config->get('config_limit_admin'))) ? $branch_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $branch_total, ceil($branch_total / $this->config->get('config_limit_admin')));

        $data['filter_name'] = $filter_name;
        $data['filter_type'] = $filter_type;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('branch/branch_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['branch_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->get['branch_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $branch_info = $this->model_branch_branch->getBranch($this->request->get['branch_id']);
        }

        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($branch_info)) {
            $data['name'] = $branch_info['name'];
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['type'])) {
            $data['type'] = $this->request->post['type'];
        } elseif (!empty($branch_info)) {
            $data['type'] = $branch_info['type'];
        } else {
            $data['type'] = 'store';
        }
		$data['user_token'] = $this->session->data['user_token'];

    if (isset($this->request->post['available_online'])) {
        $data['available_online'] = $this->request->post['available_online'];
    } elseif (!empty($branch_info)) {
        $data['available_online'] = $branch_info['available_online'];
    } else {
        $data['available_online'] = 0;
    }
    
        if (isset($this->request->post['telephone'])) {
            $data['telephone'] = $this->request->post['telephone'];
        } elseif (!empty($branch_info)) {
            $data['telephone'] = $branch_info['telephone'];
        } else {
            $data['telephone'] = '';
        }

        if (isset($this->request->post['email'])) {
            $data['email'] = $this->request->post['email'];
        } elseif (!empty($branch_info)) {
            $data['email'] = $branch_info['email'];
        } else {
            $data['email'] = '';
        }		



        if (isset($this->request->post['eta_branch_id'])) {
            $data['eta_branch_id'] = $this->request->post['eta_branch_id'];
        } elseif (!empty($branch_info)) {
            $data['eta_branch_id'] = $branch_info['eta_branch_id'];
        } else {
            $data['eta_branch_id'] = '';
        }

        // Address Fields
        if (isset($this->request->post['address_1'])) {
            $data['address_1'] = $this->request->post['address_1'];
        } elseif (!empty($branch_info)) {
            $data['address_1'] = $branch_info['address_1'];
        } else {
            $data['address_1'] = '';
        }

        if (isset($this->request->post['address_2'])) {
            $data['address_2'] = $this->request->post['address_2'];
        } elseif (!empty($branch_info)) {
            $data['address_2'] = $branch_info['address_2'];
        } else {
            $data['address_2'] = '';
        }

        if (isset($this->request->post['city'])) {
            $data['city'] = $this->request->post['city'];
        } elseif (!empty($branch_info)) {
            $data['city'] = $branch_info['city'];
        } else {
            $data['city'] = '';
        }

        if (isset($this->request->post['postcode'])) {
            $data['postcode'] = $this->request->post['postcode'];
        } elseif (!empty($branch_info)) {
            $data['postcode'] = $branch_info['postcode'];
        } else {
            $data['postcode'] = '';
        }

        if (isset($this->request->post['country_id'])) {
            $data['country_id'] = $this->request->post['country_id'];
        } elseif (!empty($branch_info)) {
            $data['country_id'] = $branch_info['country_id'];
        } else {
            $data['country_id'] = 63;
        }

        if (isset($this->request->post['zone_id'])) {
            $data['zone_id'] = $this->request->post['zone_id'];
        } elseif (!empty($branch_info)) {
            $data['zone_id'] = $branch_info['zone_id'];
        } else {
            $data['zone_id'] = '';
        }

        // Load countries and zones
        $this->load->model('localisation/country');
        $data['countries'] = $this->model_localisation_country->getCountries();

        $this->load->model('localisation/zone');
        if (!empty($data['country_id'])) {
            $data['zones'] = $this->model_localisation_zone->getZonesByCountryId($data['country_id']);
        } else {
            $data['zones'] = $this->model_localisation_zone->getZonesByCountryId(63);
        }

        // Load users for branch manager
        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();

        if (isset($this->request->post['manager_id'])) {
            $data['manager_id'] = $this->request->post['manager_id'];
        } elseif (!empty($branch_info)) {
            $data['manager_id'] = $branch_info['manager_id'];
        } else {
            $data['manager_id'] = '';
        }



        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('branch/branch', 'user_token=' . $this->session->data['user_token'], true)
        );

        if (!isset($this->request->get['branch_id'])) {
            $data['action'] = $this->url->link('branch/branch/add', 'user_token=' . $this->session->data['user_token'], true);
        } else {
            $data['action'] = $this->url->link('branch/branch/edit', 'user_token=' . $this->session->data['user_token'] . '&branch_id=' . $this->request->get['branch_id'], true);
        }

        $data['cancel'] = $this->url->link('branch/branch', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('branch/branch_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'branch/branch')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 255)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'branch/branch')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
