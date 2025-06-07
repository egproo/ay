<?php
/**
 * تقييم العملاء المحتملين (Lead Scoring Controller)
 *
 * الهدف: تقييم وترتيب العملاء المحتملين حسب احتمالية التحويل
 * الميزات: نظام نقاط ذكي، تحليل سلوك، توقعات مبيعات، تكامل مع CRM
 * التكامل: مع المبيعات والتسويق والتحليلات والذكاء الاصطناعي
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerCrmLeadScoring extends Controller {

    private $error = [];

    /**
     * الصفحة الرئيسية لتقييم العملاء المحتملين
     */
    public function index() {
        $this->load->language('crm/lead_scoring');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('crm/lead_scoring', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $this->load->model('crm/lead_scoring');

        // إعداد المرشحات
        $filter_data = $this->getFilterData();

        // الحصول على العملاء المحتملين مع النقاط
        $results = $this->model_crm_lead_scoring->getLeadsWithScores($filter_data);
        $total = $this->model_crm_lead_scoring->getTotalLeadsWithScores($filter_data);

        $data['leads'] = [];

        foreach ($results as $result) {
            $score_class = $this->getScoreClass($result['total_score']);
            $priority_text = $this->getPriorityText($result['priority']);

            $data['leads'][] = [
                'lead_id' => $result['lead_id'],
                'customer_name' => $result['customer_name'],
                'email' => $result['email'],
                'phone' => $result['phone'],
                'company' => $result['company'],
                'source' => $result['source'],
                'status' => $result['status'],
                'status_text' => $this->getStatusText($result['status']),
                'total_score' => $result['total_score'],
                'score_class' => $score_class,
                'priority' => $result['priority'],
                'priority_text' => $priority_text,
                'priority_class' => $this->getPriorityClass($result['priority']),
                'conversion_probability' => $result['conversion_probability'] . '%',
                'estimated_value' => number_format($result['estimated_value'], 2),
                'last_activity' => $result['last_activity'] ? date($this->language->get('date_format_short'), strtotime($result['last_activity'])) : '-',
                'assigned_to' => $result['assigned_to_name'],
                'date_created' => date($this->language->get('date_format_short'), strtotime($result['date_created'])),
                'view' => $this->url->link('crm/lead_scoring/view', 'user_token=' . $this->session->data['user_token'] . '&lead_id=' . $result['lead_id'], true),
                'edit' => $this->url->link('crm/lead_scoring/edit', 'user_token=' . $this->session->data['user_token'] . '&lead_id=' . $result['lead_id'], true),
                'convert' => $this->url->link('crm/lead_scoring/convert', 'user_token=' . $this->session->data['user_token'] . '&lead_id=' . $result['lead_id'], true),
                'activities' => $this->url->link('crm/lead_activity', 'user_token=' . $this->session->data['user_token'] . '&lead_id=' . $result['lead_id'], true)
            ];
        }

        // إعداد الروابط والأزرار
        $data['add'] = $this->url->link('crm/lead_scoring/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['bulk_score'] = $this->url->link('crm/lead_scoring/bulkScore', 'user_token=' . $this->session->data['user_token'], true);
        $data['export'] = $this->url->link('crm/lead_scoring/export', 'user_token=' . $this->session->data['user_token'], true);
        $data['scoring_rules'] = $this->url->link('crm/lead_scoring/rules', 'user_token=' . $this->session->data['user_token'], true);
        $data['analytics'] = $this->url->link('crm/lead_scoring/analytics', 'user_token=' . $this->session->data['user_token'], true);

        // إعداد التصفح
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $filter_data['limit'];
        $pagination->url = $this->url->link('crm/lead_scoring', 'user_token=' . $this->session->data['user_token'] . $this->getFilterUrl() . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($filter_data['page'] - 1) * $filter_data['limit']) + 1 : 0, ((($filter_data['page'] - 1) * $filter_data['limit']) > ($total - $filter_data['limit'])) ? $total : ((($filter_data['page'] - 1) * $filter_data['limit']) + $filter_data['limit']), $total, ceil($total / $filter_data['limit']));

        // إعداد المرشحات للعرض
        $data['filter_name'] = $filter_data['filter_name'];
        $data['filter_score_range'] = $filter_data['filter_score_range'];
        $data['filter_priority'] = $filter_data['filter_priority'];
        $data['filter_status'] = $filter_data['filter_status'];
        $data['filter_source'] = $filter_data['filter_source'];
        $data['filter_assigned_to'] = $filter_data['filter_assigned_to'];

        // إحصائيات سريعة
        $data['statistics'] = $this->getQuickStatistics();

        // قوائم للفلاتر
        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();
        $data['sources'] = $this->getLeadSources();
        $data['score_ranges'] = $this->getScoreRanges();

        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/lead_scoring_list', $data));
    }

    /**
     * عرض تفاصيل تقييم العميل المحتمل
     */
    public function view() {
        $this->load->language('crm/lead_scoring');

        if (isset($this->request->get['lead_id'])) {
            $lead_id = (int)$this->request->get['lead_id'];

            $this->load->model('crm/lead_scoring');

            $lead_info = $this->model_crm_lead_scoring->getLeadWithScore($lead_id);

            if ($lead_info) {
                $this->document->setTitle($this->language->get('heading_title_view') . ' - ' . $lead_info['customer_name']);

                // الحصول على تفاصيل النقاط
                $score_breakdown = $this->model_crm_lead_scoring->getScoreBreakdown($lead_id);

                // الحصول على تاريخ الأنشطة
                $activities = $this->model_crm_lead_scoring->getLeadActivities($lead_id);

                // الحصول على التوقعات
                $predictions = $this->model_crm_lead_scoring->getConversionPredictions($lead_id);

                $data['lead'] = $lead_info;
                $data['score_breakdown'] = $score_breakdown;
                $data['activities'] = $activities;
                $data['predictions'] = $predictions;

                // حساب الإحصائيات
                $data['lead_statistics'] = $this->calculateLeadStatistics($lead_info, $activities);

                $data['user_token'] = $this->session->data['user_token'];
                $data['header'] = $this->load->controller('common/header');
                $data['column_left'] = $this->load->controller('common/column_left');
                $data['footer'] = $this->load->controller('common/footer');

                $this->response->setOutput($this->load->view('crm/lead_scoring_view', $data));
            } else {
                $this->session->data['error'] = $this->language->get('error_not_found');
                $this->response->redirect($this->url->link('crm/lead_scoring', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('crm/lead_scoring', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * إعادة حساب النقاط
     */
    public function recalculate() {
        $this->load->language('crm/lead_scoring');

        if (isset($this->request->get['lead_id'])) {
            $lead_id = (int)$this->request->get['lead_id'];

            $this->load->model('crm/lead_scoring');

            $result = $this->model_crm_lead_scoring->recalculateScore($lead_id);

            if ($result) {
                $this->session->data['success'] = $this->language->get('text_success_recalculate');
            } else {
                $this->session->data['error'] = $this->language->get('error_recalculate');
            }

            $this->response->redirect($this->url->link('crm/lead_scoring/view', 'user_token=' . $this->session->data['user_token'] . '&lead_id=' . $lead_id, true));
        } else {
            $this->response->redirect($this->url->link('crm/lead_scoring', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * تحويل العميل المحتمل إلى عميل
     */
    public function convert() {
        $this->load->language('crm/lead_scoring');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($this->request->get['lead_id'])) {
            $lead_id = (int)$this->request->get['lead_id'];

            $this->load->model('crm/lead_scoring');

            $result = $this->model_crm_lead_scoring->convertLead($lead_id, $this->request->post);

            if ($result) {
                // إضافة سجل في نشاط النظام
                $this->load->model('tool/activity_log');
                $this->model_tool_activity_log->addActivity('lead_converted', 'crm', 'تم تحويل عميل محتمل إلى عميل', $lead_id);

                $this->session->data['success'] = $this->language->get('text_success_convert');
                $this->response->redirect($this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $result, true));
            } else {
                $this->session->data['error'] = $this->language->get('error_convert');
                $this->response->redirect($this->url->link('crm/lead_scoring/view', 'user_token=' . $this->session->data['user_token'] . '&lead_id=' . $lead_id, true));
            }
        }

        $this->view();
    }

    /**
     * إدارة قواعد التقييم
     */
    public function rules() {
        $this->load->language('crm/lead_scoring');

        $this->document->setTitle($this->language->get('heading_title_rules'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateRules()) {
            $this->load->model('crm/lead_scoring');

            $this->model_crm_lead_scoring->updateScoringRules($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success_rules');

            $this->response->redirect($this->url->link('crm/lead_scoring/rules', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->load->model('crm/lead_scoring');

        $data['rules'] = $this->model_crm_lead_scoring->getScoringRules();
        $data['rule_categories'] = $this->getRuleCategories();

        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/lead_scoring_rules', $data));
    }

    /**
     * تحليلات التقييم
     */
    public function analytics() {
        $this->load->language('crm/lead_scoring');

        $this->document->setTitle($this->language->get('heading_title_analytics'));

        $this->load->model('crm/lead_scoring');

        // إحصائيات شاملة
        $data['analytics'] = [
            'score_distribution' => $this->model_crm_lead_scoring->getScoreDistribution(),
            'conversion_rates' => $this->model_crm_lead_scoring->getConversionRates(),
            'source_performance' => $this->model_crm_lead_scoring->getSourcePerformance(),
            'monthly_trends' => $this->model_crm_lead_scoring->getMonthlyTrends(),
            'top_performers' => $this->model_crm_lead_scoring->getTopPerformers(),
            'prediction_accuracy' => $this->model_crm_lead_scoring->getPredictionAccuracy()
        ];

        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/lead_scoring_analytics', $data));
    }

    /**
     * دوال مساعدة
     */
    private function getFilterData() {
        $filter_data = [
            'filter_name' => $this->request->get['filter_name'] ?? '',
            'filter_score_range' => $this->request->get['filter_score_range'] ?? '',
            'filter_priority' => $this->request->get['filter_priority'] ?? '',
            'filter_status' => $this->request->get['filter_status'] ?? '',
            'filter_source' => $this->request->get['filter_source'] ?? '',
            'filter_assigned_to' => $this->request->get['filter_assigned_to'] ?? '',
            'sort' => $this->request->get['sort'] ?? 'total_score',
            'order' => $this->request->get['order'] ?? 'DESC',
            'start' => ($this->request->get['page'] ?? 1 - 1) * ($this->request->get['limit'] ?? 20),
            'limit' => $this->request->get['limit'] ?? 20,
            'page' => $this->request->get['page'] ?? 1
        ];

        return $filter_data;
    }

    private function getScoreClass($score) {
        if ($score >= 80) return 'success';
        if ($score >= 60) return 'warning';
        if ($score >= 40) return 'info';
        return 'danger';
    }

    private function getPriorityText($priority) {
        switch ($priority) {
            case 'hot': return $this->language->get('text_priority_hot');
            case 'warm': return $this->language->get('text_priority_warm');
            case 'cold': return $this->language->get('text_priority_cold');
            default: return $this->language->get('text_priority_unknown');
        }
    }

    private function getPriorityClass($priority) {
        switch ($priority) {
            case 'hot': return 'danger';
            case 'warm': return 'warning';
            case 'cold': return 'info';
            default: return 'default';
        }
    }

    private function getStatusText($status) {
        switch ($status) {
            case 'new': return $this->language->get('text_status_new');
            case 'contacted': return $this->language->get('text_status_contacted');
            case 'qualified': return $this->language->get('text_status_qualified');
            case 'proposal': return $this->language->get('text_status_proposal');
            case 'negotiation': return $this->language->get('text_status_negotiation');
            case 'converted': return $this->language->get('text_status_converted');
            case 'lost': return $this->language->get('text_status_lost');
            default: return $this->language->get('text_status_unknown');
        }
    }

    private function getQuickStatistics() {
        $this->load->model('crm/lead_scoring');

        return [
            'total_leads' => $this->model_crm_lead_scoring->getTotalLeads(),
            'hot_leads' => $this->model_crm_lead_scoring->getHotLeads(),
            'avg_score' => $this->model_crm_lead_scoring->getAverageScore(),
            'conversion_rate' => $this->model_crm_lead_scoring->getConversionRate(),
            'monthly_conversions' => $this->model_crm_lead_scoring->getMonthlyConversions(),
            'pipeline_value' => $this->model_crm_lead_scoring->getPipelineValue()
        ];
    }

    private function getLeadSources() {
        return [
            'website' => $this->language->get('text_source_website'),
            'social_media' => $this->language->get('text_source_social'),
            'email' => $this->language->get('text_source_email'),
            'phone' => $this->language->get('text_source_phone'),
            'referral' => $this->language->get('text_source_referral'),
            'advertisement' => $this->language->get('text_source_ad'),
            'event' => $this->language->get('text_source_event'),
            'other' => $this->language->get('text_source_other')
        ];
    }

    private function getScoreRanges() {
        return [
            '80-100' => $this->language->get('text_score_hot'),
            '60-79' => $this->language->get('text_score_warm'),
            '40-59' => $this->language->get('text_score_medium'),
            '0-39' => $this->language->get('text_score_cold')
        ];
    }

    private function getRuleCategories() {
        return [
            'demographic' => $this->language->get('text_category_demographic'),
            'behavioral' => $this->language->get('text_category_behavioral'),
            'engagement' => $this->language->get('text_category_engagement'),
            'company' => $this->language->get('text_category_company'),
            'source' => $this->language->get('text_category_source')
        ];
    }

    private function calculateLeadStatistics($lead, $activities) {
        $total_activities = count($activities);
        $last_activity_days = $lead['last_activity'] ? (time() - strtotime($lead['last_activity'])) / (24 * 60 * 60) : 0;

        return [
            'total_activities' => $total_activities,
            'last_activity_days' => round($last_activity_days),
            'engagement_score' => $this->calculateEngagementScore($activities),
            'days_in_pipeline' => (time() - strtotime($lead['date_created'])) / (24 * 60 * 60)
        ];
    }

    private function calculateEngagementScore($activities) {
        $score = 0;
        foreach ($activities as $activity) {
            switch ($activity['type']) {
                case 'email_open': $score += 2; break;
                case 'email_click': $score += 5; break;
                case 'website_visit': $score += 3; break;
                case 'form_submit': $score += 10; break;
                case 'phone_call': $score += 15; break;
                case 'meeting': $score += 20; break;
            }
        }
        return min($score, 100);
    }

    private function validateRules() {
        if (!$this->user->hasPermission('modify', 'crm/lead_scoring')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    /**
     * التقييم المجمع للعملاء المحتملين
     */
    public function bulkScore() {
        $this->load->language('crm/lead_scoring');

        $json = array();

        // فحص الصلاحيات
        if (!$this->user->hasPermission('modify', 'crm/lead_scoring')) {
            $json['error'] = $this->language->get('error_permission');
        }

        $selected = isset($this->request->post['selected']) ? $this->request->post['selected'] : array();
        $method = isset($this->request->post['method']) ? $this->request->post['method'] : 'auto';

        if (!$json && empty($selected)) {
            $json['error'] = $this->language->get('error_no_selection');
        }

        if (!$json) {
            try {
                $updated_count = 0;

                foreach ($selected as $lead_id) {
                    $lead_id = (int)$lead_id;

                    if ($lead_id > 0) {
                        // إعادة حساب النقاط حسب الطريقة المحددة
                        $new_score = $this->calculateLeadScore($lead_id, $method);

                        // تحديث النقاط في قاعدة البيانات
                        $this->model_crm_lead_scoring->updateLeadScore($lead_id, $new_score);

                        $updated_count++;
                    }
                }

                // تسجيل النشاط المجمع
                $this->model_crm_activity->addActivity(array(
                    'activity_type' => 'bulk_score_update',
                    'description' => 'تم تحديث نقاط ' . $updated_count . ' عميل محتمل بطريقة ' . $method,
                    'user_id' => $this->user->getId(),
                    'date_created' => date('Y-m-d H:i:s')
                ));

                $json['success'] = sprintf($this->language->get('text_success_bulk_score'), $updated_count);

            } catch (Exception $e) {
                $json['error'] = 'حدث خطأ أثناء التقييم المجمع: ' . $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تصدير بيانات العملاء المحتملين
     */
    public function export() {
        // فحص الصلاحيات
        if (!$this->user->hasPermission('access', 'crm/lead_scoring')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('crm/lead_scoring', 'user_token=' . $this->session->data['user_token'], true));
        }

        $format = isset($this->request->get['format']) ? $this->request->get['format'] : 'excel';

        // معالجة الفلاتر
        $filter_data = $this->processFilters();
        $filter_data['limit'] = 10000; // حد أقصى للتصدير

        // الحصول على البيانات
        $leads = $this->model_crm_lead_scoring->getLeadsWithScores($filter_data);

        // إعداد البيانات للتصدير
        $export_data = array();
        $export_data[] = array(
            'اسم العميل',
            'البريد الإلكتروني',
            'الهاتف',
            'الشركة',
            'المصدر',
            'النقاط الإجمالية',
            'النقاط الديموغرافية',
            'النقاط السلوكية',
            'نقاط التفاعل',
            'نقاط الشركة',
            'نقاط المصدر',
            'الأولوية',
            'احتمالية التحويل',
            'القيمة المتوقعة',
            'تاريخ الإنشاء',
            'آخر نشاط'
        );

        foreach ($leads as $lead) {
            $score_breakdown = $this->calculateScoreBreakdown($lead['lead_id']);

            $export_data[] = array(
                $lead['customer_name'],
                $lead['email'],
                $lead['phone'],
                $lead['company'],
                $lead['source'],
                $lead['total_score'],
                $score_breakdown['demographic_score'],
                $score_breakdown['behavioral_score'],
                $score_breakdown['engagement_score'],
                $score_breakdown['company_score'],
                $score_breakdown['source_score'],
                $this->determinePriority($lead['total_score']),
                $this->calculateConversionProbability($lead['total_score']) . '%',
                $lead['estimated_value'],
                $lead['date_created'],
                $lead['last_activity']
            );
        }

        // تصدير البيانات حسب التنسيق
        switch ($format) {
            case 'excel':
                $this->exportToExcel($export_data, 'lead_scoring_' . date('Y-m-d'));
                break;
            case 'csv':
                $this->exportToCSV($export_data, 'lead_scoring_' . date('Y-m-d'));
                break;
            case 'pdf':
                $this->exportToPDF($export_data, 'lead_scoring_' . date('Y-m-d'));
                break;
            default:
                $this->exportToExcel($export_data, 'lead_scoring_' . date('Y-m-d'));
        }
    }

    /**
     * الحصول على الإحصائيات في الوقت الفعلي
     */
    public function getStatistics() {
        $json = array();

        // فحص الصلاحيات
        if (!$this->user->hasPermission('access', 'crm/lead_scoring')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $json['success'] = true;
            $json['statistics'] = $this->calculateStatistics();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * الحصول على بيانات الرسوم البيانية
     */
    public function getChartsData() {
        $json = array();

        // فحص الصلاحيات
        if (!$this->user->hasPermission('access', 'crm/lead_scoring')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $json['success'] = true;
            $json['charts'] = $this->prepareChartsData();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تهيئة خوارزميات التقييم
     */
    private function initializeScoringAlgorithms() {
        $this->scoring_algorithms = array(
            'demographic' => array(
                'name' => 'التقييم الديموغرافي',
                'weight' => 0.20,
                'factors' => array(
                    'age_group' => 5,
                    'location' => 3,
                    'job_title' => 7,
                    'industry' => 5,
                    'education' => 3,
                    'income_level' => 7
                )
            ),
            'behavioral' => array(
                'name' => 'التقييم السلوكي',
                'weight' => 0.30,
                'factors' => array(
                    'website_visits' => 8,
                    'page_views' => 6,
                    'time_on_site' => 7,
                    'downloads' => 9,
                    'form_submissions' => 10,
                    'search_behavior' => 5
                )
            ),
            'engagement' => array(
                'name' => 'تقييم التفاعل',
                'weight' => 0.25,
                'factors' => array(
                    'email_opens' => 6,
                    'email_clicks' => 8,
                    'social_shares' => 4,
                    'event_attendance' => 9,
                    'webinar_participation' => 8,
                    'survey_responses' => 7
                )
            ),
            'company' => array(
                'name' => 'تقييم الشركة',
                'weight' => 0.15,
                'factors' => array(
                    'company_size' => 8,
                    'revenue' => 9,
                    'growth_rate' => 7,
                    'technology_stack' => 6,
                    'decision_maker' => 10
                )
            ),
            'source' => array(
                'name' => 'تقييم المصدر',
                'weight' => 0.10,
                'factors' => array(
                    'referral_quality' => 8,
                    'channel_effectiveness' => 7,
                    'campaign_performance' => 6,
                    'source_credibility' => 5
                )
            )
        );
    }

    /**
     * تحميل أوزان التقييم من الإعدادات
     */
    private function loadScoringWeights() {
        $settings = $this->model_setting_setting->getSetting('lead_scoring');

        if (!empty($settings['lead_scoring_weights'])) {
            $this->scoring_weights = json_decode($settings['lead_scoring_weights'], true);
        } else {
            // الأوزان الافتراضية
            $this->scoring_weights = array(
                'demographic_weight' => 20,
                'behavioral_weight' => 30,
                'engagement_weight' => 25,
                'company_weight' => 15,
                'source_weight' => 10
            );
        }
    }

    /**
     * معالجة الفلاتر
     */
    private function processFilters() {
        $filter_data = array();

        // فلتر الاسم
        if (isset($this->request->get['filter_name'])) {
            $filter_data['filter_name'] = $this->request->get['filter_name'];
        }

        // فلتر نطاق النقاط
        if (isset($this->request->get['filter_score_range'])) {
            $filter_data['filter_score_range'] = $this->request->get['filter_score_range'];
        }

        // فلتر الأولوية
        if (isset($this->request->get['filter_priority'])) {
            $filter_data['filter_priority'] = $this->request->get['filter_priority'];
        }

        // فلتر الحالة
        if (isset($this->request->get['filter_status'])) {
            $filter_data['filter_status'] = $this->request->get['filter_status'];
        }

        // فلتر المصدر
        if (isset($this->request->get['filter_source'])) {
            $filter_data['filter_source'] = $this->request->get['filter_source'];
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
            $filter_data['sort'] = 'total_score';
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

    /**
     * حساب نقاط العميل المحتمل
     */
    private function calculateLeadScore($lead_id, $method = 'auto') {
        $lead = $this->model_crm_lead_scoring->getLeadWithScore($lead_id);

        if (!$lead) {
            return false;
        }

        $score_breakdown = array();

        // حساب النقاط الديموغرافية
        $score_breakdown['demographic_score'] = $this->calculateDemographicScore($lead);

        // حساب النقاط السلوكية
        $score_breakdown['behavioral_score'] = $this->calculateBehavioralScore($lead);

        // حساب نقاط التفاعل
        $score_breakdown['engagement_score'] = $this->calculateEngagementScore($lead);

        // حساب نقاط الشركة
        $score_breakdown['company_score'] = $this->calculateCompanyScore($lead);

        // حساب نقاط المصدر
        $score_breakdown['source_score'] = $this->calculateSourceScore($lead);

        // حساب النقاط الإجمالية
        $total_score = 0;
        $total_score += $score_breakdown['demographic_score'] * ($this->scoring_weights['demographic_weight'] / 100);
        $total_score += $score_breakdown['behavioral_score'] * ($this->scoring_weights['behavioral_weight'] / 100);
        $total_score += $score_breakdown['engagement_score'] * ($this->scoring_weights['engagement_weight'] / 100);
        $total_score += $score_breakdown['company_score'] * ($this->scoring_weights['company_weight'] / 100);
        $total_score += $score_breakdown['source_score'] * ($this->scoring_weights['source_weight'] / 100);

        $score_breakdown['total_score'] = round($total_score, 2);
        $score_breakdown['method'] = $method;
        $score_breakdown['calculated_at'] = date('Y-m-d H:i:s');

        return $score_breakdown;
    }

    /**
     * حساب النقاط الديموغرافية
     */
    private function calculateDemographicScore($lead) {
        $score = 0;
        $max_score = 30;

        // العمر
        if (!empty($lead['age'])) {
            if ($lead['age'] >= 25 && $lead['age'] <= 45) {
                $score += 8; // الفئة العمرية المستهدفة
            } elseif ($lead['age'] >= 18 && $lead['age'] <= 65) {
                $score += 5;
            }
        }

        // الموقع الجغرافي
        if (!empty($lead['country'])) {
            $target_countries = array('EG', 'SA', 'AE', 'KW', 'QA', 'BH', 'OM');
            if (in_array($lead['country'], $target_countries)) {
                $score += 6;
            } else {
                $score += 3;
            }
        }

        // المسمى الوظيفي
        if (!empty($lead['job_title'])) {
            $decision_maker_titles = array('CEO', 'CTO', 'Manager', 'Director', 'VP', 'President');
            foreach ($decision_maker_titles as $title) {
                if (stripos($lead['job_title'], $title) !== false) {
                    $score += 10;
                    break;
                }
            }
            if ($score < 10) {
                $score += 5; // مسمى وظيفي عادي
            }
        }

        // مستوى التعليم
        if (!empty($lead['education'])) {
            $education_levels = array('PhD', 'Master', 'Bachelor', 'Diploma');
            foreach ($education_levels as $level) {
                if (stripos($lead['education'], $level) !== false) {
                    $score += 6;
                    break;
                }
            }
        }

        return min($score, $max_score);
    }

    /**
     * حساب النقاط السلوكية
     */
    private function calculateBehavioralScore($lead) {
        $score = 0;
        $max_score = 40;

        // زيارات الموقع
        $website_visits = $this->model_crm_lead_scoring->getLeadWebsiteVisits($lead['lead_id']);
        if ($website_visits > 10) {
            $score += 10;
        } elseif ($website_visits > 5) {
            $score += 7;
        } elseif ($website_visits > 0) {
            $score += 4;
        }

        // مشاهدات الصفحات
        $page_views = $this->model_crm_lead_scoring->getLeadPageViews($lead['lead_id']);
        if ($page_views > 50) {
            $score += 8;
        } elseif ($page_views > 20) {
            $score += 6;
        } elseif ($page_views > 5) {
            $score += 3;
        }

        // الوقت المقضي في الموقع
        $time_on_site = $this->model_crm_lead_scoring->getLeadTimeOnSite($lead['lead_id']);
        if ($time_on_site > 300) { // أكثر من 5 دقائق
            $score += 8;
        } elseif ($time_on_site > 120) { // أكثر من دقيقتين
            $score += 5;
        } elseif ($time_on_site > 30) {
            $score += 2;
        }

        // التحميلات
        $downloads = $this->model_crm_lead_scoring->getLeadDownloads($lead['lead_id']);
        $score += min($downloads * 2, 10);

        // إرسال النماذج
        $form_submissions = $this->model_crm_lead_scoring->getLeadFormSubmissions($lead['lead_id']);
        $score += min($form_submissions * 4, 12);

        return min($score, $max_score);
    }

    /**
     * حساب نقاط التفاعل
     */
    private function calculateEngagementScore($lead) {
        $score = 0;
        $max_score = 50;

        // فتح رسائل البريد الإلكتروني
        $email_opens = $this->model_crm_lead_scoring->getLeadEmailOpens($lead['lead_id']);
        if ($email_opens > 20) {
            $score += 10;
        } elseif ($email_opens > 10) {
            $score += 7;
        } elseif ($email_opens > 5) {
            $score += 4;
        }

        // النقر على روابط البريد الإلكتروني
        $email_clicks = $this->model_crm_lead_scoring->getLeadEmailClicks($lead['lead_id']);
        if ($email_clicks > 10) {
            $score += 12;
        } elseif ($email_clicks > 5) {
            $score += 8;
        } elseif ($email_clicks > 0) {
            $score += 4;
        }

        // المشاركات الاجتماعية
        $social_shares = $this->model_crm_lead_scoring->getLeadSocialShares($lead['lead_id']);
        $score += min($social_shares * 2, 8);

        // حضور الفعاليات
        $event_attendance = $this->model_crm_lead_scoring->getLeadEventAttendance($lead['lead_id']);
        $score += min($event_attendance * 5, 15);

        // المشاركة في الندوات الإلكترونية
        $webinar_participation = $this->model_crm_lead_scoring->getLeadWebinarParticipation($lead['lead_id']);
        $score += min($webinar_participation * 6, 12);

        // الردود على الاستطلاعات
        $survey_responses = $this->model_crm_lead_scoring->getLeadSurveyResponses($lead['lead_id']);
        $score += min($survey_responses * 3, 9);

        return min($score, $max_score);
    }

    /**
     * حساب نقاط الشركة
     */
    private function calculateCompanyScore($lead) {
        $score = 0;
        $max_score = 35;

        // حجم الشركة
        if (!empty($lead['company_size'])) {
            if ($lead['company_size'] >= 1000) {
                $score += 12; // شركة كبيرة
            } elseif ($lead['company_size'] >= 100) {
                $score += 8; // شركة متوسطة
            } elseif ($lead['company_size'] >= 10) {
                $score += 5; // شركة صغيرة
            }
        }

        // الإيرادات السنوية
        if (!empty($lead['annual_revenue'])) {
            if ($lead['annual_revenue'] >= 10000000) { // 10 مليون
                $score += 15;
            } elseif ($lead['annual_revenue'] >= 1000000) { // مليون
                $score += 10;
            } elseif ($lead['annual_revenue'] >= 100000) { // 100 ألف
                $score += 6;
            }
        }

        // معدل النمو
        if (!empty($lead['growth_rate'])) {
            if ($lead['growth_rate'] >= 20) {
                $score += 8; // نمو سريع
            } elseif ($lead['growth_rate'] >= 10) {
                $score += 5; // نمو متوسط
            } elseif ($lead['growth_rate'] > 0) {
                $score += 2; // نمو بطيء
            }
        }

        return min($score, $max_score);
    }

    /**
     * حساب نقاط المصدر
     */
    private function calculateSourceScore($lead) {
        $score = 0;
        $max_score = 25;

        // جودة المصدر
        $source_quality = array(
            'referral' => 15,
            'organic_search' => 12,
            'direct' => 10,
            'social_media' => 8,
            'email_campaign' => 7,
            'paid_search' => 6,
            'display_ads' => 4,
            'other' => 2
        );

        if (!empty($lead['source']) && isset($source_quality[$lead['source']])) {
            $score += $source_quality[$lead['source']];
        }

        // فعالية الحملة
        if (!empty($lead['campaign_id'])) {
            $campaign_performance = $this->model_crm_lead_scoring->getCampaignPerformance($lead['campaign_id']);
            if ($campaign_performance['conversion_rate'] > 10) {
                $score += 10;
            } elseif ($campaign_performance['conversion_rate'] > 5) {
                $score += 6;
            } elseif ($campaign_performance['conversion_rate'] > 0) {
                $score += 3;
            }
        }

        return min($score, $max_score);
    }
}
