<?php
/**
 * تحكم إدارة الأصول الثابتة المحسن
 * يدعم إدارة الأصول والاستهلاك والتقييم والتكامل المحاسبي
 */
class ControllerAssetsFixedAssets extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('assets/fixed_assets');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('assets/fixed_assets');
        $this->getList();
    }

    public function add() {
        $this->load->language('assets/fixed_assets');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('assets/fixed_assets');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $asset_id = $this->model_assets_fixed_assets->addFixedAsset($this->request->post);
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

            $this->response->redirect($this->url->link('assets/fixed_assets', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('assets/fixed_assets');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('assets/fixed_assets');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_assets_fixed_assets->editFixedAsset($this->request->get['asset_id'], $this->request->post);
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

            $this->response->redirect($this->url->link('assets/fixed_assets', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('assets/fixed_assets');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('assets/fixed_assets');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $asset_id) {
                $this->model_assets_fixed_assets->deleteFixedAsset($asset_id);
            }

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

            $this->response->redirect($this->url->link('assets/fixed_assets', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function depreciation() {
        $this->load->language('assets/fixed_assets');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('assets/fixed_assets');

        $this->getDepreciation();
    }

    public function calculateDepreciation() {
        $this->load->language('assets/fixed_assets');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('assets/fixed_assets');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateDepreciation()) {
            $result = $this->model_assets_fixed_assets->calculateMonthlyDepreciation($this->request->post);

            if ($result['success']) {
                $this->session->data['success'] = $this->language->get('text_success_depreciation');
            } else {
                $this->session->data['error'] = $result['error'];
            }

            $this->response->redirect($this->url->link('assets/fixed_assets/depreciation', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getDepreciationForm();
    }

    public function disposal() {
        $this->load->language('assets/fixed_assets');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('assets/fixed_assets');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateDisposal()) {
            $result = $this->model_assets_fixed_assets->disposeAsset($this->request->post);

            if ($result['success']) {
                $this->session->data['success'] = $this->language->get('text_success_disposal');
            } else {
                $this->session->data['error'] = $result['error'];
            }

            $this->response->redirect($this->url->link('assets/fixed_assets', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getDisposalForm();
    }

    protected function getList() {
        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = '';
        }

        if (isset($this->request->get['filter_category'])) {
            $filter_category = $this->request->get['filter_category'];
        } else {
            $filter_category = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'name';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('assets/fixed_assets', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['add'] = $this->url->link('assets/fixed_assets/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('assets/fixed_assets/delete', 'user_token=' . $this->session->data['user_token'], true);
        $data['depreciation'] = $this->url->link('assets/fixed_assets/depreciation', 'user_token=' . $this->session->data['user_token'], true);

        $data['assets'] = array();

        $filter_data = array(
            'filter_name' => $filter_name,
            'filter_category' => $filter_category,
            'filter_status' => $filter_status,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $asset_total = $this->model_assets_fixed_assets->getTotalFixedAssets($filter_data);
        $results = $this->model_assets_fixed_assets->getFixedAssets($filter_data);

        foreach ($results as $result) {
            $data['assets'][] = array(
                'asset_id' => $result['asset_id'],
                'name' => $result['name'],
                'asset_code' => $result['asset_code'],
                'category_name' => $result['category_name'],
                'purchase_cost' => $this->currency->format($result['purchase_cost'], $this->config->get('config_currency')),
                'current_value' => $this->currency->format($result['current_value'], $this->config->get('config_currency')),
                'accumulated_depreciation' => $this->currency->format($result['accumulated_depreciation'], $this->config->get('config_currency')),
                'status' => $result['status'],
                'edit' => $this->url->link('assets/fixed_assets/edit', 'user_token=' . $this->session->data['user_token'] . '&asset_id=' . $result['asset_id'], true),
                'disposal' => $this->url->link('assets/fixed_assets/disposal', 'user_token=' . $this->session->data['user_token'] . '&asset_id=' . $result['asset_id'], true)
            );
        }

        // إضافة باقي بيانات العرض
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

        $this->response->setOutput($this->load->view('assets/fixed_assets_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['asset_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('assets/fixed_assets', 'user_token=' . $this->session->data['user_token'], true)
        );

        if (!isset($this->request->get['asset_id'])) {
            $data['action'] = $this->url->link('assets/fixed_assets/add', 'user_token=' . $this->session->data['user_token'], true);
        } else {
            $data['action'] = $this->url->link('assets/fixed_assets/edit', 'user_token=' . $this->session->data['user_token'] . '&asset_id=' . $this->request->get['asset_id'], true);
        }

        $data['cancel'] = $this->url->link('assets/fixed_assets', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->get['asset_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $asset_info = $this->model_assets_fixed_assets->getFixedAsset($this->request->get['asset_id']);
        }

        // الحصول على فئات الأصول
        $data['categories'] = $this->model_assets_fixed_assets->getAssetCategories();

        // الحصول على الحسابات المحاسبية
        $this->load->model('accounts/chartaccount');
        $data['accounts'] = $this->model_accounts_chartaccount->getAccounts(array('filter_type' => 'asset'));

        // بيانات النموذج
        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($asset_info)) {
            $data['name'] = $asset_info['name'];
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['asset_code'])) {
            $data['asset_code'] = $this->request->post['asset_code'];
        } elseif (!empty($asset_info)) {
            $data['asset_code'] = $asset_info['asset_code'];
        } else {
            $data['asset_code'] = '';
        }

        if (isset($this->request->post['category_id'])) {
            $data['category_id'] = $this->request->post['category_id'];
        } elseif (!empty($asset_info)) {
            $data['category_id'] = $asset_info['category_id'];
        } else {
            $data['category_id'] = '';
        }

        if (isset($this->request->post['purchase_cost'])) {
            $data['purchase_cost'] = $this->request->post['purchase_cost'];
        } elseif (!empty($asset_info)) {
            $data['purchase_cost'] = $asset_info['purchase_cost'];
        } else {
            $data['purchase_cost'] = '';
        }

        if (isset($this->request->post['purchase_date'])) {
            $data['purchase_date'] = $this->request->post['purchase_date'];
        } elseif (!empty($asset_info)) {
            $data['purchase_date'] = $asset_info['purchase_date'];
        } else {
            $data['purchase_date'] = date('Y-m-d');
        }

        if (isset($this->request->post['useful_life'])) {
            $data['useful_life'] = $this->request->post['useful_life'];
        } elseif (!empty($asset_info)) {
            $data['useful_life'] = $asset_info['useful_life'];
        } else {
            $data['useful_life'] = '';
        }

        if (isset($this->request->post['depreciation_method'])) {
            $data['depreciation_method'] = $this->request->post['depreciation_method'];
        } elseif (!empty($asset_info)) {
            $data['depreciation_method'] = $asset_info['depreciation_method'];
        } else {
            $data['depreciation_method'] = 'straight_line';
        }

        if (isset($this->request->post['salvage_value'])) {
            $data['salvage_value'] = $this->request->post['salvage_value'];
        } elseif (!empty($asset_info)) {
            $data['salvage_value'] = $asset_info['salvage_value'];
        } else {
            $data['salvage_value'] = '';
        }

        if (isset($this->request->post['asset_account_id'])) {
            $data['asset_account_id'] = $this->request->post['asset_account_id'];
        } elseif (!empty($asset_info)) {
            $data['asset_account_id'] = $asset_info['asset_account_id'];
        } else {
            $data['asset_account_id'] = '';
        }

        if (isset($this->request->post['depreciation_account_id'])) {
            $data['depreciation_account_id'] = $this->request->post['depreciation_account_id'];
        } elseif (!empty($asset_info)) {
            $data['depreciation_account_id'] = $asset_info['depreciation_account_id'];
        } else {
            $data['depreciation_account_id'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($asset_info)) {
            $data['status'] = $asset_info['status'];
        } else {
            $data['status'] = 'active';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('assets/fixed_assets_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'assets/fixed_assets')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if (empty($this->request->post['asset_code'])) {
            $this->error['asset_code'] = $this->language->get('error_asset_code');
        }

        if (empty($this->request->post['category_id'])) {
            $this->error['category_id'] = $this->language->get('error_category');
        }

        if (empty($this->request->post['purchase_cost']) || !is_numeric($this->request->post['purchase_cost'])) {
            $this->error['purchase_cost'] = $this->language->get('error_purchase_cost');
        }

        if (empty($this->request->post['purchase_date'])) {
            $this->error['purchase_date'] = $this->language->get('error_purchase_date');
        }

        if (empty($this->request->post['useful_life']) || !is_numeric($this->request->post['useful_life'])) {
            $this->error['useful_life'] = $this->language->get('error_useful_life');
        }

        if (empty($this->request->post['asset_account_id'])) {
            $this->error['asset_account_id'] = $this->language->get('error_asset_account');
        }

        if (empty($this->request->post['depreciation_account_id'])) {
            $this->error['depreciation_account_id'] = $this->language->get('error_depreciation_account');
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'assets/fixed_assets')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function validateDepreciation() {
        if (!$this->user->hasPermission('modify', 'assets/fixed_assets')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['depreciation_date'])) {
            $this->error['depreciation_date'] = $this->language->get('error_depreciation_date');
        }

        return !$this->error;
    }

    protected function validateDisposal() {
        if (!$this->user->hasPermission('modify', 'assets/fixed_assets')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['asset_id'])) {
            $this->error['asset_id'] = $this->language->get('error_asset');
        }

        if (empty($this->request->post['disposal_date'])) {
            $this->error['disposal_date'] = $this->language->get('error_disposal_date');
        }

        if (!isset($this->request->post['disposal_amount']) || !is_numeric($this->request->post['disposal_amount'])) {
            $this->error['disposal_amount'] = $this->language->get('error_disposal_amount');
        }

        return !$this->error;
    }
}
