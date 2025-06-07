<?php
/**
 * نظام إدارة التكلفة المتقدم (WAC + Landed Costs)
 * مستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
 */
class ControllerPurchaseCostManagementAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('purchase/cost_management_advanced');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('purchase/cost_management_advanced');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/purchase/cost_management.css');
        $this->document->addScript('view/javascript/purchase/cost_management.js');
        $this->document->addScript('view/javascript/chart.min.js');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'cost_management',
            'record_id' => 0,
            'description' => 'عرض نظام إدارة التكلفة المتقدم',
            'module' => 'cost_management'
        ]);

        $this->getList();
    }

    public function calculateWAC() {
        $this->load->model('purchase/cost_management_advanced');
        $this->load->model('accounts/audit_trail');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            try {
                // التحقق من الصلاحيات
                if (!$this->user->hasPermission('modify', 'purchase/cost_management_advanced')) {
                    throw new Exception('ليس لديك صلاحية لحساب التكلفة المرجحة');
                }

                $filter_data = $this->prepareWACFilterData();
                
                $result = $this->model_purchase_cost_management_advanced->calculateWeightedAverageCost($filter_data);
                
                if ($result['success']) {
                    $json['success'] = true;
                    $json['message'] = 'تم حساب التكلفة المرجحة بنجاح';
                    $json['details'] = $result['details'];
                    
                    // تسجيل في سجل المراجعة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'calculate_wac',
                        'table_name' => 'cost_management',
                        'record_id' => 0,
                        'description' => 'حساب التكلفة المرجحة - معالجة ' . $result['details']['processed_products'] . ' منتج',
                        'module' => 'cost_management'
                    ]);
                } else {
                    $json['error'] = 'فشل في حساب التكلفة المرجحة';
                    $json['details'] = $result['details'];
                }
                
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'طريقة طلب غير صحيحة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function allocateLandedCosts() {
        $this->load->model('purchase/cost_management_advanced');
        $this->load->model('accounts/audit_trail');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            try {
                // التحقق من الصلاحيات
                if (!$this->user->hasPermission('modify', 'purchase/cost_management_advanced')) {
                    throw new Exception('ليس لديك صلاحية لتوزيع التكاليف الإضافية');
                }

                $allocation_data = $this->prepareLandedCostData();
                
                $result = $this->model_purchase_cost_management_advanced->allocateLandedCosts($allocation_data);
                
                if ($result['success']) {
                    $json['success'] = true;
                    $json['message'] = 'تم توزيع التكاليف الإضافية بنجاح';
                    $json['details'] = $result['details'];
                    
                    // تسجيل في سجل المراجعة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'allocate_landed_costs',
                        'table_name' => 'cost_management',
                        'record_id' => $allocation_data['shipment_id'],
                        'description' => 'توزيع تكاليف إضافية بقيمة ' . $allocation_data['total_cost'] . ' على الشحنة رقم: ' . $allocation_data['shipment_id'],
                        'module' => 'cost_management'
                    ]);
                } else {
                    $json['error'] = 'فشل في توزيع التكاليف الإضافية';
                    $json['details'] = $result['details'];
                }
                
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'طريقة طلب غير صحيحة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getCostAnalysis() {
        $this->load->model('purchase/cost_management_advanced');

        $json = array();

        try {
            $filter_data = array(
                'date_from' => $this->request->get['date_from'] ?? date('Y-m-01'),
                'date_to' => $this->request->get['date_to'] ?? date('Y-m-d'),
                'product_id' => $this->request->get['product_id'] ?? null,
                'supplier_id' => $this->request->get['supplier_id'] ?? null,
                'category_id' => $this->request->get['category_id'] ?? null
            );

            $analysis = $this->model_purchase_cost_management_advanced->getCostAnalysis($filter_data);
            
            $json['success'] = true;
            $json['analysis'] = $analysis;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getCostTrends() {
        $this->load->model('purchase/cost_management_advanced');

        $json = array();

        try {
            $filter_data = array(
                'period' => $this->request->get['period'] ?? '12_months',
                'product_id' => $this->request->get['product_id'] ?? null,
                'supplier_id' => $this->request->get['supplier_id'] ?? null
            );

            $trends = $this->model_purchase_cost_management_advanced->getCostTrends($filter_data);
            
            $json['success'] = true;
            $json['trends'] = $trends;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getVarianceAnalysis() {
        $this->load->model('purchase/cost_management_advanced');

        $json = array();

        try {
            $filter_data = array(
                'date_from' => $this->request->get['date_from'] ?? date('Y-m-01'),
                'date_to' => $this->request->get['date_to'] ?? date('Y-m-d'),
                'variance_threshold' => $this->request->get['variance_threshold'] ?? 5 // 5%
            );

            $variance = $this->model_purchase_cost_management_advanced->getVarianceAnalysis($filter_data);
            
            $json['success'] = true;
            $json['variance'] = $variance;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function optimizeCosts() {
        $this->load->model('purchase/cost_management_advanced');

        $json = array();

        try {
            $optimization = $this->model_purchase_cost_management_advanced->getOptimizationRecommendations();
            
            $json['success'] = true;
            $json['optimization'] = $optimization;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function exportCostReport() {
        $this->load->model('purchase/cost_management_advanced');

        $format = $this->request->get['format'] ?? 'excel';
        
        try {
            $filter_data = array(
                'date_from' => $this->request->get['date_from'] ?? date('Y-m-01'),
                'date_to' => $this->request->get['date_to'] ?? date('Y-m-d'),
                'report_type' => $this->request->get['report_type'] ?? 'comprehensive'
            );

            $report_data = $this->model_purchase_cost_management_advanced->generateCostReport($filter_data);
            
            switch ($format) {
                case 'excel':
                    $this->exportToExcel($report_data, $filter_data);
                    break;
                case 'pdf':
                    $this->exportToPdf($report_data, $filter_data);
                    break;
                case 'csv':
                    $this->exportToCsv($report_data, $filter_data);
                    break;
                default:
                    $this->exportToExcel($report_data, $filter_data);
            }
            
        } catch (Exception $e) {
            $this->session->data['error'] = 'خطأ في تصدير تقرير التكلفة: ' . $e->getMessage();
            $this->response->redirect($this->url->link('purchase/cost_management_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    protected function prepareWACFilterData() {
        return array(
            'product_id' => $this->request->post['product_id'] ?? null,
            'category_id' => $this->request->post['category_id'] ?? null,
            'supplier_id' => $this->request->post['supplier_id'] ?? null,
            'date_from' => $this->request->post['date_from'] ?? date('Y-m-01'),
            'date_to' => $this->request->post['date_to'] ?? date('Y-m-d'),
            'include_pending' => $this->request->post['include_pending'] ?? 0,
            'recalculate_all' => $this->request->post['recalculate_all'] ?? 0
        );
    }

    protected function prepareLandedCostData() {
        return array(
            'shipment_id' => $this->request->post['shipment_id'],
            'total_cost' => $this->request->post['total_cost'],
            'cost_breakdown' => $this->request->post['cost_breakdown'] ?? array(),
            'allocation_method' => $this->request->post['allocation_method'] ?? 'value', // value, weight, quantity
            'products' => $this->request->post['products'] ?? array(),
            'auto_update_wac' => $this->request->post['auto_update_wac'] ?? 1
        );
    }

    protected function getList() {
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/cost_management_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        // URLs للـ AJAX
        $data['calculate_wac_url'] = $this->url->link('purchase/cost_management_advanced/calculateWAC', 'user_token=' . $this->session->data['user_token'], true);
        $data['allocate_costs_url'] = $this->url->link('purchase/cost_management_advanced/allocateLandedCosts', 'user_token=' . $this->session->data['user_token'], true);
        $data['cost_analysis_url'] = $this->url->link('purchase/cost_management_advanced/getCostAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['cost_trends_url'] = $this->url->link('purchase/cost_management_advanced/getCostTrends', 'user_token=' . $this->session->data['user_token'], true);
        $data['variance_analysis_url'] = $this->url->link('purchase/cost_management_advanced/getVarianceAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['optimization_url'] = $this->url->link('purchase/cost_management_advanced/optimizeCosts', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_url'] = $this->url->link('purchase/cost_management_advanced/exportCostReport', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];

        // تحميل قوائم البيانات
        $this->load->model('catalog/product');
        $data['products'] = $this->model_catalog_product->getProducts();

        $this->load->model('catalog/category');
        $data['categories'] = $this->model_catalog_category->getCategories();

        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        // الصلاحيات
        $data['can_calculate_wac'] = $this->user->hasPermission('modify', 'purchase/cost_management_advanced');
        $data['can_allocate_costs'] = $this->user->hasPermission('modify', 'purchase/cost_management_advanced');

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

        $this->response->setOutput($this->load->view('purchase/cost_management_advanced', $data));
    }

    private function exportToExcel($data, $filter_data) {
        // تنفيذ تصدير Excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="cost_management_report_' . date('Y-m-d') . '.xlsx"');
        // كود تصدير Excel هنا
    }

    private function exportToPdf($data, $filter_data) {
        // تنفيذ تصدير PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="cost_management_report_' . date('Y-m-d') . '.pdf"');
        // كود تصدير PDF هنا
    }

    private function exportToCsv($data, $filter_data) {
        // تنفيذ تصدير CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="cost_management_report_' . date('Y-m-d') . '.csv"');
        // كود تصدير CSV هنا
    }
}
