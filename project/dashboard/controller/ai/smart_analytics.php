<?php
class ControllerAiSmartAnalytics extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('ai/smart_analytics');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('ai/smart_analytics');

        $this->getList();
    }

    public function getInsights() {
        $this->load->language('ai/smart_analytics');
        $this->load->model('ai/smart_analytics');

        $json = array();

        try {
            // رؤى الذكاء الاصطناعي
            $json['ai_insights'] = array(
                'sales_predictions' => $this->model_ai_smart_analytics->getSalesPredictions(),
                'customer_behavior' => $this->model_ai_smart_analytics->getCustomerBehaviorInsights(),
                'inventory_optimization' => $this->model_ai_smart_analytics->getInventoryOptimization(),
                'market_trends' => $this->model_ai_smart_analytics->getMarketTrends(),
                'risk_analysis' => $this->model_ai_smart_analytics->getRiskAnalysis()
            );

            // مؤشرات الأداء الذكية
            $json['smart_kpis'] = array(
                'revenue_forecast' => $this->model_ai_smart_analytics->getRevenueForecast(),
                'customer_lifetime_value' => $this->model_ai_smart_analytics->getCustomerLifetimeValue(),
                'churn_probability' => $this->model_ai_smart_analytics->getChurnProbability(),
                'cross_sell_opportunities' => $this->model_ai_smart_analytics->getCrossSellOpportunities(),
                'price_optimization' => $this->model_ai_smart_analytics->getPriceOptimization()
            );

            // التوصيات الذكية
            $json['recommendations'] = $this->model_ai_smart_analytics->getSmartRecommendations();

            // بيانات الرسوم البيانية المتقدمة
            $json['advanced_charts'] = array(
                'predictive_sales' => $this->model_ai_smart_analytics->getPredictiveSalesData(),
                'customer_segments' => $this->model_ai_smart_analytics->getCustomerSegmentationData(),
                'demand_forecasting' => $this->model_ai_smart_analytics->getDemandForecastingData(),
                'anomaly_detection' => $this->model_ai_smart_analytics->getAnomalyDetectionData()
            );

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function generatePrediction() {
        $this->load->language('ai/smart_analytics');
        $this->load->model('ai/smart_analytics');

        $json = array();

        if (isset($this->request->post['prediction_type'])) {
            $prediction_type = $this->request->post['prediction_type'];
            $parameters = isset($this->request->post['parameters']) ? $this->request->post['parameters'] : array();
            
            try {
                $prediction = $this->model_ai_smart_analytics->generatePrediction($prediction_type, $parameters);
                
                if ($prediction) {
                    $json['success'] = $this->language->get('text_prediction_generated');
                    $json['prediction'] = $prediction;
                } else {
                    $json['error'] = $this->language->get('error_prediction_failed');
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_prediction_type_required');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function trainModel() {
        $this->load->language('ai/smart_analytics');
        $this->load->model('ai/smart_analytics');

        $json = array();

        if (isset($this->request->post['model_type'])) {
            $model_type = $this->request->post['model_type'];
            $training_data = isset($this->request->post['training_data']) ? $this->request->post['training_data'] : array();
            
            try {
                $result = $this->model_ai_smart_analytics->trainModel($model_type, $training_data);
                
                if ($result['success']) {
                    $json['success'] = $this->language->get('text_model_trained');
                    $json['model_info'] = $result['model_info'];
                } else {
                    $json['error'] = $result['error'];
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_model_type_required');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function exportAnalysis() {
        $this->load->language('ai/smart_analytics');
        $this->load->model('ai/smart_analytics');

        if (isset($this->request->post['analysis_type'])) {
            $analysis_type = $this->request->post['analysis_type'];
            $date_from = isset($this->request->post['date_from']) ? $this->request->post['date_from'] : '';
            $date_to = isset($this->request->post['date_to']) ? $this->request->post['date_to'] : '';

            try {
                $analysis_data = $this->model_ai_smart_analytics->generateAnalysisReport($analysis_type, $date_from, $date_to);
                
                // إنشاء ملف Excel
                $this->load->library('excel');
                $filename = 'ai_smart_analytics_' . $analysis_type . '_' . date('Y-m-d') . '.xlsx';
                
                $excel_file = $this->excel->createReport($analysis_data, $filename);
                
                if ($excel_file) {
                    $json['success'] = $this->language->get('text_report_generated');
                    $json['download_url'] = $this->url->link('ai/smart_analytics/download', 'file=' . $filename . '&user_token=' . $this->session->data['user_token'], true);
                } else {
                    $json['error'] = $this->language->get('error_report_generation');
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_analysis_type_required');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function optimizeParameters() {
        $this->load->language('ai/smart_analytics');
        $this->load->model('ai/smart_analytics');

        $json = array();

        if (isset($this->request->post['optimization_target'])) {
            $target = $this->request->post['optimization_target'];
            $constraints = isset($this->request->post['constraints']) ? $this->request->post['constraints'] : array();
            
            try {
                $optimization = $this->model_ai_smart_analytics->optimizeParameters($target, $constraints);
                
                if ($optimization['success']) {
                    $json['success'] = $this->language->get('text_optimization_completed');
                    $json['optimization'] = $optimization['results'];
                } else {
                    $json['error'] = $optimization['error'];
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_optimization_target_required');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function getList() {
        if (isset($this->request->get['filter_date_from'])) {
            $filter_date_from = $this->request->get['filter_date_from'];
        } else {
            $filter_date_from = date('Y-m-01'); // بداية الشهر الحالي
        }

        if (isset($this->request->get['filter_date_to'])) {
            $filter_date_to = $this->request->get['filter_date_to'];
        } else {
            $filter_date_to = date('Y-m-d'); // اليوم
        }

        if (isset($this->request->get['filter_model'])) {
            $filter_model = $this->request->get['filter_model'];
        } else {
            $filter_model = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('ai/smart_analytics', 'user_token=' . $this->session->data['user_token'], true)
        );

        // إحصائيات الذكاء الاصطناعي
        $data['ai_stats'] = array(
            'active_models' => $this->model_ai_smart_analytics->getActiveModels(),
            'predictions_today' => $this->model_ai_smart_analytics->getPredictionsToday(),
            'accuracy_rate' => $this->model_ai_smart_analytics->getAccuracyRate(),
            'data_processed' => $this->model_ai_smart_analytics->getDataProcessed()
        );

        // قائمة النماذج المتاحة
        $data['available_models'] = $this->model_ai_smart_analytics->getAvailableModels();
        
        // أنواع التحليلات
        $data['analysis_types'] = array(
            'sales_forecast' => $this->language->get('text_sales_forecast'),
            'customer_analysis' => $this->language->get('text_customer_analysis'),
            'inventory_optimization' => $this->language->get('text_inventory_optimization'),
            'market_trends' => $this->language->get('text_market_trends'),
            'risk_assessment' => $this->language->get('text_risk_assessment')
        );

        // فلاتر البحث
        $data['filter_date_from'] = $filter_date_from;
        $data['filter_date_to'] = $filter_date_to;
        $data['filter_model'] = $filter_model;

        // روابط مهمة
        $data['dashboard'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
        $data['insights_url'] = $this->url->link('ai/smart_analytics/getInsights', 'user_token=' . $this->session->data['user_token'], true);
        $data['prediction_url'] = $this->url->link('ai/smart_analytics/generatePrediction', 'user_token=' . $this->session->data['user_token'], true);
        $data['training_url'] = $this->url->link('ai/smart_analytics/trainModel', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_url'] = $this->url->link('ai/smart_analytics/exportAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['optimization_url'] = $this->url->link('ai/smart_analytics/optimizeParameters', 'user_token=' . $this->session->data['user_token'], true);

        // رسائل النجاح والخطأ
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

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('ai/smart_analytics', $data));
    }

    public function download() {
        if (isset($this->request->get['file'])) {
            $file = basename($this->request->get['file']);
            $filepath = DIR_DOWNLOAD . $file;

            if (file_exists($filepath)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $file . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                
                // حذف الملف بعد التحميل
                unlink($filepath);
                exit;
            }
        }

        $this->response->redirect($this->url->link('ai/smart_analytics', 'user_token=' . $this->session->data['user_token'], true));
    }
}
