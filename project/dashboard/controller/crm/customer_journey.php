<?php
/**
 * رحلة العميل (Customer Journey Controller)
 *
 * الهدف: تتبع وتحليل رحلة العميل من أول تفاعل حتى التحويل والاحتفاظ
 * الميزات: خريطة رحلة تفاعلية، نقاط اللمس، تحليل السلوك، تحسين التجربة
 * التكامل: مع CRM والمبيعات والتسويق والتحليلات
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerCrmCustomerJourney extends Controller {

    private $error = [];

    /**
     * الصفحة الرئيسية لرحلة العميل
     */
    public function index() {
        $this->load->language('crm/customer_journey');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('crm/customer_journey', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $this->load->model('crm/customer_journey');

        // إعداد المرشحات
        $filter_data = $this->getFilterData();

        // الحصول على رحلات العملاء
        $results = $this->model_crm_customer_journey->getCustomerJourneys($filter_data);
        $total = $this->model_crm_customer_journey->getTotalCustomerJourneys($filter_data);

        $data['journeys'] = [];

        foreach ($results as $result) {
            $stage_class = $this->getStageClass($result['current_stage']);
            $health_class = $this->getHealthClass($result['journey_health']);

            $data['journeys'][] = [
                'journey_id' => $result['journey_id'],
                'customer_id' => $result['customer_id'],
                'customer_name' => $result['customer_name'],
                'email' => $result['email'],
                'phone' => $result['phone'],
                'first_touchpoint' => $result['first_touchpoint'],
                'first_touchpoint_text' => $this->getTouchpointText($result['first_touchpoint']),
                'current_stage' => $result['current_stage'],
                'current_stage_text' => $this->getStageText($result['current_stage']),
                'stage_class' => $stage_class,
                'total_touchpoints' => $result['total_touchpoints'],
                'journey_duration' => $this->calculateDuration($result['journey_start'], $result['last_activity']),
                'journey_health' => $result['journey_health'],
                'journey_health_text' => $this->getHealthText($result['journey_health']),
                'health_class' => $health_class,
                'conversion_probability' => number_format($result['conversion_probability'], 1) . '%',
                'total_value' => number_format($result['total_value'], 2),
                'last_activity' => $result['last_activity'] ? date($this->language->get('date_format_short'), strtotime($result['last_activity'])) : '-',
                'assigned_to' => $result['assigned_to_name'],
                'journey_start' => date($this->language->get('date_format_short'), strtotime($result['journey_start'])),
                'view' => $this->url->link('crm/customer_journey/view', 'user_token=' . $this->session->data['user_token'] . '&journey_id=' . $result['journey_id'], true),
                'map' => $this->url->link('crm/customer_journey/map', 'user_token=' . $this->session->data['user_token'] . '&journey_id=' . $result['journey_id'], true),
                'timeline' => $this->url->link('crm/customer_journey/timeline', 'user_token=' . $this->session->data['user_token'] . '&journey_id=' . $result['journey_id'], true),
                'optimize' => $this->url->link('crm/customer_journey/optimize', 'user_token=' . $this->session->data['user_token'] . '&journey_id=' . $result['journey_id'], true)
            ];
        }

        // إعداد الروابط والأزرار
        $data['create'] = $this->url->link('crm/customer_journey/create', 'user_token=' . $this->session->data['user_token'], true);
        $data['analytics'] = $this->url->link('crm/customer_journey/analytics', 'user_token=' . $this->session->data['user_token'], true);
        $data['export'] = $this->url->link('crm/customer_journey/export', 'user_token=' . $this->session->data['user_token'], true);
        $data['templates'] = $this->url->link('crm/customer_journey/templates', 'user_token=' . $this->session->data['user_token'], true);
        $data['touchpoints'] = $this->url->link('crm/customer_journey/touchpoints', 'user_token=' . $this->session->data['user_token'], true);

        // إعداد التصفح
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $filter_data['limit'];
        $pagination->url = $this->url->link('crm/customer_journey', 'user_token=' . $this->session->data['user_token'] . $this->getFilterUrl() . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($filter_data['page'] - 1) * $filter_data['limit']) + 1 : 0, ((($filter_data['page'] - 1) * $filter_data['limit']) > ($total - $filter_data['limit'])) ? $total : ((($filter_data['page'] - 1) * $filter_data['limit']) + $filter_data['limit']), $total, ceil($total / $filter_data['limit']));

        // إعداد المرشحات للعرض
        $data['filter_customer'] = $filter_data['filter_customer'];
        $data['filter_stage'] = $filter_data['filter_stage'];
        $data['filter_health'] = $filter_data['filter_health'];
        $data['filter_touchpoint'] = $filter_data['filter_touchpoint'];
        $data['filter_assigned_to'] = $filter_data['filter_assigned_to'];

        // إحصائيات سريعة
        $data['statistics'] = $this->getQuickStatistics();

        // الرسوم البيانية
        $data['charts'] = $this->getChartsData();

        // قوائم للفلاتر
        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();
        $data['stages'] = $this->getJourneyStages();
        $data['touchpoints'] = $this->getTouchpoints();
        $data['health_levels'] = $this->getHealthLevels();

        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/customer_journey_list', $data));
    }

    /**
     * عرض تفاصيل رحلة العميل
     */
    public function view() {
        $this->load->language('crm/customer_journey');

        if (isset($this->request->get['journey_id'])) {
            $journey_id = (int)$this->request->get['journey_id'];

            $this->load->model('crm/customer_journey');

            $journey_info = $this->model_crm_customer_journey->getCustomerJourney($journey_id);

            if ($journey_info) {
                $this->document->setTitle($this->language->get('heading_title_view') . ' - ' . $journey_info['customer_name']);

                // الحصول على نقاط اللمس
                $touchpoints = $this->model_crm_customer_journey->getJourneyTouchpoints($journey_id);

                // الحصول على المراحل
                $stages = $this->model_crm_customer_journey->getJourneyStages($journey_id);

                // الحصول على التحليلات
                $analytics = $this->model_crm_customer_journey->getJourneyAnalytics($journey_id);

                $data['journey'] = $journey_info;
                $data['touchpoints'] = $touchpoints;
                $data['stages'] = $stages;
                $data['analytics'] = $analytics;

                // حساب الإحصائيات
                $data['journey_statistics'] = $this->calculateJourneyStatistics($journey_info, $touchpoints, $stages);

                $data['user_token'] = $this->session->data['user_token'];
                $data['header'] = $this->load->controller('common/header');
                $data['column_left'] = $this->load->controller('common/column_left');
                $data['footer'] = $this->load->controller('common/footer');

                $this->response->setOutput($this->load->view('crm/customer_journey_view', $data));
            } else {
                $this->session->data['error'] = $this->language->get('error_not_found');
                $this->response->redirect($this->url->link('crm/customer_journey', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('crm/customer_journey', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * خريطة رحلة العميل التفاعلية
     */
    public function map() {
        $this->load->language('crm/customer_journey');

        if (isset($this->request->get['journey_id'])) {
            $journey_id = (int)$this->request->get['journey_id'];

            $this->load->model('crm/customer_journey');

            $journey_info = $this->model_crm_customer_journey->getCustomerJourney($journey_id);

            if ($journey_info) {
                $this->document->setTitle($this->language->get('heading_title_map') . ' - ' . $journey_info['customer_name']);

                // الحصول على خريطة الرحلة
                $journey_map = $this->model_crm_customer_journey->getJourneyMap($journey_id);

                // الحصول على نقاط اللمس مع التفاصيل
                $touchpoint_details = $this->model_crm_customer_journey->getTouchpointDetails($journey_id);

                // الحصول على المسارات البديلة
                $alternative_paths = $this->model_crm_customer_journey->getAlternativePaths($journey_id);

                $data['journey'] = $journey_info;
                $data['journey_map'] = $journey_map;
                $data['touchpoint_details'] = $touchpoint_details;
                $data['alternative_paths'] = $alternative_paths;

                $data['user_token'] = $this->session->data['user_token'];
                $data['header'] = $this->load->controller('common/header');
                $data['column_left'] = $this->load->controller('common/column_left');
                $data['footer'] = $this->load->controller('common/footer');

                $this->response->setOutput($this->load->view('crm/customer_journey_map', $data));
            } else {
                $this->session->data['error'] = $this->language->get('error_not_found');
                $this->response->redirect($this->url->link('crm/customer_journey', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('crm/customer_journey', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * الجدول الزمني لرحلة العميل
     */
    public function timeline() {
        $this->load->language('crm/customer_journey');

        if (isset($this->request->get['journey_id'])) {
            $journey_id = (int)$this->request->get['journey_id'];

            $this->load->model('crm/customer_journey');

            $journey_info = $this->model_crm_customer_journey->getCustomerJourney($journey_id);

            if ($journey_info) {
                $this->document->setTitle($this->language->get('heading_title_timeline') . ' - ' . $journey_info['customer_name']);

                // الحصول على الجدول الزمني
                $timeline = $this->model_crm_customer_journey->getJourneyTimeline($journey_id);

                // الحصول على الأحداث المهمة
                $milestones = $this->model_crm_customer_journey->getJourneyMilestones($journey_id);

                $data['journey'] = $journey_info;
                $data['timeline'] = $timeline;
                $data['milestones'] = $milestones;

                $data['user_token'] = $this->session->data['user_token'];
                $data['header'] = $this->load->controller('common/header');
                $data['column_left'] = $this->load->controller('common/column_left');
                $data['footer'] = $this->load->controller('common/footer');

                $this->response->setOutput($this->load->view('crm/customer_journey_timeline', $data));
            } else {
                $this->session->data['error'] = $this->language->get('error_not_found');
                $this->response->redirect($this->url->link('crm/customer_journey', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('crm/customer_journey', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * تحليلات رحلة العميل
     */
    public function analytics() {
        $this->load->language('crm/customer_journey');

        $this->document->setTitle($this->language->get('heading_title_analytics'));

        $this->load->model('crm/customer_journey');

        // تحليلات شاملة
        $data['analytics'] = [
            'stage_conversion_rates' => $this->model_crm_customer_journey->getStageConversionRates(),
            'touchpoint_effectiveness' => $this->model_crm_customer_journey->getTouchpointEffectiveness(),
            'journey_duration_analysis' => $this->model_crm_customer_journey->getJourneyDurationAnalysis(),
            'drop_off_points' => $this->model_crm_customer_journey->getDropOffPoints(),
            'customer_segments' => $this->model_crm_customer_journey->getCustomerSegments(),
            'channel_performance' => $this->model_crm_customer_journey->getChannelPerformance()
        ];

        // مؤشرات الأداء الرئيسية
        $data['kpis'] = [
            'avg_journey_duration' => $this->model_crm_customer_journey->getAverageJourneyDuration(),
            'conversion_rate' => $this->model_crm_customer_journey->getOverallConversionRate(),
            'customer_lifetime_value' => $this->model_crm_customer_journey->getCustomerLifetimeValue(),
            'journey_completion_rate' => $this->model_crm_customer_journey->getJourneyCompletionRate()
        ];

        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/customer_journey_analytics', $data));
    }

    /**
     * دوال مساعدة
     */
    private function getFilterData() {
        $filter_data = [
            'filter_customer' => $this->request->get['filter_customer'] ?? '',
            'filter_stage' => $this->request->get['filter_stage'] ?? '',
            'filter_health' => $this->request->get['filter_health'] ?? '',
            'filter_touchpoint' => $this->request->get['filter_touchpoint'] ?? '',
            'filter_assigned_to' => $this->request->get['filter_assigned_to'] ?? '',
            'filter_date_from' => $this->request->get['filter_date_from'] ?? '',
            'filter_date_to' => $this->request->get['filter_date_to'] ?? '',
            'sort' => $this->request->get['sort'] ?? 'last_activity',
            'order' => $this->request->get['order'] ?? 'DESC',
            'start' => ($this->request->get['page'] ?? 1 - 1) * ($this->request->get['limit'] ?? 20),
            'limit' => $this->request->get['limit'] ?? 20,
            'page' => $this->request->get['page'] ?? 1
        ];

        return $filter_data;
    }

    private function getStageClass($stage) {
        $classes = [
            'awareness' => 'info',
            'interest' => 'primary',
            'consideration' => 'warning',
            'purchase' => 'success',
            'retention' => 'success',
            'advocacy' => 'success'
        ];

        return $classes[$stage] ?? 'default';
    }

    private function getHealthClass($health) {
        switch ($health) {
            case 'excellent': return 'success';
            case 'good': return 'info';
            case 'fair': return 'warning';
            case 'poor': return 'danger';
            default: return 'default';
        }
    }

    private function getTouchpointText($touchpoint) {
        $touchpoints = [
            'website' => $this->language->get('text_touchpoint_website'),
            'email' => $this->language->get('text_touchpoint_email'),
            'phone' => $this->language->get('text_touchpoint_phone'),
            'social_media' => $this->language->get('text_touchpoint_social'),
            'advertisement' => $this->language->get('text_touchpoint_ad'),
            'store' => $this->language->get('text_touchpoint_store'),
            'event' => $this->language->get('text_touchpoint_event'),
            'referral' => $this->language->get('text_touchpoint_referral')
        ];

        return $touchpoints[$touchpoint] ?? $touchpoint;
    }

    private function getStageText($stage) {
        $stages = [
            'awareness' => $this->language->get('text_stage_awareness'),
            'interest' => $this->language->get('text_stage_interest'),
            'consideration' => $this->language->get('text_stage_consideration'),
            'purchase' => $this->language->get('text_stage_purchase'),
            'retention' => $this->language->get('text_stage_retention'),
            'advocacy' => $this->language->get('text_stage_advocacy')
        ];

        return $stages[$stage] ?? $stage;
    }

    private function getHealthText($health) {
        $health_levels = [
            'excellent' => $this->language->get('text_health_excellent'),
            'good' => $this->language->get('text_health_good'),
            'fair' => $this->language->get('text_health_fair'),
            'poor' => $this->language->get('text_health_poor')
        ];

        return $health_levels[$health] ?? $health;
    }

    private function calculateDuration($start_date, $end_date) {
        if (!$end_date) {
            $end_date = date('Y-m-d H:i:s');
        }

        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);

        if ($interval->days > 0) {
            return $interval->days . ' ' . $this->language->get('text_days');
        } elseif ($interval->h > 0) {
            return $interval->h . ' ' . $this->language->get('text_hours');
        } else {
            return $interval->i . ' ' . $this->language->get('text_minutes');
        }
    }

    private function getQuickStatistics() {
        $this->load->model('crm/customer_journey');

        return [
            'total_journeys' => $this->model_crm_customer_journey->getTotalJourneys(),
            'active_journeys' => $this->model_crm_customer_journey->getActiveJourneys(),
            'avg_duration' => $this->model_crm_customer_journey->getAverageDuration(),
            'conversion_rate' => $this->model_crm_customer_journey->getConversionRate(),
            'top_touchpoint' => $this->model_crm_customer_journey->getTopTouchpoint(),
            'health_distribution' => $this->model_crm_customer_journey->getHealthDistribution()
        ];
    }

    private function getChartsData() {
        $this->load->model('crm/customer_journey');

        return [
            'stage_funnel' => $this->model_crm_customer_journey->getStageFunnelChart(),
            'touchpoint_distribution' => $this->model_crm_customer_journey->getTouchpointChart(),
            'duration_histogram' => $this->model_crm_customer_journey->getDurationChart(),
            'health_pie' => $this->model_crm_customer_journey->getHealthChart()
        ];
    }

    private function getJourneyStages() {
        return [
            'awareness' => $this->language->get('text_stage_awareness'),
            'interest' => $this->language->get('text_stage_interest'),
            'consideration' => $this->language->get('text_stage_consideration'),
            'purchase' => $this->language->get('text_stage_purchase'),
            'retention' => $this->language->get('text_stage_retention'),
            'advocacy' => $this->language->get('text_stage_advocacy')
        ];
    }

    private function getTouchpoints() {
        return [
            'website' => $this->language->get('text_touchpoint_website'),
            'email' => $this->language->get('text_touchpoint_email'),
            'phone' => $this->language->get('text_touchpoint_phone'),
            'social_media' => $this->language->get('text_touchpoint_social'),
            'advertisement' => $this->language->get('text_touchpoint_ad'),
            'store' => $this->language->get('text_touchpoint_store'),
            'event' => $this->language->get('text_touchpoint_event'),
            'referral' => $this->language->get('text_touchpoint_referral')
        ];
    }

    private function getHealthLevels() {
        return [
            'excellent' => $this->language->get('text_health_excellent'),
            'good' => $this->language->get('text_health_good'),
            'fair' => $this->language->get('text_health_fair'),
            'poor' => $this->language->get('text_health_poor')
        ];
    }

    private function calculateJourneyStatistics($journey, $touchpoints, $stages) {
        $total_touchpoints = count($touchpoints);
        $completed_stages = count(array_filter($stages, function($stage) {
            return $stage['status'] == 'completed';
        }));

        $engagement_score = 0;
        foreach ($touchpoints as $touchpoint) {
            $engagement_score += $touchpoint['engagement_value'] ?? 1;
        }

        return [
            'total_touchpoints' => $total_touchpoints,
            'completed_stages' => $completed_stages,
            'stage_completion_rate' => count($stages) > 0 ? round(($completed_stages / count($stages)) * 100, 1) : 0,
            'engagement_score' => $engagement_score,
            'avg_time_between_touchpoints' => $this->calculateAvgTimeBetweenTouchpoints($touchpoints)
        ];
    }

    private function calculateAvgTimeBetweenTouchpoints($touchpoints) {
        if (count($touchpoints) < 2) return 0;

        $total_time = 0;
        $intervals = 0;

        for ($i = 1; $i < count($touchpoints); $i++) {
            $prev_time = strtotime($touchpoints[$i-1]['created_date']);
            $curr_time = strtotime($touchpoints[$i]['created_date']);
            $total_time += ($curr_time - $prev_time);
            $intervals++;
        }

        $avg_seconds = $intervals > 0 ? $total_time / $intervals : 0;
        $avg_hours = round($avg_seconds / 3600, 1);

        return $avg_hours;
    }

    /**
     * نقل العميل إلى مرحلة جديدة
     */
    public function moveCustomer() {
        $this->load->language('crm/customer_journey');

        $json = array();

        // فحص الصلاحيات
        if (!$this->user->hasPermission('modify', 'crm/customer_journey')) {
            $json['error'] = $this->language->get('error_permission');
        }

        $customer_id = isset($this->request->post['customer_id']) ? (int)$this->request->post['customer_id'] : 0;
        $new_stage = isset($this->request->post['new_stage']) ? $this->request->post['new_stage'] : '';

        if (!$json && !$customer_id) {
            $json['error'] = $this->language->get('error_customer_required');
        }

        if (!$json && empty($new_stage)) {
            $json['error'] = $this->language->get('error_stage_required');
        }

        if (!$json) {
            try {
                // البحث عن رحلة العميل النشطة
                $journey = $this->model_crm_customer_journey->getActiveJourneyByCustomer($customer_id);

                if (!$journey) {
                    // إنشاء رحلة جديدة للعميل
                    $journey_data = array(
                        'customer_id' => $customer_id,
                        'current_stage' => $new_stage,
                        'start_date' => date('Y-m-d H:i:s'),
                        'status' => 'active',
                        'created_by' => $this->user->getId(),
                        'date_created' => date('Y-m-d H:i:s')
                    );

                    $journey_id = $this->model_crm_customer_journey->addJourney($journey_data);
                } else {
                    // تحديث المرحلة الحالية
                    $journey_id = $journey['journey_id'];
                    $this->model_crm_customer_journey->updateJourneyStage($journey_id, $new_stage, 'تم النقل بواسطة السحب والإفلات');
                }

                // تسجيل النشاط
                $this->model_crm_activity->addActivity(array(
                    'journey_id' => $journey_id,
                    'customer_id' => $customer_id,
                    'activity_type' => 'customer_moved',
                    'description' => 'تم نقل العميل إلى مرحلة ' . $new_stage,
                    'user_id' => $this->user->getId(),
                    'date_created' => date('Y-m-d H:i:s')
                ));

                $json['success'] = $this->language->get('text_success_customer_moved');

            } catch (Exception $e) {
                $json['error'] = 'حدث خطأ أثناء نقل العميل: ' . $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * الحصول على البيانات في الوقت الفعلي
     */
    public function getRealTimeData() {
        $json = array();

        // فحص الصلاحيات
        if (!$this->user->hasPermission('access', 'crm/customer_journey')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $json['success'] = true;
            $json['data'] = array(
                'stalled_customers' => $this->getStalledCustomers(),
                'conversion_rate' => $this->getCurrentConversionRate(),
                'ineffective_touchpoints' => $this->getIneffectiveTouchpoints(),
                'stage_distribution' => $this->getStageDistribution(),
                'health_scores' => $this->getHealthScoreDistribution()
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تهيئة مراحل الرحلة
     */
    private function initializeJourneyStages() {
        $this->journey_stages = array(
            'awareness' => array(
                'name' => 'الوعي',
                'description' => 'العميل يدرك وجود المنتج أو الخدمة',
                'order' => 1,
                'conversion_weight' => 0.1
            ),
            'interest' => array(
                'name' => 'الاهتمام',
                'description' => 'العميل يظهر اهتماماً بالمنتج أو الخدمة',
                'order' => 2,
                'conversion_weight' => 0.2
            ),
            'consideration' => array(
                'name' => 'الاعتبار',
                'description' => 'العميل يفكر جدياً في الشراء',
                'order' => 3,
                'conversion_weight' => 0.4
            ),
            'purchase' => array(
                'name' => 'الشراء',
                'description' => 'العميل يقوم بعملية الشراء',
                'order' => 4,
                'conversion_weight' => 1.0
            ),
            'retention' => array(
                'name' => 'الاحتفاظ',
                'description' => 'الحفاظ على العميل وتكرار الشراء',
                'order' => 5,
                'conversion_weight' => 1.2
            ),
            'advocacy' => array(
                'name' => 'الدعوة',
                'description' => 'العميل يروج للمنتج أو الخدمة',
                'order' => 6,
                'conversion_weight' => 1.5
            )
        );
    }

    /**
     * تحميل أنواع نقاط اللمس
     */
    private function loadTouchpointTypes() {
        $this->touchpoint_types = array(
            'website' => array(
                'name' => 'الموقع الإلكتروني',
                'engagement_value' => 3,
                'activities' => array('page_view', 'form_submission', 'download')
            ),
            'email' => array(
                'name' => 'البريد الإلكتروني',
                'engagement_value' => 4,
                'activities' => array('email_open', 'email_click', 'email_reply')
            ),
            'social' => array(
                'name' => 'وسائل التواصل الاجتماعي',
                'engagement_value' => 2,
                'activities' => array('like', 'share', 'comment', 'follow')
            ),
            'phone' => array(
                'name' => 'الهاتف',
                'engagement_value' => 8,
                'activities' => array('inbound_call', 'outbound_call', 'voicemail')
            ),
            'store' => array(
                'name' => 'المتجر',
                'engagement_value' => 10,
                'activities' => array('visit', 'purchase', 'consultation')
            ),
            'mobile' => array(
                'name' => 'التطبيق المحمول',
                'engagement_value' => 5,
                'activities' => array('app_open', 'feature_use', 'push_notification')
            ),
            'referral' => array(
                'name' => 'الإحالة',
                'engagement_value' => 9,
                'activities' => array('referral_made', 'referral_converted')
            ),
            'advertising' => array(
                'name' => 'الإعلانات',
                'engagement_value' => 1,
                'activities' => array('ad_view', 'ad_click', 'ad_conversion')
            )
        );
    }

    /**
     * معالجة الفلاتر
     */
    private function processFilters() {
        $filter_data = array();

        // فلتر اسم العميل
        if (isset($this->request->get['filter_customer'])) {
            $filter_data['filter_customer'] = $this->request->get['filter_customer'];
        }

        // فلتر الشركة
        if (isset($this->request->get['filter_company'])) {
            $filter_data['filter_company'] = $this->request->get['filter_company'];
        }

        // فلتر المرحلة الحالية
        if (isset($this->request->get['filter_stage'])) {
            $filter_data['filter_stage'] = $this->request->get['filter_stage'];
        }

        // فلتر صحة الرحلة
        if (isset($this->request->get['filter_health'])) {
            $filter_data['filter_health'] = $this->request->get['filter_health'];
        }

        // فلتر نقطة اللمس الأولى
        if (isset($this->request->get['filter_first_touchpoint'])) {
            $filter_data['filter_first_touchpoint'] = $this->request->get['filter_first_touchpoint'];
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

    /**
     * حساب الإحصائيات
     */
    private function calculateStatistics() {
        $statistics = array();

        // إجمالي الرحلات النشطة
        $statistics['total_journeys'] = $this->model_crm_customer_journey->getTotalActiveJourneys();

        // متوسط مدة الرحلة
        $statistics['average_journey_time'] = $this->model_crm_customer_journey->getAverageJourneyTime();

        // معدل التحويل الإجمالي
        $statistics['overall_conversion_rate'] = $this->model_crm_customer_journey->getOverallConversionRate();

        // توزيع صحة الرحلات
        $statistics['health_distribution'] = $this->model_crm_customer_journey->getHealthDistribution();

        return $statistics;
    }

    /**
     * إعداد بيانات الرسوم البيانية
     */
    private function prepareChartsData() {
        $charts = array();

        // رسم قمع المراحل
        $charts['stage_funnel'] = $this->model_crm_customer_journey->getStageFunnelData();

        // رسم خريطة حرارية لنقاط اللمس
        $charts['touchpoint_heatmap'] = $this->model_crm_customer_journey->getTouchpointHeatmapData();

        // رسم تدفق التحويل
        $charts['conversion_flow'] = $this->model_crm_customer_journey->getConversionFlowData();

        // رسم صحة الرحلة
        $charts['health_score'] = $this->model_crm_customer_journey->getHealthScoreData();

        return $charts;
    }
}
