<?php
/**
 * نظام تحليل الموردين المتقدم
 * مستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
 */
class ControllerPurchaseSupplierAnalyticsAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('purchase/supplier_analytics_advanced');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('purchase/supplier_analytics_advanced');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/purchase/supplier_analytics.css');
        $this->document->addScript('view/javascript/purchase/supplier_analytics.js');
        $this->document->addScript('view/javascript/chart.min.js');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'supplier_analytics',
            'record_id' => 0,
            'description' => 'عرض نظام تحليل الموردين المتقدم',
            'module' => 'supplier_analytics'
        ]);

        $this->getList();
    }

    public function getSupplierPerformance() {
        $this->load->model('purchase/supplier_analytics_advanced');

        $json = array();

        try {
            $filter_data = array(
                'supplier_id' => $this->request->get['supplier_id'] ?? null,
                'date_from' => $this->request->get['date_from'] ?? date('Y-m-01'),
                'date_to' => $this->request->get['date_to'] ?? date('Y-m-d'),
                'category_id' => $this->request->get['category_id'] ?? null
            );

            $performance = $this->model_purchase_supplier_analytics_advanced->getSupplierPerformance($filter_data);
            
            $json['success'] = true;
            $json['performance'] = $performance;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getSupplierRanking() {
        $this->load->model('purchase/supplier_analytics_advanced');

        $json = array();

        try {
            $filter_data = array(
                'date_from' => $this->request->get['date_from'] ?? date('Y-m-01'),
                'date_to' => $this->request->get['date_to'] ?? date('Y-m-d'),
                'ranking_criteria' => $this->request->get['ranking_criteria'] ?? 'overall',
                'limit' => $this->request->get['limit'] ?? 20
            );

            $ranking = $this->model_purchase_supplier_analytics_advanced->getSupplierRanking($filter_data);
            
            $json['success'] = true;
            $json['ranking'] = $ranking;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getQualityAnalysis() {
        $this->load->model('purchase/supplier_analytics_advanced');

        $json = array();

        try {
            $filter_data = array(
                'supplier_id' => $this->request->get['supplier_id'] ?? null,
                'date_from' => $this->request->get['date_from'] ?? date('Y-m-01'),
                'date_to' => $this->request->get['date_to'] ?? date('Y-m-d'),
                'product_id' => $this->request->get['product_id'] ?? null
            );

            $quality = $this->model_purchase_supplier_analytics_advanced->getQualityAnalysis($filter_data);
            
            $json['success'] = true;
            $json['quality'] = $quality;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getDeliveryAnalysis() {
        $this->load->model('purchase/supplier_analytics_advanced');

        $json = array();

        try {
            $filter_data = array(
                'supplier_id' => $this->request->get['supplier_id'] ?? null,
                'date_from' => $this->request->get['date_from'] ?? date('Y-m-01'),
                'date_to' => $this->request->get['date_to'] ?? date('Y-m-d')
            );

            $delivery = $this->model_purchase_supplier_analytics_advanced->getDeliveryAnalysis($filter_data);
            
            $json['success'] = true;
            $json['delivery'] = $delivery;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getPriceAnalysis() {
        $this->load->model('purchase/supplier_analytics_advanced');

        $json = array();

        try {
            $filter_data = array(
                'supplier_id' => $this->request->get['supplier_id'] ?? null,
                'product_id' => $this->request->get['product_id'] ?? null,
                'date_from' => $this->request->get['date_from'] ?? date('Y-m-01'),
                'date_to' => $this->request->get['date_to'] ?? date('Y-m-d'),
                'comparison_type' => $this->request->get['comparison_type'] ?? 'market'
            );

            $price_analysis = $this->model_purchase_supplier_analytics_advanced->getPriceAnalysis($filter_data);
            
            $json['success'] = true;
            $json['price_analysis'] = $price_analysis;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getRiskAssessment() {
        $this->load->model('purchase/supplier_analytics_advanced');

        $json = array();

        try {
            $filter_data = array(
                'supplier_id' => $this->request->get['supplier_id'] ?? null,
                'assessment_type' => $this->request->get['assessment_type'] ?? 'comprehensive'
            );

            $risk_assessment = $this->model_purchase_supplier_analytics_advanced->getRiskAssessment($filter_data);
            
            $json['success'] = true;
            $json['risk_assessment'] = $risk_assessment;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getSupplierRecommendations() {
        $this->load->model('purchase/supplier_analytics_advanced');

        $json = array();

        try {
            $filter_data = array(
                'product_id' => $this->request->get['product_id'] ?? null,
                'category_id' => $this->request->get['category_id'] ?? null,
                'budget_range' => $this->request->get['budget_range'] ?? null,
                'quality_requirements' => $this->request->get['quality_requirements'] ?? null,
                'delivery_requirements' => $this->request->get['delivery_requirements'] ?? null
            );

            $recommendations = $this->model_purchase_supplier_analytics_advanced->getSupplierRecommendations($filter_data);
            
            $json['success'] = true;
            $json['recommendations'] = $recommendations;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function updateSupplierRating() {
        $this->load->model('purchase/supplier_analytics_advanced');
        $this->load->model('accounts/audit_trail');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            try {
                // التحقق من الصلاحيات
                if (!$this->user->hasPermission('modify', 'purchase/supplier_analytics_advanced')) {
                    throw new Exception('ليس لديك صلاحية لتحديث تقييم الموردين');
                }

                $rating_data = array(
                    'supplier_id' => $this->request->post['supplier_id'],
                    'quality_rating' => $this->request->post['quality_rating'],
                    'delivery_rating' => $this->request->post['delivery_rating'],
                    'price_rating' => $this->request->post['price_rating'],
                    'service_rating' => $this->request->post['service_rating'],
                    'overall_rating' => $this->request->post['overall_rating'],
                    'comments' => $this->request->post['comments'] ?? '',
                    'rating_period' => $this->request->post['rating_period'] ?? date('Y-m')
                );

                $result = $this->model_purchase_supplier_analytics_advanced->updateSupplierRating($rating_data);
                
                if ($result) {
                    $json['success'] = true;
                    $json['message'] = 'تم تحديث تقييم المورد بنجاح';
                    
                    // تسجيل في سجل المراجعة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'update_supplier_rating',
                        'table_name' => 'supplier_ratings',
                        'record_id' => $rating_data['supplier_id'],
                        'description' => 'تحديث تقييم المورد رقم: ' . $rating_data['supplier_id'] . ' - التقييم الإجمالي: ' . $rating_data['overall_rating'],
                        'module' => 'supplier_analytics'
                    ]);
                } else {
                    $json['error'] = 'فشل في تحديث تقييم المورد';
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

    public function generateSupplierReport() {
        $this->load->model('purchase/supplier_analytics_advanced');

        $json = array();

        try {
            $filter_data = array(
                'supplier_id' => $this->request->get['supplier_id'] ?? null,
                'date_from' => $this->request->get['date_from'] ?? date('Y-m-01'),
                'date_to' => $this->request->get['date_to'] ?? date('Y-m-d'),
                'report_type' => $this->request->get['report_type'] ?? 'comprehensive'
            );

            $report = $this->model_purchase_supplier_analytics_advanced->generateSupplierReport($filter_data);
            
            $json['success'] = true;
            $json['report'] = $report;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function exportAnalytics() {
        $this->load->model('purchase/supplier_analytics_advanced');

        $format = $this->request->get['format'] ?? 'excel';
        
        try {
            $filter_data = array(
                'date_from' => $this->request->get['date_from'] ?? date('Y-m-01'),
                'date_to' => $this->request->get['date_to'] ?? date('Y-m-d'),
                'supplier_id' => $this->request->get['supplier_id'] ?? null,
                'analytics_type' => $this->request->get['analytics_type'] ?? 'comprehensive'
            );

            $analytics_data = $this->model_purchase_supplier_analytics_advanced->getComprehensiveAnalytics($filter_data);
            
            switch ($format) {
                case 'excel':
                    $this->exportToExcel($analytics_data, $filter_data);
                    break;
                case 'pdf':
                    $this->exportToPdf($analytics_data, $filter_data);
                    break;
                case 'csv':
                    $this->exportToCsv($analytics_data, $filter_data);
                    break;
                default:
                    $this->exportToExcel($analytics_data, $filter_data);
            }
            
        } catch (Exception $e) {
            $this->session->data['error'] = 'خطأ في تصدير تحليلات الموردين: ' . $e->getMessage();
            $this->response->redirect($this->url->link('purchase/supplier_analytics_advanced', 'user_token=' . $this->session->data['user_token'], true));
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
            'href' => $this->url->link('purchase/supplier_analytics_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        // URLs للـ AJAX
        $data['performance_url'] = $this->url->link('purchase/supplier_analytics_advanced/getSupplierPerformance', 'user_token=' . $this->session->data['user_token'], true);
        $data['ranking_url'] = $this->url->link('purchase/supplier_analytics_advanced/getSupplierRanking', 'user_token=' . $this->session->data['user_token'], true);
        $data['quality_url'] = $this->url->link('purchase/supplier_analytics_advanced/getQualityAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['delivery_url'] = $this->url->link('purchase/supplier_analytics_advanced/getDeliveryAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['price_url'] = $this->url->link('purchase/supplier_analytics_advanced/getPriceAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['risk_url'] = $this->url->link('purchase/supplier_analytics_advanced/getRiskAssessment', 'user_token=' . $this->session->data['user_token'], true);
        $data['recommendations_url'] = $this->url->link('purchase/supplier_analytics_advanced/getSupplierRecommendations', 'user_token=' . $this->session->data['user_token'], true);
        $data['update_rating_url'] = $this->url->link('purchase/supplier_analytics_advanced/updateSupplierRating', 'user_token=' . $this->session->data['user_token'], true);
        $data['report_url'] = $this->url->link('purchase/supplier_analytics_advanced/generateSupplierReport', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_url'] = $this->url->link('purchase/supplier_analytics_advanced/exportAnalytics', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];

        // تحميل قوائم البيانات
        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        $this->load->model('catalog/product');
        $data['products'] = $this->model_catalog_product->getProducts();

        $this->load->model('catalog/category');
        $data['categories'] = $this->model_catalog_category->getCategories();

        // الصلاحيات
        $data['can_update_rating'] = $this->user->hasPermission('modify', 'purchase/supplier_analytics_advanced');

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

        $this->response->setOutput($this->load->view('purchase/supplier_analytics_advanced', $data));
    }

    private function exportToExcel($data, $filter_data) {
        // تنفيذ تصدير Excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="supplier_analytics_' . date('Y-m-d') . '.xlsx"');
        // كود تصدير Excel هنا
    }

    private function exportToPdf($data, $filter_data) {
        // تنفيذ تصدير PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="supplier_analytics_' . date('Y-m-d') . '.pdf"');
        // كود تصدير PDF هنا
    }

    private function exportToCsv($data, $filter_data) {
        // تنفيذ تصدير CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="supplier_analytics_' . date('Y-m-d') . '.csv"');
        // كود تصدير CSV هنا
    }
}
