<?php
/**
 * إدارة الجرد المخزني المتطور (Advanced Stock Counting Controller)
 * 
 * الهدف: توفير واجهة متطورة لإدارة عمليات الجرد المخزني
 * الميزات: جرد دوري/مستمر، تتبع الحالة، تحليل الفروقات، workflow متقدم
 * التكامل: مع المحاسبة والتسويات والتقارير والتنبيهات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryStockCounting extends Controller {
    
    private $error = array();
    
    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/stock_counting');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/stock_counting');
        $this->load->model('inventory/category');
        $this->load->model('inventory/branch');
        $this->load->model('user/user');
        
        // معالجة الطلبات
        $this->getList();
    }
    
    protected function getList() {
        // معالجة الفلاتر
        $filter_data = $this->getFilters();
        
        // إعداد الروابط
        $url = $this->buildUrl($filter_data);
        
        // إعداد البيانات الأساسية
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/stock_counting', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        // روابط الإجراءات
        $data['add'] = $this->url->link('inventory/stock_counting/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_excel'] = $this->url->link('inventory/stock_counting/exportExcel', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['export_pdf'] = $this->url->link('inventory/stock_counting/exportPdf', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['print'] = $this->url->link('inventory/stock_counting/print', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['refresh'] = $this->url->link('inventory/stock_counting', 'user_token=' . $this->session->data['user_token'], true);
        
        // الحصول على البيانات
        $stock_countings = array();
        $filter_data_with_pagination = $filter_data;
        $filter_data_with_pagination['start'] = ($filter_data['page'] - 1) * $this->config->get('config_limit_admin');
        $filter_data_with_pagination['limit'] = $this->config->get('config_limit_admin');
        
        $results = $this->model_inventory_stock_counting->getStockCountings($filter_data_with_pagination);
        $total = $this->model_inventory_stock_counting->getTotalStockCountings($filter_data);
        
        foreach ($results as $result) {
            $progress_percentage = $result['total_items'] > 0 ? round(($result['counted_items'] / $result['total_items']) * 100, 1) : 0;
            
            $stock_countings[] = array(
                'counting_id'             => $result['counting_id'],
                'counting_number'         => $result['counting_number'],
                'counting_name'           => $result['counting_name'],
                'counting_type'           => $result['counting_type'],
                'counting_type_text'      => $result['counting_type_text'],
                'status'                  => $result['status'],
                'status_text'             => $result['status_text'],
                'status_class'            => $this->getStatusClass($result['status']),
                'branch_name'             => $result['branch_name'],
                'branch_type'             => $this->language->get('text_branch_type_' . $result['branch_type']),
                'category_name'           => $result['category_name'] ? $result['category_name'] : $this->language->get('text_all_categories'),
                'user_name'               => $result['user_name'],
                'start_date'              => $result['start_date'] ? date($this->language->get('date_format_short'), strtotime($result['start_date'])) : '',
                'end_date'                => $result['end_date'] ? date($this->language->get('date_format_short'), strtotime($result['end_date'])) : '',
                'counting_date'           => date($this->language->get('date_format_short'), strtotime($result['counting_date'])),
                'total_items'             => number_format($result['total_items']),
                'counted_items'           => number_format($result['counted_items']),
                'progress_percentage'     => $progress_percentage,
                'progress_class'          => $this->getProgressClass($progress_percentage),
                'total_variance_quantity' => number_format($result['total_variance_quantity'], 2),
                'total_variance_value'    => $this->currency->format($result['total_variance_value'], $this->config->get('config_currency')),
                'variance_class'          => $this->getVarianceClass($result['total_variance_value']),
                'notes'                   => $result['notes'],
                'date_added'              => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
                'edit'                    => $this->url->link('inventory/stock_counting/edit', 'user_token=' . $this->session->data['user_token'] . '&counting_id=' . $result['counting_id'], true),
                'view'                    => $this->url->link('inventory/stock_counting/view', 'user_token=' . $this->session->data['user_token'] . '&counting_id=' . $result['counting_id'], true),
                'count'                   => $this->url->link('inventory/stock_counting/count', 'user_token=' . $this->session->data['user_token'] . '&counting_id=' . $result['counting_id'], true),
                'delete'                  => $this->url->link('inventory/stock_counting/delete', 'user_token=' . $this->session->data['user_token'] . '&counting_id=' . $result['counting_id'], true)
            );
        }
        
        $data['stock_countings'] = $stock_countings;
        
        // الحصول على ملخص الجرد
        $summary = $this->model_inventory_stock_counting->getCountingSummary($filter_data);
        $data['summary'] = array(
            'total_countings'         => number_format($summary['total_countings']),
            'draft_count'             => number_format($summary['draft_count']),
            'in_progress_count'       => number_format($summary['in_progress_count']),
            'completed_count'         => number_format($summary['completed_count']),
            'posted_count'            => number_format($summary['posted_count']),
            'avg_items_per_counting'  => number_format($summary['avg_items_per_counting'], 1)
        );
        
        // إعداد الفلاتر للعرض
        $this->setupFiltersForDisplay($data, $filter_data);
        
        // إعداد الترقيم
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/stock_counting', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
        
        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($filter_data['page'] - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($filter_data['page'] - 1) * $this->config->get('config_limit_admin')) > ($total - $this->config->get('config_limit_admin'))) ? $total : ((($filter_data['page'] - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $total, ceil($total / $this->config->get('config_limit_admin')));
        
        // إعداد الترتيب
        $data['sort'] = $filter_data['sort'];
        $data['order'] = $filter_data['order'];
        
        $data['user_token'] = $this->session->data['user_token'];
        
        // رسائل النجاح والخطأ
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('inventory/stock_counting_list', $data));
    }
    
    /**
     * إضافة جرد جديد
     */
    public function add() {
        $this->load->language('inventory/stock_counting');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/stock_counting');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $counting_id = $this->model_inventory_stock_counting->addStockCounting($this->request->post);
            
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
            
            $this->response->redirect($this->url->link('inventory/stock_counting', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    /**
     * تعديل جرد
     */
    public function edit() {
        $this->load->language('inventory/stock_counting');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/stock_counting');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_inventory_stock_counting->editStockCounting($this->request->get['counting_id'], $this->request->post);
            
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
            
            $this->response->redirect($this->url->link('inventory/stock_counting', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    /**
     * حذف جرد
     */
    public function delete() {
        $this->load->language('inventory/stock_counting');
        $this->load->model('inventory/stock_counting');
        
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $counting_id) {
                $this->model_inventory_stock_counting->deleteStockCounting($counting_id);
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
            
            $this->response->redirect($this->url->link('inventory/stock_counting', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getList();
    }
    
    /**
     * عرض نموذج الإضافة/التعديل
     */
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['counting_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        // إعداد البيانات للنموذج
        $this->setupFormData($data);
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('inventory/stock_counting_form', $data));
    }
    
    /**
     * إعداد بيانات النموذج
     */
    private function setupFormData(&$data) {
        // الحصول على البيانات الموجودة أو القيم الافتراضية
        if (isset($this->request->get['counting_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $counting_info = $this->model_inventory_stock_counting->getStockCounting($this->request->get['counting_id']);
        }
        
        $fields = array(
            'counting_number', 'counting_name', 'counting_type', 'branch_id', 
            'category_id', 'start_date', 'end_date', 'counting_date', 'notes'
        );
        
        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($counting_info)) {
                $data[$field] = $counting_info[$field];
            } else {
                $data[$field] = '';
            }
        }
        
        // توليد رقم جرد جديد للإضافة
        if (!isset($this->request->get['counting_id'])) {
            $data['counting_number'] = $this->model_inventory_stock_counting->generateCountingNumber();
            $data['counting_date'] = date('Y-m-d');
        }
        
        // الحصول على القوائم
        $this->load->model('inventory/branch');
        $this->load->model('inventory/category');
        
        $data['branches'] = $this->model_inventory_branch->getBranches();
        $data['categories'] = $this->model_inventory_category->getCategories();
        
        // خيارات نوع الجرد
        $data['counting_types'] = array(
            array('value' => 'full', 'text' => $this->language->get('text_counting_type_full')),
            array('value' => 'partial', 'text' => $this->language->get('text_counting_type_partial')),
            array('value' => 'cycle', 'text' => $this->language->get('text_counting_type_cycle')),
            array('value' => 'spot', 'text' => $this->language->get('text_counting_type_spot'))
        );
        
        // الروابط
        $data['action'] = $this->url->link('inventory/stock_counting/' . (!isset($this->request->get['counting_id']) ? 'add' : 'edit'), 'user_token=' . $this->session->data['user_token'] . (!isset($this->request->get['counting_id']) ? '' : '&counting_id=' . $this->request->get['counting_id']), true);
        $data['cancel'] = $this->url->link('inventory/stock_counting', 'user_token=' . $this->session->data['user_token'], true);
    }
    
    /**
     * تنفيذ الجرد
     */
    public function count() {
        $this->load->language('inventory/stock_counting');
        $this->load->model('inventory/stock_counting');
        
        $counting_id = isset($this->request->get['counting_id']) ? (int)$this->request->get['counting_id'] : 0;
        
        if (!$counting_id) {
            $this->session->data['error'] = $this->language->get('error_counting_not_found');
            $this->response->redirect($this->url->link('inventory/stock_counting', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // معالجة تحديث الكميات
        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['items'])) {
            foreach ($this->request->post['items'] as $item_id => $item_data) {
                if (isset($item_data['actual_quantity']) && $item_data['actual_quantity'] !== '') {
                    $this->model_inventory_stock_counting->updateCountingItem(
                        $item_id, 
                        $item_data['actual_quantity'], 
                        isset($item_data['notes']) ? $item_data['notes'] : ''
                    );
                }
            }
            
            $this->session->data['success'] = $this->language->get('text_success');
        }
        
        // الحصول على معلومات الجرد
        $counting_info = $this->model_inventory_stock_counting->getStockCounting($counting_id);
        $counting_items = $this->model_inventory_stock_counting->getCountingItems($counting_id);
        
        $data['counting_info'] = $counting_info;
        $data['counting_items'] = $counting_items;
        
        $this->response->setOutput($this->load->view('inventory/stock_counting_count', $data));
    }
    
    /**
     * معالجة الفلاتر
     */
    private function getFilters() {
        $filters = array(
            'filter_counting_number'  => '',
            'filter_counting_name'    => '',
            'filter_status'           => '',
            'filter_counting_type'    => '',
            'filter_branch_id'        => '',
            'filter_category_id'      => '',
            'filter_user_id'          => '',
            'filter_date_from'        => '',
            'filter_date_to'          => '',
            'sort'                    => 'sc.date_added',
            'order'                   => 'DESC',
            'page'                    => 1
        );
        
        foreach ($filters as $key => $default) {
            if (isset($this->request->get[$key])) {
                $filters[$key] = $this->request->get[$key];
            }
        }
        
        return $filters;
    }
    
    /**
     * بناء رابط URL مع الفلاتر
     */
    private function buildUrl($filters) {
        $url = '';
        
        foreach ($filters as $key => $value) {
            if ($value !== '' && $key !== 'page') {
                $url .= '&' . $key . '=' . urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
            }
        }
        
        return $url;
    }
    
    /**
     * إعداد الفلاتر للعرض
     */
    private function setupFiltersForDisplay(&$data, $filters) {
        // نسخ الفلاتر للعرض
        foreach ($filters as $key => $value) {
            $data[$key] = $value;
        }
        
        // الحصول على قوائم الفلاتر
        $data['branches'] = $this->model_inventory_branch->getBranches();
        $data['categories'] = $this->model_inventory_category->getCategories();
        $data['users'] = $this->model_user_user->getUsers();
        
        // خيارات الحالة
        $data['status_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => 'draft', 'text' => $this->language->get('text_status_draft')),
            array('value' => 'in_progress', 'text' => $this->language->get('text_status_in_progress')),
            array('value' => 'completed', 'text' => $this->language->get('text_status_completed')),
            array('value' => 'posted', 'text' => $this->language->get('text_status_posted')),
            array('value' => 'cancelled', 'text' => $this->language->get('text_status_cancelled'))
        );
        
        // خيارات نوع الجرد
        $data['counting_type_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => 'full', 'text' => $this->language->get('text_counting_type_full')),
            array('value' => 'partial', 'text' => $this->language->get('text_counting_type_partial')),
            array('value' => 'cycle', 'text' => $this->language->get('text_counting_type_cycle')),
            array('value' => 'spot', 'text' => $this->language->get('text_counting_type_spot'))
        );
    }
    
    /**
     * الحصول على فئة CSS للحالة
     */
    private function getStatusClass($status) {
        switch ($status) {
            case 'draft':
                return 'default';
            case 'in_progress':
                return 'warning';
            case 'completed':
                return 'info';
            case 'posted':
                return 'success';
            case 'cancelled':
                return 'danger';
            default:
                return 'default';
        }
    }
    
    /**
     * الحصول على فئة CSS للتقدم
     */
    private function getProgressClass($percentage) {
        if ($percentage >= 100) {
            return 'success';
        } elseif ($percentage >= 75) {
            return 'info';
        } elseif ($percentage >= 50) {
            return 'warning';
        } else {
            return 'danger';
        }
    }
    
    /**
     * الحصول على فئة CSS للفروقات
     */
    private function getVarianceClass($variance_value) {
        if ($variance_value == 0) {
            return 'success';
        } elseif (abs($variance_value) < 100) {
            return 'warning';
        } else {
            return 'danger';
        }
    }
    
    /**
     * التحقق من صحة النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/stock_counting')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if ((utf8_strlen($this->request->post['counting_name']) < 3) || (utf8_strlen($this->request->post['counting_name']) > 255)) {
            $this->error['counting_name'] = $this->language->get('error_counting_name');
        }
        
        if (empty($this->request->post['branch_id'])) {
            $this->error['branch_id'] = $this->language->get('error_branch_required');
        }
        
        if (empty($this->request->post['counting_date'])) {
            $this->error['counting_date'] = $this->language->get('error_counting_date');
        }
        
        return !$this->error;
    }
    
    /**
     * التحقق من صحة الحذف
     */
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'inventory/stock_counting')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
    
    /**
     * تصدير إلى Excel
     */
    public function exportExcel() {
        $this->load->language('inventory/stock_counting');
        $this->load->model('inventory/stock_counting');
        
        $filter_data = $this->getFilters();
        $results = $this->model_inventory_stock_counting->exportToExcel($filter_data);
        
        // إنشاء ملف Excel
        $filename = 'stock_countings_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        $output = fopen('php://output', 'w');
        
        // كتابة العناوين
        $headers = array(
            $this->language->get('column_counting_number'),
            $this->language->get('column_counting_name'),
            $this->language->get('column_counting_type'),
            $this->language->get('column_status'),
            $this->language->get('column_branch'),
            $this->language->get('column_category'),
            $this->language->get('column_counting_date'),
            $this->language->get('column_total_items'),
            $this->language->get('column_total_variance_value'),
            $this->language->get('column_user'),
            $this->language->get('column_notes')
        );
        
        fputcsv($output, $headers);
        
        // كتابة البيانات
        foreach ($results as $result) {
            $row = array(
                $result['counting_number'],
                $result['counting_name'],
                $result['counting_type_text'],
                $result['status_text'],
                $result['branch_name'],
                $result['category_name'],
                $result['counting_date'],
                $result['total_items'],
                $result['total_variance_value'],
                $result['user_name'],
                $result['notes']
            );
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}
