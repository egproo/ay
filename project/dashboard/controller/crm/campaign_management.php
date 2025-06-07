<?php
/**
 * إدارة الحملات التسويقية (Campaign Management Controller)
 *
 * الهدف: إنشاء وإدارة ومتابعة الحملات التسويقية المتكاملة
 * الميزات: حملات متعددة القنوات، أتمتة التسويق، تتبع الأداء، تحليل ROI
 * التكامل: مع CRM والمبيعات والإيميل والتحليلات والمحاسبة
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerCrmCampaignManagement extends Controller {

    private $error = [];

    /**
     * الصفحة الرئيسية لإدارة الحملات
     */
    public function index() {
        $this->load->language('crm/campaign_management');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('crm/campaign_management', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $this->load->model('crm/campaign_management');

        // إعداد المرشحات
        $filter_data = $this->getFilterData();

        // الحصول على الحملات
        $results = $this->model_crm_campaign_management->getCampaigns($filter_data);
        $total = $this->model_crm_campaign_management->getTotalCampaigns($filter_data);

        $data['campaigns'] = [];

        foreach ($results as $result) {
            $status_class = $this->getStatusClass($result['status']);
            $performance_class = $this->getPerformanceClass($result['performance_score']);

            $data['campaigns'][] = [
                'campaign_id' => $result['campaign_id'],
                'name' => $result['name'],
                'description' => $result['description'],
                'type' => $result['type'],
                'type_text' => $this->getCampaignTypeText($result['type']),
                'status' => $result['status'],
                'status_text' => $this->getStatusText($result['status']),
                'status_class' => $status_class,
                'budget' => number_format($result['budget'], 2),
                'spent' => number_format($result['spent'], 2),
                'remaining_budget' => number_format($result['budget'] - $result['spent'], 2),
                'budget_utilization' => $result['budget'] > 0 ? round(($result['spent'] / $result['budget']) * 100, 1) : 0,
                'target_audience' => number_format($result['target_audience']),
                'reached_audience' => number_format($result['reached_audience']),
                'reach_percentage' => $result['target_audience'] > 0 ? round(($result['reached_audience'] / $result['target_audience']) * 100, 1) : 0,
                'leads_generated' => number_format($result['leads_generated']),
                'conversions' => number_format($result['conversions']),
                'conversion_rate' => $result['leads_generated'] > 0 ? round(($result['conversions'] / $result['leads_generated']) * 100, 1) : 0,
                'revenue_generated' => number_format($result['revenue_generated'], 2),
                'roi' => $result['spent'] > 0 ? round((($result['revenue_generated'] - $result['spent']) / $result['spent']) * 100, 1) : 0,
                'performance_score' => number_format($result['performance_score'], 1),
                'performance_class' => $performance_class,
                'start_date' => date($this->language->get('date_format_short'), strtotime($result['start_date'])),
                'end_date' => date($this->language->get('date_format_short'), strtotime($result['end_date'])),
                'created_by' => $result['created_by_name'],
                'date_created' => date($this->language->get('date_format_short'), strtotime($result['date_created'])),
                'view' => $this->url->link('crm/campaign_management/view', 'user_token=' . $this->session->data['user_token'] . '&campaign_id=' . $result['campaign_id'], true),
                'edit' => $this->url->link('crm/campaign_management/edit', 'user_token=' . $this->session->data['user_token'] . '&campaign_id=' . $result['campaign_id'], true),
                'duplicate' => $this->url->link('crm/campaign_management/duplicate', 'user_token=' . $this->session->data['user_token'] . '&campaign_id=' . $result['campaign_id'], true),
                'analytics' => $this->url->link('crm/campaign_management/analytics', 'user_token=' . $this->session->data['user_token'] . '&campaign_id=' . $result['campaign_id'], true),
                'leads' => $this->url->link('crm/campaign_management/leads', 'user_token=' . $this->session->data['user_token'] . '&campaign_id=' . $result['campaign_id'], true)
            ];
        }

        // إعداد الروابط والأزرار
        $data['add'] = $this->url->link('crm/campaign_management/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['templates'] = $this->url->link('crm/campaign_management/templates', 'user_token=' . $this->session->data['user_token'], true);
        $data['bulk_actions'] = $this->url->link('crm/campaign_management/bulkActions', 'user_token=' . $this->session->data['user_token'], true);
        $data['export'] = $this->url->link('crm/campaign_management/export', 'user_token=' . $this->session->data['user_token'], true);
        $data['analytics_overview'] = $this->url->link('crm/campaign_management/analyticsOverview', 'user_token=' . $this->session->data['user_token'], true);

        // إعداد التصفح
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $filter_data['limit'];
        $pagination->url = $this->url->link('crm/campaign_management', 'user_token=' . $this->session->data['user_token'] . $this->getFilterUrl() . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($filter_data['page'] - 1) * $filter_data['limit']) + 1 : 0, ((($filter_data['page'] - 1) * $filter_data['limit']) > ($total - $filter_data['limit'])) ? $total : ((($filter_data['page'] - 1) * $filter_data['limit']) + $filter_data['limit']), $total, ceil($total / $filter_data['limit']));

        // إعداد المرشحات للعرض
        $data['filter_name'] = $filter_data['filter_name'];
        $data['filter_type'] = $filter_data['filter_type'];
        $data['filter_status'] = $filter_data['filter_status'];
        $data['filter_performance'] = $filter_data['filter_performance'];
        $data['filter_date_from'] = $filter_data['filter_date_from'];
        $data['filter_date_to'] = $filter_data['filter_date_to'];

        // إحصائيات سريعة
        $data['statistics'] = $this->getQuickStatistics();

        // الرسوم البيانية
        $data['charts'] = $this->getChartsData();

        // قوائم للفلاتر
        $data['campaign_types'] = $this->getCampaignTypes();
        $data['campaign_statuses'] = $this->getCampaignStatuses();
        $data['performance_levels'] = $this->getPerformanceLevels();

        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/campaign_management_list', $data));
    }

    /**
     * إضافة حملة جديدة
     */
    public function add() {
        $this->load->language('crm/campaign_management');

        $this->document->setTitle($this->language->get('heading_title_add'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('crm/campaign_management');

            $campaign_id = $this->model_crm_campaign_management->addCampaign($this->request->post);

            // إضافة سجل في نشاط النظام
            $this->load->model('tool/activity_log');
            $this->model_tool_activity_log->addActivity('campaign_add', 'crm', 'تم إنشاء حملة تسويقية جديدة: ' . $this->request->post['name'], $campaign_id);

            // إنشاء القيود المحاسبية للميزانية
            $this->createBudgetAccountingEntry($campaign_id, $this->request->post['budget']);

            $this->session->data['success'] = $this->language->get('text_success_add');

            $url = $this->getRedirectUrl();
            $this->response->redirect($this->url->link('crm/campaign_management', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * عرض تفاصيل الحملة
     */
    public function view() {
        $this->load->language('crm/campaign_management');

        if (isset($this->request->get['campaign_id'])) {
            $campaign_id = (int)$this->request->get['campaign_id'];

            $this->load->model('crm/campaign_management');

            $campaign_info = $this->model_crm_campaign_management->getCampaign($campaign_id);

            if ($campaign_info) {
                $this->document->setTitle($this->language->get('heading_title_view') . ' - ' . $campaign_info['name']);

                // الحصول على تفاصيل الحملة
                $campaign_details = $this->model_crm_campaign_management->getCampaignDetails($campaign_id);

                // الحصول على العملاء المحتملين
                $campaign_leads = $this->model_crm_campaign_management->getCampaignLeads($campaign_id);

                // الحصول على الأنشطة
                $campaign_activities = $this->model_crm_campaign_management->getCampaignActivities($campaign_id);

                // الحصول على التحليلات
                $campaign_analytics = $this->model_crm_campaign_management->getCampaignAnalytics($campaign_id);

                $data['campaign'] = $campaign_info;
                $data['campaign_details'] = $campaign_details;
                $data['campaign_leads'] = $campaign_leads;
                $data['campaign_activities'] = $campaign_activities;
                $data['campaign_analytics'] = $campaign_analytics;

                // حساب الإحصائيات
                $data['campaign_statistics'] = $this->calculateCampaignStatistics($campaign_info, $campaign_leads, $campaign_activities);

                // الرسوم البيانية
                $data['campaign_charts'] = $this->getCampaignCharts($campaign_id);

                $data['user_token'] = $this->session->data['user_token'];
                $data['header'] = $this->load->controller('common/header');
                $data['column_left'] = $this->load->controller('common/column_left');
                $data['footer'] = $this->load->controller('common/footer');

                $this->response->setOutput($this->load->view('crm/campaign_management_view', $data));
            } else {
                $this->session->data['error'] = $this->language->get('error_not_found');
                $this->response->redirect($this->url->link('crm/campaign_management', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('crm/campaign_management', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * تحليلات الحملة
     */
    public function analytics() {
        $this->load->language('crm/campaign_management');

        if (isset($this->request->get['campaign_id'])) {
            $campaign_id = (int)$this->request->get['campaign_id'];

            $this->load->model('crm/campaign_management');

            $campaign_info = $this->model_crm_campaign_management->getCampaign($campaign_id);

            if ($campaign_info) {
                $this->document->setTitle($this->language->get('heading_title_analytics') . ' - ' . $campaign_info['name']);

                // تحليلات مفصلة
                $data['analytics'] = [
                    'performance_metrics' => $this->model_crm_campaign_management->getPerformanceMetrics($campaign_id),
                    'audience_analysis' => $this->model_crm_campaign_management->getAudienceAnalysis($campaign_id),
                    'channel_performance' => $this->model_crm_campaign_management->getChannelPerformance($campaign_id),
                    'conversion_funnel' => $this->model_crm_campaign_management->getConversionFunnel($campaign_id),
                    'roi_analysis' => $this->model_crm_campaign_management->getROIAnalysis($campaign_id),
                    'time_series_data' => $this->model_crm_campaign_management->getTimeSeriesData($campaign_id)
                ];

                $data['campaign'] = $campaign_info;

                $data['user_token'] = $this->session->data['user_token'];
                $data['header'] = $this->load->controller('common/header');
                $data['column_left'] = $this->load->controller('common/column_left');
                $data['footer'] = $this->load->controller('common/footer');

                $this->response->setOutput($this->load->view('crm/campaign_management_analytics', $data));
            } else {
                $this->session->data['error'] = $this->language->get('error_not_found');
                $this->response->redirect($this->url->link('crm/campaign_management', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('crm/campaign_management', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * نسخ حملة
     */
    public function duplicate() {
        $this->load->language('crm/campaign_management');

        if (isset($this->request->get['campaign_id'])) {
            $campaign_id = (int)$this->request->get['campaign_id'];

            $this->load->model('crm/campaign_management');

            $new_campaign_id = $this->model_crm_campaign_management->duplicateCampaign($campaign_id);

            if ($new_campaign_id) {
                $this->session->data['success'] = $this->language->get('text_success_duplicate');
                $this->response->redirect($this->url->link('crm/campaign_management/edit', 'user_token=' . $this->session->data['user_token'] . '&campaign_id=' . $new_campaign_id, true));
            } else {
                $this->session->data['error'] = $this->language->get('error_duplicate');
                $this->response->redirect($this->url->link('crm/campaign_management', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('crm/campaign_management', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * دوال مساعدة
     */
    private function getFilterData() {
        $filter_data = [
            'filter_name' => $this->request->get['filter_name'] ?? '',
            'filter_type' => $this->request->get['filter_type'] ?? '',
            'filter_status' => $this->request->get['filter_status'] ?? '',
            'filter_performance' => $this->request->get['filter_performance'] ?? '',
            'filter_date_from' => $this->request->get['filter_date_from'] ?? '',
            'filter_date_to' => $this->request->get['filter_date_to'] ?? '',
            'sort' => $this->request->get['sort'] ?? 'date_created',
            'order' => $this->request->get['order'] ?? 'DESC',
            'start' => ($this->request->get['page'] ?? 1 - 1) * ($this->request->get['limit'] ?? 20),
            'limit' => $this->request->get['limit'] ?? 20,
            'page' => $this->request->get['page'] ?? 1
        ];

        return $filter_data;
    }

    private function getStatusClass($status) {
        $classes = [
            'draft' => 'default',
            'active' => 'success',
            'paused' => 'warning',
            'completed' => 'info',
            'cancelled' => 'danger'
        ];

        return $classes[$status] ?? 'default';
    }

    private function getPerformanceClass($score) {
        if ($score >= 80) return 'success';
        if ($score >= 60) return 'info';
        if ($score >= 40) return 'warning';
        return 'danger';
    }

    private function getCampaignTypeText($type) {
        $types = [
            'email' => $this->language->get('text_type_email'),
            'social_media' => $this->language->get('text_type_social'),
            'ppc' => $this->language->get('text_type_ppc'),
            'content' => $this->language->get('text_type_content'),
            'seo' => $this->language->get('text_type_seo'),
            'affiliate' => $this->language->get('text_type_affiliate'),
            'event' => $this->language->get('text_type_event'),
            'print' => $this->language->get('text_type_print'),
            'radio' => $this->language->get('text_type_radio'),
            'tv' => $this->language->get('text_type_tv')
        ];

        return $types[$type] ?? $type;
    }

    private function getStatusText($status) {
        $statuses = [
            'draft' => $this->language->get('text_status_draft'),
            'active' => $this->language->get('text_status_active'),
            'paused' => $this->language->get('text_status_paused'),
            'completed' => $this->language->get('text_status_completed'),
            'cancelled' => $this->language->get('text_status_cancelled')
        ];

        return $statuses[$status] ?? $status;
    }

    private function getQuickStatistics() {
        $this->load->model('crm/campaign_management');

        return [
            'total_campaigns' => $this->model_crm_campaign_management->getTotalCampaigns([]),
            'active_campaigns' => $this->model_crm_campaign_management->getActiveCampaigns(),
            'total_budget' => $this->model_crm_campaign_management->getTotalBudget(),
            'total_spent' => $this->model_crm_campaign_management->getTotalSpent(),
            'total_leads' => $this->model_crm_campaign_management->getTotalLeadsGenerated(),
            'avg_roi' => $this->model_crm_campaign_management->getAverageROI(),
            'best_performing_campaign' => $this->model_crm_campaign_management->getBestPerformingCampaign(),
            'conversion_rate' => $this->model_crm_campaign_management->getOverallConversionRate()
        ];
    }

    private function getChartsData() {
        $this->load->model('crm/campaign_management');

        return [
            'budget_vs_spent' => $this->model_crm_campaign_management->getBudgetVsSpentChart(),
            'campaign_performance' => $this->model_crm_campaign_management->getPerformanceChart(),
            'leads_by_campaign' => $this->model_crm_campaign_management->getLeadsByCampaign(),
            'roi_comparison' => $this->model_crm_campaign_management->getROIComparisonChart()
        ];
    }

    private function getCampaignTypes() {
        return [
            'email' => $this->language->get('text_type_email'),
            'social_media' => $this->language->get('text_type_social'),
            'ppc' => $this->language->get('text_type_ppc'),
            'content' => $this->language->get('text_type_content'),
            'seo' => $this->language->get('text_type_seo'),
            'affiliate' => $this->language->get('text_type_affiliate'),
            'event' => $this->language->get('text_type_event'),
            'print' => $this->language->get('text_type_print'),
            'radio' => $this->language->get('text_type_radio'),
            'tv' => $this->language->get('text_type_tv')
        ];
    }

    private function getCampaignStatuses() {
        return [
            'draft' => $this->language->get('text_status_draft'),
            'active' => $this->language->get('text_status_active'),
            'paused' => $this->language->get('text_status_paused'),
            'completed' => $this->language->get('text_status_completed'),
            'cancelled' => $this->language->get('text_status_cancelled')
        ];
    }

    private function getPerformanceLevels() {
        return [
            'excellent' => $this->language->get('text_performance_excellent'),
            'good' => $this->language->get('text_performance_good'),
            'average' => $this->language->get('text_performance_average'),
            'poor' => $this->language->get('text_performance_poor')
        ];
    }

    private function calculateCampaignStatistics($campaign, $leads, $activities) {
        $total_leads = count($leads);
        $converted_leads = count(array_filter($leads, function($lead) {
            return $lead['status'] == 'converted';
        }));

        $conversion_rate = $total_leads > 0 ? ($converted_leads / $total_leads) * 100 : 0;

        $total_activities = count($activities);
        $engagement_rate = $campaign['reached_audience'] > 0 ? ($total_activities / $campaign['reached_audience']) * 100 : 0;

        return [
            'total_leads' => $total_leads,
            'converted_leads' => $converted_leads,
            'conversion_rate' => round($conversion_rate, 2),
            'total_activities' => $total_activities,
            'engagement_rate' => round($engagement_rate, 2),
            'cost_per_lead' => $total_leads > 0 ? round($campaign['spent'] / $total_leads, 2) : 0,
            'cost_per_conversion' => $converted_leads > 0 ? round($campaign['spent'] / $converted_leads, 2) : 0
        ];
    }

    private function createBudgetAccountingEntry($campaign_id, $budget) {
        $this->load->model('accounting/journal_entry');

        $entries = [
            [
                'account_code' => '5311', // مصروفات تسويق
                'debit' => $budget,
                'credit' => 0,
                'description' => 'ميزانية حملة تسويقية رقم ' . $campaign_id
            ],
            [
                'account_code' => '2311', // التزامات تسويقية
                'debit' => 0,
                'credit' => $budget,
                'description' => 'ميزانية حملة تسويقية رقم ' . $campaign_id
            ]
        ];

        $journal_data = [
            'reference' => 'CAMP-BUDGET-' . $campaign_id,
            'description' => 'قيد ميزانية حملة تسويقية',
            'entries' => $entries
        ];

        return $this->model_accounting_journal_entry->addJournalEntry($journal_data);
    }

    private function validateForm() {
        if (!$this->user->hasPermission('modify', 'crm/campaign_management')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen(trim($this->request->post['name'])) < 1) || (utf8_strlen($this->request->post['name']) > 255)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if ($this->request->post['budget'] <= 0) {
            $this->error['budget'] = $this->language->get('error_budget');
        }

        if (empty($this->request->post['start_date'])) {
            $this->error['start_date'] = $this->language->get('error_start_date');
        }

        if (empty($this->request->post['end_date'])) {
            $this->error['end_date'] = $this->language->get('error_end_date');
        }

        return !$this->error;
    }

    /**
     * تحليل الحملة
     */
    public function analyze() {
        $this->load->language('crm/campaign_management');

        $json = array();

        // فحص الصلاحيات
        if (!$this->user->hasPermission('access', 'crm/campaign_management')) {
            $json['error'] = $this->language->get('error_permission');
        }

        $campaign_id = isset($this->request->post['campaign_id']) ? (int)$this->request->post['campaign_id'] : 0;

        if (!$json && !$campaign_id) {
            $json['error'] = $this->language->get('error_campaign_required');
        }

        if (!$json) {
            try {
                // تحليل شامل للحملة
                $analysis_results = $this->performCampaignAnalysis($campaign_id);

                $json['success'] = true;
                $json['data'] = $analysis_results;

            } catch (Exception $e) {
                $json['error'] = 'حدث خطأ أثناء تحليل الحملة: ' . $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تحسين الحملة
     */
    public function optimize() {
        $this->load->language('crm/campaign_management');

        $json = array();

        // فحص الصلاحيات
        if (!$this->user->hasPermission('modify', 'crm/campaign_management')) {
            $json['error'] = $this->language->get('error_permission');
        }

        $campaign_id = isset($this->request->post['campaign_id']) ? (int)$this->request->post['campaign_id'] : 0;

        if (!$json && !$campaign_id) {
            $json['error'] = $this->language->get('error_campaign_required');
        }

        if (!$json) {
            try {
                // تحليل الحملة الحالية
                $current_analysis = $this->performCampaignAnalysis($campaign_id);

                // تحديد فرص التحسين
                $optimization_opportunities = $this->identifyOptimizationOpportunities($current_analysis);

                // تطبيق التحسينات
                $optimization_results = $this->applyCampaignOptimizations($campaign_id, $optimization_opportunities);

                $json['success'] = true;
                $json['data'] = $optimization_results;
                $json['optimized_data'] = $this->model_crm_campaign_management->getCampaign($campaign_id);

            } catch (Exception $e) {
                $json['error'] = 'حدث خطأ أثناء تحسين الحملة: ' . $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * الحصول على المقاييس في الوقت الفعلي
     */
    public function getRealTimeMetrics() {
        $json = array();

        // فحص الصلاحيات
        if (!$this->user->hasPermission('access', 'crm/campaign_management')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $json['success'] = true;
            $json['data'] = array(
                'active_campaigns' => $this->getActiveCampaignsCount(),
                'total_budget' => $this->getTotalBudget(),
                'total_spent' => $this->getTotalSpent(),
                'average_roi' => $this->getAverageROI(),
                'top_performing_campaigns' => $this->getTopPerformingCampaigns(),
                'budget_utilization' => $this->getBudgetUtilization(),
                'conversion_rates' => $this->getConversionRates(),
                'channel_performance' => $this->getChannelPerformance()
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * الحصول على قواعد الأتمتة
     */
    public function getAutomationRules() {
        $json = array();

        // فحص الصلاحيات
        if (!$this->user->hasPermission('access', 'crm/campaign_management')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $json['success'] = true;
            $json['data'] = $this->automation_rules;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تهيئة أنواع الحملات
     */
    private function initializeCampaignTypes() {
        $this->campaign_types = array(
            'email' => array(
                'name' => 'البريد الإلكتروني',
                'description' => 'حملات البريد الإلكتروني المباشر',
                'cost_per_lead' => 2.5,
                'avg_conversion_rate' => 3.2
            ),
            'social_media' => array(
                'name' => 'وسائل التواصل الاجتماعي',
                'description' => 'حملات على منصات التواصل الاجتماعي',
                'cost_per_lead' => 4.8,
                'avg_conversion_rate' => 2.1
            ),
            'paid_search' => array(
                'name' => 'البحث المدفوع',
                'description' => 'إعلانات محركات البحث',
                'cost_per_lead' => 8.2,
                'avg_conversion_rate' => 4.7
            ),
            'display' => array(
                'name' => 'الإعلانات المرئية',
                'description' => 'إعلانات البانر والعرض',
                'cost_per_lead' => 3.1,
                'avg_conversion_rate' => 1.8
            ),
            'content' => array(
                'name' => 'تسويق المحتوى',
                'description' => 'حملات المحتوى والمدونات',
                'cost_per_lead' => 1.9,
                'avg_conversion_rate' => 5.3
            ),
            'event' => array(
                'name' => 'الفعاليات',
                'description' => 'المعارض والندوات والفعاليات',
                'cost_per_lead' => 15.6,
                'avg_conversion_rate' => 8.9
            ),
            'referral' => array(
                'name' => 'الإحالات',
                'description' => 'برامج الإحالة والتوصيات',
                'cost_per_lead' => 5.4,
                'avg_conversion_rate' => 12.3
            ),
            'retargeting' => array(
                'name' => 'إعادة الاستهداف',
                'description' => 'استهداف الزوار السابقين',
                'cost_per_lead' => 6.7,
                'avg_conversion_rate' => 7.2
            )
        );
    }

    /**
     * تحميل حالات الحملات
     */
    private function loadCampaignStatuses() {
        $this->campaign_statuses = array(
            'draft' => array(
                'name' => 'مسودة',
                'description' => 'الحملة قيد الإعداد',
                'color' => '#6c757d'
            ),
            'active' => array(
                'name' => 'نشطة',
                'description' => 'الحملة قيد التشغيل',
                'color' => '#28a745'
            ),
            'paused' => array(
                'name' => 'متوقفة مؤقتاً',
                'description' => 'الحملة متوقفة مؤقتاً',
                'color' => '#ffc107'
            ),
            'completed' => array(
                'name' => 'مكتملة',
                'description' => 'الحملة انتهت بنجاح',
                'color' => '#007bff'
            ),
            'cancelled' => array(
                'name' => 'ملغية',
                'description' => 'الحملة تم إلغاؤها',
                'color' => '#dc3545'
            )
        );
    }

    /**
     * تحميل قواعد الأتمتة
     */
    private function loadAutomationRules() {
        $this->automation_rules = array(
            'budget_alert' => array(
                'name' => 'تنبيه الميزانية',
                'description' => 'تنبيه عند استنفاد نسبة معينة من الميزانية',
                'trigger' => 'budget_threshold',
                'action' => 'send_notification',
                'enabled' => true
            ),
            'performance_optimization' => array(
                'name' => 'تحسين الأداء',
                'description' => 'تحسين تلقائي للحملات ضعيفة الأداء',
                'trigger' => 'low_performance',
                'action' => 'optimize_campaign',
                'enabled' => true
            ),
            'auto_pause' => array(
                'name' => 'إيقاف تلقائي',
                'description' => 'إيقاف الحملات عند تجاوز الميزانية',
                'trigger' => 'budget_exceeded',
                'action' => 'pause_campaign',
                'enabled' => true
            ),
            'lead_scoring' => array(
                'name' => 'تقييم العملاء المحتملين',
                'description' => 'تقييم تلقائي للعملاء المحتملين الجدد',
                'trigger' => 'new_lead',
                'action' => 'calculate_score',
                'enabled' => true
            )
        );
    }

    /**
     * معالجة الفلاتر
     */
    private function processFilters() {
        $filter_data = array();

        // فلتر اسم الحملة
        if (isset($this->request->get['filter_name'])) {
            $filter_data['filter_name'] = $this->request->get['filter_name'];
        }

        // فلتر نوع الحملة
        if (isset($this->request->get['filter_type'])) {
            $filter_data['filter_type'] = $this->request->get['filter_type'];
        }

        // فلتر حالة الحملة
        if (isset($this->request->get['filter_status'])) {
            $filter_data['filter_status'] = $this->request->get['filter_status'];
        }

        // فلتر مستوى الأداء
        if (isset($this->request->get['filter_performance'])) {
            $filter_data['filter_performance'] = $this->request->get['filter_performance'];
        }

        // فلتر المسؤول
        if (isset($this->request->get['filter_assigned_to'])) {
            $filter_data['filter_assigned_to'] = $this->request->get['filter_assigned_to'];
        }

        // فلتر التاريخ
        if (isset($this->request->get['filter_date_from'])) {
            $filter_data['filter_date_from'] = $this->request->get['filter_date_from'];
        }

        if (isset($this->request->get['filter_date_to'])) {
            $filter_data['filter_date_to'] = $this->request->get['filter_date_to'];
        }

        // الترتيب
        if (isset($this->request->get['sort'])) {
            $filter_data['sort'] = $this->request->get['sort'];
        } else {
            $filter_data['sort'] = 'date_created';
        }

        if (isset($this->request->get['order'])) {
            $filter_data['order'] = $this->request->get['order'];
        } else {
            $filter_data['order'] = 'DESC';
        }

        // التصفح
        if (isset($this->request->get['page'])) {
            $filter_data['page'] = (int)$this->request->get['page'];
        } else {
            $filter_data['page'] = 1;
        }

        $filter_data['limit'] = 20;
        $filter_data['start'] = ($filter_data['page'] - 1) * $filter_data['limit'];

        return $filter_data;
    }
}
