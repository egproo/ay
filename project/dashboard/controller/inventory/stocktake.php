<?php
/**
 * إدارة الجرد الدوري والسنوي
 * يستخدم لإدارة عمليات جرد المخزون والتحقق من دقة بيانات المخزون
 */
class ControllerInventoryStocktake extends Controller {
    private $error = array();

    /**
     * عرض صفحة الجرد
     */
    public function index() {
        $this->load->language('inventory/stocktake');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/stocktake');

        $this->getList();
    }

    /**
     * عرض قائمة عمليات الجرد
     */
    protected function getList() {
        if (isset($this->request->get['filter_reference'])) {
            $filter_reference = $this->request->get['filter_reference'];
        } else {
            $filter_reference = '';
        }

        if (isset($this->request->get['filter_branch'])) {
            $filter_branch = $this->request->get['filter_branch'];
        } else {
            $filter_branch = '';
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

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['filter_type'])) {
            $filter_type = $this->request->get['filter_type'];
        } else {
            $filter_type = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 's.date_added';
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

        if (isset($this->request->get['filter_reference'])) {
            $url .= '&filter_reference=' . urlencode($this->request->get['filter_reference']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_date_from'])) {
            $url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
        }

        if (isset($this->request->get['filter_date_to'])) {
            $url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_type'])) {
            $url .= '&filter_type=' . $this->request->get['filter_type'];
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
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . $url)
        );

        $data['add'] = $this->url->link('inventory/stocktake/add', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['delete'] = $this->url->link('inventory/stocktake/delete', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['export'] = $this->url->link('inventory/stocktake/export', 'user_token=' . $this->session->data['user_token'] . $url);

        $filter_data = array(
            'filter_reference'    => $filter_reference,
            'filter_branch'       => $filter_branch,
            'filter_date_from'    => $filter_date_from,
            'filter_date_to'      => $filter_date_to,
            'filter_status'       => $filter_status,
            'filter_type'         => $filter_type,
            'sort'                => $sort,
            'order'               => $order,
            'start'               => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'               => $this->config->get('config_limit_admin')
        );

        $stocktake_total = $this->model_inventory_stocktake->getTotalStocktakes($filter_data);
        $stocktakes = $this->model_inventory_stocktake->getStocktakes($filter_data);

        $data['stocktakes'] = array();

        foreach ($stocktakes as $stocktake) {
            $data['stocktakes'][] = array(
                'stocktake_id'    => $stocktake['stocktake_id'],
                'reference'       => $stocktake['reference'],
                'branch_name'     => $stocktake['branch_name'],
                'stocktake_date'  => date($this->language->get('date_format_short'), strtotime($stocktake['stocktake_date'])),
                'type'            => $stocktake['type'],
                'type_text'       => $this->language->get('text_type_' . $stocktake['type']),
                'total_items'     => $stocktake['total_items'],
                'status'          => $stocktake['status'],
                'status_text'     => $this->language->get('text_status_' . $stocktake['status']),
                'created_by_name' => $stocktake['created_by_name'],
                'date_added'      => date($this->language->get('date_format_short'), strtotime($stocktake['date_added'])),
                'view'            => $this->url->link('inventory/stocktake/view', 'user_token=' . $this->session->data['user_token'] . '&stocktake_id=' . $stocktake['stocktake_id'] . $url),
                'edit'            => $this->url->link('inventory/stocktake/edit', 'user_token=' . $this->session->data['user_token'] . '&stocktake_id=' . $stocktake['stocktake_id'] . $url),
                'print'           => $this->url->link('inventory/stocktake/print', 'user_token=' . $this->session->data['user_token'] . '&stocktake_id=' . $stocktake['stocktake_id'] . $url),
                'complete'        => $this->url->link('inventory/stocktake/complete', 'user_token=' . $this->session->data['user_token'] . '&stocktake_id=' . $stocktake['stocktake_id'] . $url),
                'cancel'          => $this->url->link('inventory/stocktake/cancel', 'user_token=' . $this->session->data['user_token'] . '&stocktake_id=' . $stocktake['stocktake_id'] . $url)
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

        if (isset($this->request->get['filter_reference'])) {
            $url .= '&filter_reference=' . urlencode($this->request->get['filter_reference']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_date_from'])) {
            $url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
        }

        if (isset($this->request->get['filter_date_to'])) {
            $url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_type'])) {
            $url .= '&filter_type=' . $this->request->get['filter_type'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_reference'] = $this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . '&sort=s.reference' . $url);
        $data['sort_branch'] = $this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . '&sort=b.name' . $url);
        $data['sort_date'] = $this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . '&sort=s.stocktake_date' . $url);
        $data['sort_type'] = $this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . '&sort=s.type' . $url);
        $data['sort_status'] = $this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . '&sort=s.status' . $url);
        $data['sort_date_added'] = $this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . '&sort=s.date_added' . $url);

        $url = '';

        if (isset($this->request->get['filter_reference'])) {
            $url .= '&filter_reference=' . urlencode($this->request->get['filter_reference']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_date_from'])) {
            $url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
        }

        if (isset($this->request->get['filter_date_to'])) {
            $url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_type'])) {
            $url .= '&filter_type=' . $this->request->get['filter_type'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $stocktake_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}');

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($stocktake_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($stocktake_total - $this->config->get('config_limit_admin'))) ? $stocktake_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $stocktake_total, ceil($stocktake_total / $this->config->get('config_limit_admin')));

        $data['filter_reference'] = $filter_reference;
        $data['filter_branch'] = $filter_branch;
        $data['filter_date_from'] = $filter_date_from;
        $data['filter_date_to'] = $filter_date_to;
        $data['filter_status'] = $filter_status;
        $data['filter_type'] = $filter_type;

        $this->load->model('branch/branch');
        $data['branches'] = $this->model_branch_branch->getBranches();

        $data['stocktake_types'] = array(
            '' => $this->language->get('text_all_types'),
            'full' => $this->language->get('text_type_full'),
            'partial' => $this->language->get('text_type_partial'),
            'spot' => $this->language->get('text_type_spot'),
            'cycle' => $this->language->get('text_type_cycle')
        );

        $data['stocktake_statuses'] = array(
            '' => $this->language->get('text_all_status'),
            'draft' => $this->language->get('text_status_draft'),
            'in_progress' => $this->language->get('text_status_in_progress'),
            'completed' => $this->language->get('text_status_completed'),
            'cancelled' => $this->language->get('text_status_cancelled')
        );

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/stocktake_list', $data));
    }

    /**
     * إضافة عملية جرد جديدة
     */
    public function add() {
        $this->load->language('inventory/stocktake');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/stocktake');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $stocktake_id = $this->model_inventory_stocktake->addStocktake($this->request->post);

            // إضافة إشعار
            $this->load->model('notification/notification');
            $this->model_notification_notification->addNotification([
                'type' => 'stocktake_created',
                'title' => $this->language->get('text_stocktake_created'),
                'message' => sprintf($this->language->get('text_stocktake_created_message'), $this->request->post['reference']),
                'reference_type' => 'stocktake',
                'reference_id' => $stocktake_id,
                'user_id' => $this->user->getId(),
                'status' => 1
            ]);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_reference'])) {
                $url .= '&filter_reference=' . urlencode($this->request->get['filter_reference']);
            }

            if (isset($this->request->get['filter_branch'])) {
                $url .= '&filter_branch=' . $this->request->get['filter_branch'];
            }

            if (isset($this->request->get['filter_date_from'])) {
                $url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
            }

            if (isset($this->request->get['filter_date_to'])) {
                $url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_type'])) {
                $url .= '&filter_type=' . $this->request->get['filter_type'];
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

            $this->response->redirect($this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . $url));
        }

        $this->getForm();
    }

    /**
     * تعديل عملية جرد
     */
    public function edit() {
        $this->load->language('inventory/stocktake');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/stocktake');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $stocktake_id = $this->request->get['stocktake_id'];
            $this->model_inventory_stocktake->editStocktake($stocktake_id, $this->request->post);

            // إضافة إشعار
            $this->load->model('notification/notification');
            $this->model_notification_notification->addNotification([
                'type' => 'stocktake_updated',
                'title' => $this->language->get('text_stocktake_updated'),
                'message' => sprintf($this->language->get('text_stocktake_updated_message'), $this->request->post['reference']),
                'reference_type' => 'stocktake',
                'reference_id' => $stocktake_id,
                'user_id' => $this->user->getId(),
                'status' => 1
            ]);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_reference'])) {
                $url .= '&filter_reference=' . urlencode($this->request->get['filter_reference']);
            }

            if (isset($this->request->get['filter_branch'])) {
                $url .= '&filter_branch=' . $this->request->get['filter_branch'];
            }

            if (isset($this->request->get['filter_date_from'])) {
                $url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
            }

            if (isset($this->request->get['filter_date_to'])) {
                $url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_type'])) {
                $url .= '&filter_type=' . $this->request->get['filter_type'];
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

            $this->response->redirect($this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . $url));
        }

        $this->getForm();
    }

    /**
     * عرض تفاصيل عملية الجرد
     */
    public function view() {
        $this->load->language('inventory/stocktake');
        $this->document->setTitle($this->language->get('heading_stocktake_view'));
        $this->load->model('inventory/stocktake');

        if (isset($this->request->get['stocktake_id'])) {
            $stocktake_id = $this->request->get['stocktake_id'];
        } else {
            $stocktake_id = 0;
        }

        $stocktake_info = $this->model_inventory_stocktake->getStocktake($stocktake_id);

        if (!$stocktake_info) {
            $this->response->redirect($this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token']));
        }

        $url = '';

        if (isset($this->request->get['filter_reference'])) {
            $url .= '&filter_reference=' . urlencode($this->request->get['filter_reference']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_date_from'])) {
            $url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
        }

        if (isset($this->request->get['filter_date_to'])) {
            $url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_type'])) {
            $url .= '&filter_type=' . $this->request->get['filter_type'];
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
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . $url)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_stocktake_view'),
            'href' => $this->url->link('inventory/stocktake/view', 'user_token=' . $this->session->data['user_token'] . '&stocktake_id=' . $stocktake_id . $url)
        );

        $data['back'] = $this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['print'] = $this->url->link('inventory/stocktake/print', 'user_token=' . $this->session->data['user_token'] . '&stocktake_id=' . $stocktake_id . $url);
        $data['export'] = $this->url->link('inventory/stocktake/export', 'user_token=' . $this->session->data['user_token'] . '&stocktake_id=' . $stocktake_id . $url);

        if ($stocktake_info['status'] == 'in_progress') {
            $data['complete'] = $this->url->link('inventory/stocktake/complete', 'user_token=' . $this->session->data['user_token'] . '&stocktake_id=' . $stocktake_id . $url);
        }

        if ($stocktake_info['status'] == 'draft' || $stocktake_info['status'] == 'in_progress') {
            $data['cancel'] = $this->url->link('inventory/stocktake/cancel', 'user_token=' . $this->session->data['user_token'] . '&stocktake_id=' . $stocktake_id . $url);
        }

        $data['reference'] = $stocktake_info['reference'];
        $data['branch_name'] = $stocktake_info['branch_name'];
        $data['stocktake_date'] = date($this->language->get('date_format_short'), strtotime($stocktake_info['stocktake_date']));
        $data['type'] = $stocktake_info['type'];
        $data['type_text'] = $this->language->get('text_type_' . $stocktake_info['type']);
        $data['status'] = $stocktake_info['status'];
        $data['status_text'] = $this->language->get('text_status_' . $stocktake_info['status']);
        $data['notes'] = $stocktake_info['notes'];
        $data['created_by_name'] = $stocktake_info['created_by_name'];
        $data['date_added'] = date($this->language->get('date_format_short'), strtotime($stocktake_info['date_added']));

        if ($stocktake_info['status'] == 'completed') {
            $data['completed_by_name'] = $stocktake_info['completed_by_name'];
            $data['date_completed'] = date($this->language->get('date_format_short'), strtotime($stocktake_info['date_completed']));
        }

        // الحصول على منتجات الجرد
        $stocktake_products = $this->model_inventory_stocktake->getStocktakeProducts($stocktake_id);

        $data['products'] = array();
        $data['total_products'] = count($stocktake_products);
        $data['total_expected'] = 0;
        $data['total_counted'] = 0;
        $data['total_variance'] = 0;

        foreach ($stocktake_products as $product) {
            $variance_quantity = $product['counted_quantity'] - $product['expected_quantity'];
            $variance_percentage = ($product['expected_quantity'] > 0) ? ($variance_quantity / $product['expected_quantity']) * 100 : 0;

            $data['products'][] = array(
                'product_name'         => $product['product_name'],
                'model'                => $product['model'],
                'sku'                  => $product['sku'],
                'unit_name'            => $product['unit_name'],
                'expected_quantity'    => number_format($product['expected_quantity'], 4, '.', ','),
                'counted_quantity'     => number_format($product['counted_quantity'], 4, '.', ','),
                'variance_quantity'    => number_format($variance_quantity, 4, '.', ','),
                'variance_percentage'  => number_format($variance_percentage, 2, '.', ','),
                'notes'                => $product['notes']
            );

            $data['total_expected'] += $product['expected_quantity'];
            $data['total_counted'] += $product['counted_quantity'];
            $data['total_variance'] += $variance_quantity;
        }

        $data['total_expected'] = number_format($data['total_expected'], 4, '.', ',');
        $data['total_counted'] = number_format($data['total_counted'], 4, '.', ',');
        $data['total_variance'] = number_format($data['total_variance'], 4, '.', ',');

        // حساب نسبة الفرق الإجمالية
        $total_expected = (float)str_replace(',', '', $data['total_expected']);
        $total_variance = (float)str_replace(',', '', $data['total_variance']);
        $data['variance_percentage'] = ($total_expected > 0) ? number_format(($total_variance / $total_expected) * 100, 2, '.', ',') : 0;

        // حساب قيمة الفرق (يمكن تنفيذه لاحقًا بعد ربطه بتكلفة المنتجات)
        $data['variance_value'] = number_format(0, 2, '.', ',');

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/stocktake_view', $data));
    }

    /**
     * طباعة عملية الجرد
     */
    public function print() {
        $this->load->language('inventory/stocktake');
        $this->document->setTitle($this->language->get('heading_stocktake_view'));
        $this->load->model('inventory/stocktake');

        if (isset($this->request->get['stocktake_id'])) {
            $stocktake_id = $this->request->get['stocktake_id'];
        } else {
            $stocktake_id = 0;
        }

        $stocktake_info = $this->model_inventory_stocktake->getStocktake($stocktake_id);

        if (!$stocktake_info) {
            $this->response->redirect($this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token']));
        }

        $data['title'] = $this->language->get('heading_stocktake_view');

        $data['base'] = HTTP_SERVER;
        $data['direction'] = $this->language->get('direction');
        $data['lang'] = $this->language->get('code');

        $this->load->model('setting/setting');
        $data['config_company_name'] = $this->config->get('config_name');
        $data['config_address'] = $this->config->get('config_address');
        $data['config_email'] = $this->config->get('config_email');
        $data['config_telephone'] = $this->config->get('config_telephone');

        $data['reference'] = $stocktake_info['reference'];
        $data['branch_name'] = $stocktake_info['branch_name'];
        $data['stocktake_date'] = date($this->language->get('date_format_short'), strtotime($stocktake_info['stocktake_date']));
        $data['type'] = $stocktake_info['type'];
        $data['type_text'] = $this->language->get('text_type_' . $stocktake_info['type']);
        $data['status'] = $stocktake_info['status'];
        $data['status_text'] = $this->language->get('text_status_' . $stocktake_info['status']);
        $data['notes'] = $stocktake_info['notes'];
        $data['created_by_name'] = $stocktake_info['created_by_name'];
        $data['date_added'] = date($this->language->get('date_format_short'), strtotime($stocktake_info['date_added']));

        if ($stocktake_info['status'] == 'completed') {
            $data['completed_by_name'] = $stocktake_info['completed_by_name'];
            $data['date_completed'] = date($this->language->get('date_format_short'), strtotime($stocktake_info['date_completed']));
        }

        // الحصول على منتجات الجرد
        $stocktake_products = $this->model_inventory_stocktake->getStocktakeProducts($stocktake_id);

        $data['products'] = array();
        $data['total_products'] = count($stocktake_products);
        $data['total_expected'] = 0;
        $data['total_counted'] = 0;
        $data['total_variance'] = 0;

        foreach ($stocktake_products as $product) {
            $variance_quantity = $product['counted_quantity'] - $product['expected_quantity'];
            $variance_percentage = ($product['expected_quantity'] > 0) ? ($variance_quantity / $product['expected_quantity']) * 100 : 0;

            $data['products'][] = array(
                'product_name'         => $product['product_name'],
                'model'                => $product['model'],
                'sku'                  => $product['sku'],
                'unit_name'            => $product['unit_name'],
                'expected_quantity'    => number_format($product['expected_quantity'], 4, '.', ','),
                'counted_quantity'     => number_format($product['counted_quantity'], 4, '.', ','),
                'variance_quantity'    => number_format($variance_quantity, 4, '.', ','),
                'variance_percentage'  => number_format($variance_percentage, 2, '.', ','),
                'notes'                => $product['notes']
            );

            $data['total_expected'] += $product['expected_quantity'];
            $data['total_counted'] += $product['counted_quantity'];
            $data['total_variance'] += $variance_quantity;
        }

        $data['total_expected'] = number_format($data['total_expected'], 4, '.', ',');
        $data['total_counted'] = number_format($data['total_counted'], 4, '.', ',');
        $data['total_variance'] = number_format($data['total_variance'], 4, '.', ',');

        // حساب نسبة الفرق الإجمالية
        $total_expected = (float)str_replace(',', '', $data['total_expected']);
        $total_variance = (float)str_replace(',', '', $data['total_variance']);
        $data['variance_percentage'] = ($total_expected > 0) ? number_format(($total_variance / $total_expected) * 100, 2, '.', ',') : 0;

        // حساب قيمة الفرق (يمكن تنفيذه لاحقًا بعد ربطه بتكلفة المنتجات)
        $data['variance_value'] = number_format(0, 2, '.', ',');

        // ترجمة العناوين
        $data['text_stocktake_details'] = $this->language->get('text_stocktake_details');
        $data['text_stocktake_products'] = $this->language->get('text_stocktake_products');
        $data['text_stocktake_summary'] = $this->language->get('text_stocktake_summary');
        $data['text_total_products'] = $this->language->get('text_total_products');
        $data['text_total_expected'] = $this->language->get('text_total_expected');
        $data['text_total_counted'] = $this->language->get('text_total_counted');
        $data['text_total_variance'] = $this->language->get('text_total_variance');
        $data['text_variance_percentage'] = $this->language->get('text_variance_percentage');
        $data['text_variance_value'] = $this->language->get('text_variance_value');
        $data['text_created_by'] = $this->language->get('text_created_by');
        $data['text_completed_by'] = $this->language->get('text_completed_by');
        $data['text_date_created'] = $this->language->get('text_date_created');
        $data['text_date_completed'] = $this->language->get('text_date_completed');

        $data['entry_reference'] = $this->language->get('entry_reference');
        $data['entry_branch'] = $this->language->get('entry_branch');
        $data['entry_stocktake_date'] = $this->language->get('entry_stocktake_date');
        $data['entry_type'] = $this->language->get('entry_type');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_notes'] = $this->language->get('entry_notes');

        $data['column_product'] = $this->language->get('column_product');
        $data['column_model'] = $this->language->get('column_model');
        $data['column_sku'] = $this->language->get('column_sku');
        $data['column_unit'] = $this->language->get('column_unit');
        $data['column_expected_quantity'] = $this->language->get('column_expected_quantity');
        $data['column_counted_quantity'] = $this->language->get('column_counted_quantity');
        $data['column_variance_quantity'] = $this->language->get('column_variance_quantity');
        $data['column_variance_percentage'] = $this->language->get('column_variance_percentage');
        $data['column_notes'] = $this->language->get('column_notes');

        $this->response->setOutput($this->load->view('inventory/stocktake_print', $data));
    }

    /**
     * إكمال عملية الجرد
     */
    public function complete() {
        $this->load->language('inventory/stocktake');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/stocktake');

        if (!$this->user->hasPermission('modify', 'inventory/stocktake')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->get['stocktake_id'])) {
            $stocktake_id = $this->request->get['stocktake_id'];
        } else {
            $stocktake_id = 0;
        }

        $stocktake_info = $this->model_inventory_stocktake->getStocktake($stocktake_id);

        if (!$stocktake_info) {
            $this->session->data['error'] = $this->language->get('error_stocktake_id');
            $this->response->redirect($this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token']));
        }

        if ($stocktake_info['status'] != 'in_progress') {
            $this->session->data['error'] = $this->language->get('error_stocktake_status');
            $this->response->redirect($this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token']));
        }

        // إكمال عملية الجرد
        $this->model_inventory_stocktake->completeStocktake($stocktake_id, array());

        // إضافة إشعار
        $this->load->model('notification/notification');
        $this->model_notification_notification->addNotification([
            'type' => 'stocktake_completed',
            'title' => $this->language->get('text_stocktake_completed'),
            'message' => sprintf($this->language->get('text_stocktake_completed_message'), $stocktake_info['reference']),
            'reference_type' => 'stocktake',
            'reference_id' => $stocktake_id,
            'user_id' => $this->user->getId(),
            'status' => 1
        ]);

        $this->session->data['success'] = $this->language->get('text_complete_success');

        $url = '';

        if (isset($this->request->get['filter_reference'])) {
            $url .= '&filter_reference=' . urlencode($this->request->get['filter_reference']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_date_from'])) {
            $url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
        }

        if (isset($this->request->get['filter_date_to'])) {
            $url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_type'])) {
            $url .= '&filter_type=' . $this->request->get['filter_type'];
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

        $this->response->redirect($this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . $url));
    }

    /**
     * إلغاء عملية الجرد
     */
    public function cancel() {
        $this->load->language('inventory/stocktake');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/stocktake');

        if (!$this->user->hasPermission('modify', 'inventory/stocktake')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->get['stocktake_id'])) {
            $stocktake_id = $this->request->get['stocktake_id'];
        } else {
            $stocktake_id = 0;
        }

        $stocktake_info = $this->model_inventory_stocktake->getStocktake($stocktake_id);

        if (!$stocktake_info) {
            $this->session->data['error'] = $this->language->get('error_stocktake_id');
            $this->response->redirect($this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token']));
        }

        if ($stocktake_info['status'] != 'draft' && $stocktake_info['status'] != 'in_progress') {
            $this->session->data['error'] = $this->language->get('error_stocktake_status');
            $this->response->redirect($this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token']));
        }

        // إلغاء عملية الجرد
        $this->model_inventory_stocktake->cancelStocktake($stocktake_id);

        // إضافة إشعار
        $this->load->model('notification/notification');
        $this->model_notification_notification->addNotification([
            'type' => 'stocktake_cancelled',
            'title' => $this->language->get('text_stocktake_cancelled'),
            'message' => sprintf($this->language->get('text_stocktake_cancelled_message'), $stocktake_info['reference']),
            'reference_type' => 'stocktake',
            'reference_id' => $stocktake_id,
            'user_id' => $this->user->getId(),
            'status' => 1
        ]);

        $this->session->data['success'] = $this->language->get('text_cancel_success');

        $url = '';

        if (isset($this->request->get['filter_reference'])) {
            $url .= '&filter_reference=' . urlencode($this->request->get['filter_reference']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_date_from'])) {
            $url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
        }

        if (isset($this->request->get['filter_date_to'])) {
            $url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_type'])) {
            $url .= '&filter_type=' . $this->request->get['filter_type'];
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

        $this->response->redirect($this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . $url));
    }

    /**
     * حذف عملية جرد
     */
    public function delete() {
        $this->load->language('inventory/stocktake');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/stocktake');

        if (!$this->user->hasPermission('modify', 'inventory/stocktake')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $stocktake_id) {
                $stocktake_info = $this->model_inventory_stocktake->getStocktake($stocktake_id);

                if ($stocktake_info && ($stocktake_info['status'] == 'draft' || $stocktake_info['status'] == 'cancelled')) {
                    $this->model_inventory_stocktake->deleteStocktake($stocktake_id);

                    // إضافة إشعار
                    $this->load->model('notification/notification');
                    $this->model_notification_notification->addNotification([
                        'type' => 'stocktake_deleted',
                        'title' => $this->language->get('text_stocktake_deleted'),
                        'message' => sprintf($this->language->get('text_stocktake_deleted_message'), $stocktake_info['reference']),
                        'reference_type' => 'stocktake',
                        'reference_id' => 0,
                        'user_id' => $this->user->getId(),
                        'status' => 1
                    ]);
                }
            }

            $this->session->data['success'] = $this->language->get('text_success');
        }

        $url = '';

        if (isset($this->request->get['filter_reference'])) {
            $url .= '&filter_reference=' . urlencode($this->request->get['filter_reference']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_date_from'])) {
            $url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
        }

        if (isset($this->request->get['filter_date_to'])) {
            $url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_type'])) {
            $url .= '&filter_type=' . $this->request->get['filter_type'];
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

        $this->response->redirect($this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . $url));
    }

    /**
     * تنزيل قالب Excel
     */
    public function downloadTemplate() {
        $this->load->language('inventory/stocktake');

        // تحميل مكتبة PHPExcel
        require_once(DIR_SYSTEM . 'library/PHPExcel.php');

        // إنشاء ملف Excel جديد
        $objPHPExcel = new PHPExcel();

        // تعيين خصائص الملف
        $objPHPExcel->getProperties()->setCreator($this->config->get('config_name'))
            ->setLastModifiedBy($this->config->get('config_name'))
            ->setTitle($this->language->get('text_import_from_excel'))
            ->setSubject($this->language->get('text_import_from_excel'))
            ->setDescription($this->language->get('text_import_instructions'));

        // تعيين الورقة النشطة
        $objPHPExcel->setActiveSheetIndex(0);

        // تعيين عنوان الورقة
        $objPHPExcel->getActiveSheet()->setTitle($this->language->get('text_import_from_excel'));

        // إضافة عناوين الأعمدة
        $objPHPExcel->getActiveSheet()->setCellValue('A1', $this->language->get('column_product'));
        $objPHPExcel->getActiveSheet()->setCellValue('B1', 'product_id');
        $objPHPExcel->getActiveSheet()->setCellValue('C1', $this->language->get('column_unit'));
        $objPHPExcel->getActiveSheet()->setCellValue('D1', 'unit_id');
        $objPHPExcel->getActiveSheet()->setCellValue('E1', $this->language->get('column_counted_quantity'));
        $objPHPExcel->getActiveSheet()->setCellValue('F1', $this->language->get('column_notes'));

        // تنسيق الخلايا
        $objPHPExcel->getActiveSheet()->getStyle('A1:F1')->getFont()->setBold(true);

        // تعيين عرض الأعمدة
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(30);

        // تعيين اسم الملف
        $filename = 'stocktake_template.xlsx';

        // تعيين رأس الملف
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // حفظ الملف
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');

        exit;
    }

    /**
     * استيراد بيانات الجرد
     */
    public function import() {
        $this->load->language('inventory/stocktake');
        $this->load->model('inventory/stocktake');

        $json = array();

        if (!$this->user->hasPermission('modify', 'inventory/stocktake')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (isset($this->request->files['file']) && is_uploaded_file($this->request->files['file']['tmp_name'])) {
            // تحميل مكتبة PHPExcel
            require_once(DIR_SYSTEM . 'library/PHPExcel.php');

            try {
                $objPHPExcel = PHPExcel_IOFactory::load($this->request->files['file']['tmp_name']);
                $sheet = $objPHPExcel->getSheet(0);
                $highestRow = $sheet->getHighestRow();

                $products = array();

                for ($row = 2; $row <= $highestRow; $row++) {
                    $product_id = $sheet->getCellByColumnAndRow(1, $row)->getValue();
                    $unit_id = $sheet->getCellByColumnAndRow(3, $row)->getValue();
                    $counted_quantity = $sheet->getCellByColumnAndRow(4, $row)->getValue();
                    $notes = $sheet->getCellByColumnAndRow(5, $row)->getValue();

                    if ($product_id && $unit_id && $counted_quantity) {
                        $products[] = array(
                            'product_id' => $product_id,
                            'unit_id' => $unit_id,
                            'counted_quantity' => $counted_quantity,
                            'notes' => $notes
                        );
                    }
                }

                if (isset($this->request->get['stocktake_id'])) {
                    $stocktake_id = $this->request->get['stocktake_id'];

                    $stocktake_info = $this->model_inventory_stocktake->getStocktake($stocktake_id);

                    if ($stocktake_info && ($stocktake_info['status'] == 'draft' || $stocktake_info['status'] == 'in_progress')) {
                        foreach ($products as $product) {
                            $this->model_inventory_stocktake->updateStocktakeProduct($stocktake_id, $product);
                        }

                        $json['success'] = $this->language->get('text_import_success');
                    } else {
                        $json['error'] = $this->language->get('error_stocktake_status');
                    }
                } else {
                    $json['stocktake_products'] = $products;
                    $json['success'] = $this->language->get('text_import_success');
                }
            } catch (Exception $e) {
                $json['error'] = $this->language->get('error_import_format');
            }
        } else {
            $json['error'] = $this->language->get('error_import_file');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * الحصول على المنتجات المتاحة
     */
    public function getAvailableProducts() {
        $this->load->language('inventory/stocktake');
        $this->load->model('inventory/stocktake');

        $json = array();

        if (isset($this->request->post['branch_id'])) {
            $branch_id = $this->request->post['branch_id'];
        } else {
            $branch_id = 0;
        }

        if (isset($this->request->post['category_id'])) {
            $category_id = $this->request->post['category_id'];
        } else {
            $category_id = 0;
        }

        $filter_data = array(
            'branch_id' => $branch_id,
            'category_id' => $category_id
        );

        $products = $this->model_inventory_stocktake->getAvailableProducts($filter_data);

        $json['products'] = array();

        foreach ($products as $product) {
            $json['products'][] = array(
                'product_id' => $product['product_id'],
                'name' => $product['name'],
                'model' => $product['model'],
                'sku' => $product['sku'],
                'unit_id' => $product['unit_id'],
                'unit_name' => $product['unit_name'],
                'quantity' => $product['quantity']
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * الحصول على المنتجات المحددة
     */
    public function getSelectedProducts() {
        $this->load->language('inventory/stocktake');
        $this->load->model('inventory/stocktake');

        $json = array();

        if (isset($this->request->post['branch_id'])) {
            $branch_id = $this->request->post['branch_id'];
        } else {
            $branch_id = 0;
        }

        if (isset($this->request->post['product_unit_ids']) && is_array($this->request->post['product_unit_ids'])) {
            $product_unit_ids = $this->request->post['product_unit_ids'];
        } else {
            $product_unit_ids = array();
        }

        $products = array();

        foreach ($product_unit_ids as $product_unit_id) {
            $parts = explode('_', $product_unit_id);

            if (count($parts) == 2) {
                $product_id = $parts[0];
                $unit_id = $parts[1];

                $product_info = $this->model_inventory_stocktake->getProductInfo($product_id, $unit_id, $branch_id);

                if ($product_info) {
                    $products[] = array(
                        'product_id' => $product_info['product_id'],
                        'name' => $product_info['name'],
                        'model' => $product_info['model'],
                        'sku' => $product_info['sku'],
                        'unit_id' => $product_info['unit_id'],
                        'unit_name' => $product_info['unit_name'],
                        'quantity' => $product_info['quantity']
                    );
                }
            }
        }

        $json['products'] = $products;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تصدير بيانات الجرد
     */
    public function export() {
        $this->load->language('inventory/stocktake');
        $this->load->model('inventory/stocktake');

        if (isset($this->request->get['stocktake_id'])) {
            $stocktake_id = $this->request->get['stocktake_id'];

            $stocktake_info = $this->model_inventory_stocktake->getStocktake($stocktake_id);

            if ($stocktake_info) {
                $stocktake_products = $this->model_inventory_stocktake->getStocktakeProducts($stocktake_id);

                // تحميل مكتبة PHPExcel
                require_once(DIR_SYSTEM . 'library/PHPExcel.php');

                // إنشاء ملف Excel جديد
                $objPHPExcel = new PHPExcel();

                // تعيين خصائص الملف
                $objPHPExcel->getProperties()->setCreator($this->config->get('config_name'))
                    ->setLastModifiedBy($this->config->get('config_name'))
                    ->setTitle($this->language->get('heading_stocktake_view'))
                    ->setSubject($stocktake_info['reference'])
                    ->setDescription($this->language->get('heading_stocktake_view') . ' - ' . $stocktake_info['reference']);

                // تعيين الورقة النشطة
                $objPHPExcel->setActiveSheetIndex(0);

                // تعيين عنوان الورقة
                $objPHPExcel->getActiveSheet()->setTitle($this->language->get('heading_stocktake_view'));

                // إضافة معلومات الجرد
                $objPHPExcel->getActiveSheet()->setCellValue('A1', $this->language->get('text_stocktake_details'));
                $objPHPExcel->getActiveSheet()->setCellValue('A2', $this->language->get('entry_reference'));
                $objPHPExcel->getActiveSheet()->setCellValue('B2', $stocktake_info['reference']);
                $objPHPExcel->getActiveSheet()->setCellValue('A3', $this->language->get('entry_branch'));
                $objPHPExcel->getActiveSheet()->setCellValue('B3', $stocktake_info['branch_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('A4', $this->language->get('entry_stocktake_date'));
                $objPHPExcel->getActiveSheet()->setCellValue('B4', date($this->language->get('date_format_short'), strtotime($stocktake_info['stocktake_date'])));
                $objPHPExcel->getActiveSheet()->setCellValue('A5', $this->language->get('entry_type'));
                $objPHPExcel->getActiveSheet()->setCellValue('B5', $this->language->get('text_type_' . $stocktake_info['type']));
                $objPHPExcel->getActiveSheet()->setCellValue('A6', $this->language->get('entry_status'));
                $objPHPExcel->getActiveSheet()->setCellValue('B6', $this->language->get('text_status_' . $stocktake_info['status']));

                // إضافة عناوين المنتجات
                $objPHPExcel->getActiveSheet()->setCellValue('A8', $this->language->get('text_stocktake_products'));
                $objPHPExcel->getActiveSheet()->setCellValue('A9', $this->language->get('column_product'));
                $objPHPExcel->getActiveSheet()->setCellValue('B9', $this->language->get('column_model'));
                $objPHPExcel->getActiveSheet()->setCellValue('C9', $this->language->get('column_sku'));
                $objPHPExcel->getActiveSheet()->setCellValue('D9', $this->language->get('column_unit'));
                $objPHPExcel->getActiveSheet()->setCellValue('E9', $this->language->get('column_expected_quantity'));
                $objPHPExcel->getActiveSheet()->setCellValue('F9', $this->language->get('column_counted_quantity'));
                $objPHPExcel->getActiveSheet()->setCellValue('G9', $this->language->get('column_variance_quantity'));
                $objPHPExcel->getActiveSheet()->setCellValue('H9', $this->language->get('column_variance_percentage'));
                $objPHPExcel->getActiveSheet()->setCellValue('I9', $this->language->get('column_notes'));

                // إضافة بيانات المنتجات
                $row = 10;
                $total_expected = 0;
                $total_counted = 0;
                $total_variance = 0;

                foreach ($stocktake_products as $product) {
                    $variance_quantity = $product['counted_quantity'] - $product['expected_quantity'];
                    $variance_percentage = ($product['expected_quantity'] > 0) ? ($variance_quantity / $product['expected_quantity']) * 100 : 0;

                    $objPHPExcel->getActiveSheet()->setCellValue('A' . $row, $product['product_name']);
                    $objPHPExcel->getActiveSheet()->setCellValue('B' . $row, $product['model']);
                    $objPHPExcel->getActiveSheet()->setCellValue('C' . $row, $product['sku']);
                    $objPHPExcel->getActiveSheet()->setCellValue('D' . $row, $product['unit_name']);
                    $objPHPExcel->getActiveSheet()->setCellValue('E' . $row, $product['expected_quantity']);
                    $objPHPExcel->getActiveSheet()->setCellValue('F' . $row, $product['counted_quantity']);
                    $objPHPExcel->getActiveSheet()->setCellValue('G' . $row, $variance_quantity);
                    $objPHPExcel->getActiveSheet()->setCellValue('H' . $row, $variance_percentage);
                    $objPHPExcel->getActiveSheet()->setCellValue('I' . $row, $product['notes']);

                    $total_expected += $product['expected_quantity'];
                    $total_counted += $product['counted_quantity'];
                    $total_variance += $variance_quantity;

                    $row++;
                }

                // إضافة الإجماليات
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $row, $this->language->get('text_total'));
                $objPHPExcel->getActiveSheet()->setCellValue('E' . $row, $total_expected);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . $row, $total_counted);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . $row, $total_variance);

                // تنسيق الخلايا
                $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
                $objPHPExcel->getActiveSheet()->getStyle('A8')->getFont()->setBold(true);
                $objPHPExcel->getActiveSheet()->getStyle('A9:I9')->getFont()->setBold(true);
                $objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':I' . $row)->getFont()->setBold(true);

                // تعيين عرض الأعمدة
                $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(30);
                $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
                $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
                $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
                $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
                $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
                $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
                $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
                $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(30);

                // تعيين اسم الملف
                $filename = 'stocktake_' . $stocktake_info['reference'] . '_' . date('Y-m-d') . '.xlsx';

                // تعيين رأس الملف
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                header('Cache-Control: max-age=0');

                // حفظ الملف
                $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
                $objWriter->save('php://output');

                exit;
            }
        } else {
            // تصدير قائمة عمليات الجرد
            $filter_data = array();

            if (isset($this->request->get['filter_reference'])) {
                $filter_data['filter_reference'] = $this->request->get['filter_reference'];
            }

            if (isset($this->request->get['filter_branch'])) {
                $filter_data['filter_branch'] = $this->request->get['filter_branch'];
            }

            if (isset($this->request->get['filter_date_from'])) {
                $filter_data['filter_date_from'] = $this->request->get['filter_date_from'];
            }

            if (isset($this->request->get['filter_date_to'])) {
                $filter_data['filter_date_to'] = $this->request->get['filter_date_to'];
            }

            if (isset($this->request->get['filter_status'])) {
                $filter_data['filter_status'] = $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_type'])) {
                $filter_data['filter_type'] = $this->request->get['filter_type'];
            }

            $stocktakes = $this->model_inventory_stocktake->getStocktakes($filter_data);

            // تحميل مكتبة PHPExcel
            require_once(DIR_SYSTEM . 'library/PHPExcel.php');

            // إنشاء ملف Excel جديد
            $objPHPExcel = new PHPExcel();

            // تعيين خصائص الملف
            $objPHPExcel->getProperties()->setCreator($this->config->get('config_name'))
                ->setLastModifiedBy($this->config->get('config_name'))
                ->setTitle($this->language->get('heading_title'))
                ->setSubject($this->language->get('heading_title'))
                ->setDescription($this->language->get('heading_title'));

            // تعيين الورقة النشطة
            $objPHPExcel->setActiveSheetIndex(0);

            // تعيين عنوان الورقة
            $objPHPExcel->getActiveSheet()->setTitle($this->language->get('heading_title'));

            // إضافة عناوين الأعمدة
            $objPHPExcel->getActiveSheet()->setCellValue('A1', $this->language->get('column_reference'));
            $objPHPExcel->getActiveSheet()->setCellValue('B1', $this->language->get('column_branch'));
            $objPHPExcel->getActiveSheet()->setCellValue('C1', $this->language->get('column_date'));
            $objPHPExcel->getActiveSheet()->setCellValue('D1', $this->language->get('column_type'));
            $objPHPExcel->getActiveSheet()->setCellValue('E1', $this->language->get('column_status'));
            $objPHPExcel->getActiveSheet()->setCellValue('F1', $this->language->get('column_total_items'));
            $objPHPExcel->getActiveSheet()->setCellValue('G1', $this->language->get('column_created_by'));
            $objPHPExcel->getActiveSheet()->setCellValue('H1', $this->language->get('column_date_added'));

            // إضافة بيانات عمليات الجرد
            $row = 2;

            foreach ($stocktakes as $stocktake) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $row, $stocktake['reference']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . $row, $stocktake['branch_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . $row, date($this->language->get('date_format_short'), strtotime($stocktake['stocktake_date'])));
                $objPHPExcel->getActiveSheet()->setCellValue('D' . $row, $this->language->get('text_type_' . $stocktake['type']));
                $objPHPExcel->getActiveSheet()->setCellValue('E' . $row, $this->language->get('text_status_' . $stocktake['status']));
                $objPHPExcel->getActiveSheet()->setCellValue('F' . $row, $stocktake['total_items']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . $row, $stocktake['created_by_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . $row, date($this->language->get('date_format_short'), strtotime($stocktake['date_added'])));

                $row++;
            }

            // تنسيق الخلايا
            $objPHPExcel->getActiveSheet()->getStyle('A1:H1')->getFont()->setBold(true);

            // تعيين عرض الأعمدة
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);

            // تعيين اسم الملف
            $filename = 'stocktake_list_' . date('Y-m-d') . '.xlsx';

            // تعيين رأس الملف
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            // حفظ الملف
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save('php://output');

            exit;
        }

        $this->response->redirect($this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token']));
    }

    /**
     * التحقق من صحة النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/stocktake')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['reference'])) {
            $this->error['reference'] = $this->language->get('error_reference');
        }

        if (empty($this->request->post['branch_id'])) {
            $this->error['branch'] = $this->language->get('error_branch');
        }

        if (empty($this->request->post['stocktake_date'])) {
            $this->error['stocktake_date'] = $this->language->get('error_stocktake_date');
        }

        if (empty($this->request->post['type'])) {
            $this->error['type'] = $this->language->get('error_type');
        }

        if (empty($this->request->post['products'])) {
            $this->error['products'] = $this->language->get('error_products');
        } else {
            foreach ($this->request->post['products'] as $product) {
                if (!isset($product['product_id']) || empty($product['product_id'])) {
                    $this->error['products'] = $this->language->get('error_product');
                }

                if (!isset($product['unit_id']) || empty($product['unit_id'])) {
                    $this->error['products'] = $this->language->get('error_unit');
                }

                if (!isset($product['expected_quantity'])) {
                    $this->error['products'] = $this->language->get('error_expected_quantity');
                }

                if (!isset($product['counted_quantity'])) {
                    $this->error['products'] = $this->language->get('error_counted_quantity_required');
                }
            }
        }

        if (isset($this->request->get['stocktake_id'])) {
            $stocktake_info = $this->model_inventory_stocktake->getStocktake($this->request->get['stocktake_id']);

            if ($stocktake_info && $stocktake_info['status'] == 'completed') {
                $this->error['warning'] = $this->language->get('error_stocktake_completed');
            }

            if ($stocktake_info && $stocktake_info['status'] == 'cancelled') {
                $this->error['warning'] = $this->language->get('error_stocktake_cancelled');
            }
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة الحذف
     */
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'inventory/stocktake')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    /**
     * الحصول على نموذج إضافة/تعديل عملية الجرد
     */
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['stocktake_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['reference'])) {
            $data['error_reference'] = $this->error['reference'];
        } else {
            $data['error_reference'] = '';
        }

        if (isset($this->error['branch'])) {
            $data['error_branch'] = $this->error['branch'];
        } else {
            $data['error_branch'] = '';
        }

        if (isset($this->error['stocktake_date'])) {
            $data['error_stocktake_date'] = $this->error['stocktake_date'];
        } else {
            $data['error_stocktake_date'] = '';
        }

        if (isset($this->error['type'])) {
            $data['error_type'] = $this->error['type'];
        } else {
            $data['error_type'] = '';
        }

        if (isset($this->error['products'])) {
            $data['error_products'] = $this->error['products'];
        } else {
            $data['error_products'] = '';
        }

        $url = '';

        if (isset($this->request->get['filter_reference'])) {
            $url .= '&filter_reference=' . urlencode($this->request->get['filter_reference']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_date_from'])) {
            $url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
        }

        if (isset($this->request->get['filter_date_to'])) {
            $url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_type'])) {
            $url .= '&filter_type=' . $this->request->get['filter_type'];
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
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . $url)
        );

        if (!isset($this->request->get['stocktake_id'])) {
            $data['action'] = $this->url->link('inventory/stocktake/add', 'user_token=' . $this->session->data['user_token'] . $url);
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_add'),
                'href' => $this->url->link('inventory/stocktake/add', 'user_token=' . $this->session->data['user_token'] . $url)
            );
        } else {
            $data['action'] = $this->url->link('inventory/stocktake/edit', 'user_token=' . $this->session->data['user_token'] . '&stocktake_id=' . $this->request->get['stocktake_id'] . $url);
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_edit'),
                'href' => $this->url->link('inventory/stocktake/edit', 'user_token=' . $this->session->data['user_token'] . '&stocktake_id=' . $this->request->get['stocktake_id'] . $url)
            );
        }

        $data['cancel'] = $this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['download_template'] = $this->url->link('inventory/stocktake/downloadTemplate', 'user_token=' . $this->session->data['user_token']);

        // الحصول على بيانات عملية الجرد
        if (isset($this->request->get['stocktake_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $stocktake_info = $this->model_inventory_stocktake->getStocktake($this->request->get['stocktake_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['reference'])) {
            $data['reference'] = $this->request->post['reference'];
        } elseif (!empty($stocktake_info)) {
            $data['reference'] = $stocktake_info['reference'];
        } else {
            $data['reference'] = '';
        }

        if (isset($this->request->post['branch_id'])) {
            $data['branch_id'] = $this->request->post['branch_id'];
        } elseif (!empty($stocktake_info)) {
            $data['branch_id'] = $stocktake_info['branch_id'];
        } else {
            $data['branch_id'] = '';
        }

        if (isset($this->request->post['stocktake_date'])) {
            $data['stocktake_date'] = $this->request->post['stocktake_date'];
        } elseif (!empty($stocktake_info)) {
            $data['stocktake_date'] = $stocktake_info['stocktake_date'];
        } else {
            $data['stocktake_date'] = date('Y-m-d');
        }

        if (isset($this->request->post['type'])) {
            $data['type'] = $this->request->post['type'];
        } elseif (!empty($stocktake_info)) {
            $data['type'] = $stocktake_info['type'];
        } else {
            $data['type'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($stocktake_info)) {
            $data['status'] = $stocktake_info['status'];
        } else {
            $data['status'] = 'draft';
        }

        if (isset($this->request->post['notes'])) {
            $data['notes'] = $this->request->post['notes'];
        } elseif (!empty($stocktake_info)) {
            $data['notes'] = $stocktake_info['notes'];
        } else {
            $data['notes'] = '';
        }

        // الحصول على منتجات عملية الجرد
        $data['products'] = array();

        if (isset($this->request->post['products'])) {
            $products = $this->request->post['products'];
        } elseif (isset($this->request->get['stocktake_id'])) {
            $products = $this->model_inventory_stocktake->getStocktakeProducts($this->request->get['stocktake_id']);
        } else {
            $products = array();
        }

        $data['product_row'] = 0;

        foreach ($products as $product) {
            if (isset($product['product_id'])) {
                $data['products'][$data['product_row']] = $product;
                $data['product_row']++;
            }
        }

        // الحصول على الفروع
        $this->load->model('branch/branch');
        $data['branches'] = $this->model_branch_branch->getBranches();

        // الحصول على الفئات
        $this->load->model('catalog/category');
        $data['categories'] = $this->model_catalog_category->getCategories(array('sort' => 'name'));

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/stocktake_form', $data));
    }
}
