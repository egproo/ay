<?php
/**
 * لوحة تحكم الموارد البشرية التفاعلية
 * 
 * يوفر لوحة تحكم شاملة للموارد البشرية مع:
 * - إحصائيات الموظفين المتقدمة
 * - رسوم بيانية تفاعلية
 * - تنبيهات ذكية
 * - تقارير سريعة
 * - مؤشرات الأداء الرئيسية
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerHrHrDashboard extends Controller {
    
    /**
     * الصفحة الرئيسية للوحة التحكم
     */
    public function index() {
        $this->load->language('hr/hr_dashboard');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('hr/employee');
        $this->load->model('hr/attendance');
        $this->load->model('hr/payroll_advanced');
        $this->load->model('hr/employee_advance');
        $this->load->model('hr/performance_evaluation');
        $this->load->model('hr/leave_request');
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('hr/hr_dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        // الإحصائيات الرئيسية
        $data['main_statistics'] = $this->getMainStatistics();
        
        // إحصائيات الحضور
        $data['attendance_statistics'] = $this->getAttendanceStatistics();
        
        // إحصائيات الرواتب
        $data['payroll_statistics'] = $this->getPayrollStatistics();
        
        // إحصائيات السلف
        $data['advance_statistics'] = $this->getAdvanceStatistics();
        
        // إحصائيات تقييم الأداء
        $data['performance_statistics'] = $this->getPerformanceStatistics();
        
        // إحصائيات الإجازات
        $data['leave_statistics'] = $this->getLeaveStatistics();
        
        // الرسوم البيانية
        $data['charts_data'] = $this->getChartsData();
        
        // التنبيهات الذكية
        $data['smart_alerts'] = $this->getSmartAlerts();
        
        // الأحداث القادمة
        $data['upcoming_events'] = $this->getUpcomingEvents();
        
        // مؤشرات الأداء الرئيسية
        $data['kpi_metrics'] = $this->getKPIMetrics();
        
        $data['user_token'] = $this->session->data['user_token'];
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('hr/hr_dashboard', $data));
    }
    
    /**
     * الحصول على الإحصائيات الرئيسية
     */
    private function getMainStatistics() {
        $statistics = [];
        
        // إجمالي الموظفين
        $total_employees = $this->model_hr_employee->getTotalEmployees();
        $statistics['total_employees'] = $total_employees;
        
        // الموظفين النشطين
        $active_employees = $this->model_hr_employee->getActiveEmployeesCount();
        $statistics['active_employees'] = $active_employees;
        
        // الموظفين الجدد هذا الشهر
        $new_employees = $this->model_hr_employee->getNewEmployeesThisMonth();
        $statistics['new_employees'] = count($new_employees);
        
        // الموظفين المغادرين هذا الشهر
        $departed_employees = $this->model_hr_employee->getDepartedEmployeesThisMonth();
        $statistics['departed_employees'] = count($departed_employees);
        
        // معدل دوران الموظفين
        $turnover_rate = ($total_employees > 0) ? 
            (count($departed_employees) / $total_employees) * 100 : 0;
        $statistics['turnover_rate'] = round($turnover_rate, 2);
        
        // متوسط سنوات الخبرة
        $avg_experience = $this->model_hr_employee->getAverageExperience();
        $statistics['avg_experience'] = round($avg_experience, 1);
        
        return $statistics;
    }
    
    /**
     * الحصول على إحصائيات الحضور
     */
    private function getAttendanceStatistics() {
        $statistics = [];
        
        // معدل الحضور اليوم
        $today_attendance = $this->model_hr_attendance->getTodayAttendanceRate();
        $statistics['today_attendance_rate'] = round($today_attendance, 1);
        
        // معدل الحضور هذا الشهر
        $monthly_attendance = $this->model_hr_attendance->getMonthlyAttendanceRate();
        $statistics['monthly_attendance_rate'] = round($monthly_attendance, 1);
        
        // المتأخرين اليوم
        $late_today = $this->model_hr_attendance->getLateEmployeesToday();
        $statistics['late_today'] = count($late_today);
        
        // الغائبين اليوم
        $absent_today = $this->model_hr_attendance->getAbsentEmployeesToday();
        $statistics['absent_today'] = count($absent_today);
        
        // ساعات الإضافي هذا الشهر
        $overtime_hours = $this->model_hr_attendance->getMonthlyOvertimeHours();
        $statistics['monthly_overtime'] = round($overtime_hours, 1);
        
        return $statistics;
    }
    
    /**
     * الحصول على إحصائيات الرواتب
     */
    private function getPayrollStatistics() {
        $statistics = [];
        
        // إجمالي الرواتب هذا الشهر
        $monthly_payroll = $this->model_hr_payroll_advanced->getMonthlyPayrollTotal();
        $statistics['monthly_payroll'] = $this->currency->format($monthly_payroll, $this->config->get('config_currency'));
        
        // متوسط الراتب
        $avg_salary = $this->model_hr_payroll_advanced->getAverageSalary();
        $statistics['avg_salary'] = $this->currency->format($avg_salary, $this->config->get('config_currency'));
        
        // دورات الرواتب المعلقة
        $pending_cycles = $this->model_hr_payroll_advanced->getPendingCyclesCount();
        $statistics['pending_cycles'] = $pending_cycles;
        
        // إجمالي المكافآت هذا الشهر
        $monthly_bonuses = $this->model_hr_payroll_advanced->getMonthlyBonusesTotal();
        $statistics['monthly_bonuses'] = $this->currency->format($monthly_bonuses, $this->config->get('config_currency'));
        
        return $statistics;
    }
    
    /**
     * الحصول على إحصائيات السلف
     */
    private function getAdvanceStatistics() {
        $statistics = [];
        
        // إجمالي السلف النشطة
        $active_advances = $this->model_hr_employee_advance->getActiveAdvancesTotal();
        $statistics['active_advances'] = $this->currency->format($active_advances, $this->config->get('config_currency'));
        
        // طلبات السلف المعلقة
        $pending_requests = $this->model_hr_employee_advance->getPendingRequestsCount();
        $statistics['pending_requests'] = $pending_requests;
        
        // الأقساط المستحقة هذا الشهر
        $due_installments = $this->model_hr_employee_advance->getDueInstallmentsTotal();
        $statistics['due_installments'] = $this->currency->format($due_installments, $this->config->get('config_currency'));
        
        return $statistics;
    }
    
    /**
     * الحصول على إحصائيات تقييم الأداء
     */
    private function getPerformanceStatistics() {
        $statistics = [];
        
        // التقييمات المكتملة هذا العام
        $completed_evaluations = $this->model_hr_performance_evaluation->getCompletedEvaluationsCount();
        $statistics['completed_evaluations'] = $completed_evaluations;
        
        // التقييمات المعلقة
        $pending_evaluations = $this->model_hr_performance_evaluation->getPendingEvaluationsCount();
        $statistics['pending_evaluations'] = $pending_evaluations;
        
        // متوسط تقييم الأداء
        $avg_performance = $this->model_hr_performance_evaluation->getAveragePerformanceScore();
        $statistics['avg_performance'] = round($avg_performance, 1);
        
        // الموظفين المتميزين
        $excellent_performers = $this->model_hr_performance_evaluation->getExcellentPerformersCount();
        $statistics['excellent_performers'] = $excellent_performers;
        
        return $statistics;
    }
    
    /**
     * الحصول على إحصائيات الإجازات
     */
    private function getLeaveStatistics() {
        $statistics = [];
        
        // طلبات الإجازات المعلقة
        $pending_leaves = $this->model_hr_leave_request->getPendingLeavesCount();
        $statistics['pending_leaves'] = $pending_leaves;
        
        // الموظفين في إجازة اليوم
        $on_leave_today = $this->model_hr_leave_request->getEmployeesOnLeaveToday();
        $statistics['on_leave_today'] = count($on_leave_today);
        
        // متوسط أيام الإجازة المستخدمة
        $avg_leave_days = $this->model_hr_leave_request->getAverageUsedLeaveDays();
        $statistics['avg_leave_days'] = round($avg_leave_days, 1);
        
        return $statistics;
    }
    
    /**
     * الحصول على بيانات الرسوم البيانية
     */
    private function getChartsData() {
        $charts = [];
        
        // رسم بياني للحضور الشهري
        $attendance_data = $this->model_hr_attendance->getMonthlyAttendanceChart();
        $charts['attendance_chart'] = $attendance_data;
        
        // رسم بياني للرواتب
        $payroll_data = $this->model_hr_payroll_advanced->getPayrollTrendChart();
        $charts['payroll_chart'] = $payroll_data;
        
        // رسم بياني دائري لتوزيع الموظفين حسب الأقسام
        $department_data = $this->model_hr_employee->getDepartmentDistribution();
        $charts['department_chart'] = $department_data;
        
        // رسم بياني لتقييم الأداء
        $performance_data = $this->model_hr_performance_evaluation->getPerformanceDistribution();
        $charts['performance_chart'] = $performance_data;
        
        return $charts;
    }
    
    /**
     * الحصول على التنبيهات الذكية
     */
    private function getSmartAlerts() {
        $alerts = [];
        
        // تنبيهات الحضور
        $late_employees = $this->model_hr_attendance->getLateEmployeesToday();
        if (count($late_employees) > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fa-clock',
                'title' => 'موظفين متأخرين',
                'message' => count($late_employees) . ' موظف متأخر اليوم',
                'action_url' => $this->url->link('hr/attendance', 'user_token=' . $this->session->data['user_token'], true)
            ];
        }
        
        // تنبيهات السلف
        $pending_advances = $this->model_hr_employee_advance->getPendingRequestsCount();
        if ($pending_advances > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'fa-money-bill',
                'title' => 'طلبات سلف معلقة',
                'message' => $pending_advances . ' طلب سلفة في انتظار الموافقة',
                'action_url' => $this->url->link('hr/employee_advance', 'user_token=' . $this->session->data['user_token'], true)
            ];
        }
        
        // تنبيهات الإجازات
        $pending_leaves = $this->model_hr_leave_request->getPendingLeavesCount();
        if ($pending_leaves > 0) {
            $alerts[] = [
                'type' => 'primary',
                'icon' => 'fa-calendar',
                'title' => 'طلبات إجازة معلقة',
                'message' => $pending_leaves . ' طلب إجازة في انتظار الموافقة',
                'action_url' => $this->url->link('hr/leave_request', 'user_token=' . $this->session->data['user_token'], true)
            ];
        }
        
        // تنبيهات الرواتب
        $pending_payroll = $this->model_hr_payroll_advanced->getPendingCyclesCount();
        if ($pending_payroll > 0) {
            $alerts[] = [
                'type' => 'success',
                'icon' => 'fa-calculator',
                'title' => 'دورات رواتب معلقة',
                'message' => $pending_payroll . ' دورة رواتب تحتاج اعتماد',
                'action_url' => $this->url->link('hr/payroll_advanced', 'user_token=' . $this->session->data['user_token'], true)
            ];
        }
        
        return $alerts;
    }
    
    /**
     * الحصول على الأحداث القادمة
     */
    private function getUpcomingEvents() {
        $events = [];
        
        // أعياد الميلاد القادمة
        $birthdays = $this->model_hr_employee->getUpcomingBirthdays(7); // خلال 7 أيام
        foreach ($birthdays as $birthday) {
            $events[] = [
                'type' => 'birthday',
                'icon' => 'fa-birthday-cake',
                'title' => 'عيد ميلاد ' . $birthday['employee_name'],
                'date' => $birthday['birthday'],
                'description' => 'عيد ميلاد الموظف'
            ];
        }
        
        // انتهاء فترات التجربة
        $probation_endings = $this->model_hr_employee->getEndingProbationPeriods(30); // خلال 30 يوم
        foreach ($probation_endings as $probation) {
            $events[] = [
                'type' => 'probation',
                'icon' => 'fa-user-check',
                'title' => 'انتهاء فترة تجربة ' . $probation['employee_name'],
                'date' => $probation['probation_end_date'],
                'description' => 'انتهاء فترة التجربة'
            ];
        }
        
        // انتهاء العقود
        $contract_endings = $this->model_hr_employee->getEndingContracts(60); // خلال 60 يوم
        foreach ($contract_endings as $contract) {
            $events[] = [
                'type' => 'contract',
                'icon' => 'fa-file-contract',
                'title' => 'انتهاء عقد ' . $contract['employee_name'],
                'date' => $contract['contract_end_date'],
                'description' => 'انتهاء العقد'
            ];
        }
        
        // ترتيب الأحداث حسب التاريخ
        usort($events, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
        
        return array_slice($events, 0, 10); // أول 10 أحداث
    }
    
    /**
     * الحصول على مؤشرات الأداء الرئيسية
     */
    private function getKPIMetrics() {
        $kpis = [];
        
        // معدل الاحتفاظ بالموظفين
        $retention_rate = $this->model_hr_employee->getRetentionRate();
        $kpis['retention_rate'] = [
            'value' => round($retention_rate, 1),
            'target' => 90,
            'unit' => '%',
            'trend' => 'up' // up, down, stable
        ];
        
        // متوسط وقت التوظيف
        $avg_hiring_time = $this->model_hr_employee->getAverageHiringTime();
        $kpis['avg_hiring_time'] = [
            'value' => round($avg_hiring_time, 0),
            'target' => 30,
            'unit' => 'يوم',
            'trend' => 'down'
        ];
        
        // معدل الرضا الوظيفي
        $satisfaction_rate = $this->model_hr_performance_evaluation->getEmployeeSatisfactionRate();
        $kpis['satisfaction_rate'] = [
            'value' => round($satisfaction_rate, 1),
            'target' => 85,
            'unit' => '%',
            'trend' => 'up'
        ];
        
        // معدل الإنتاجية
        $productivity_rate = $this->model_hr_performance_evaluation->getProductivityRate();
        $kpis['productivity_rate'] = [
            'value' => round($productivity_rate, 1),
            'target' => 80,
            'unit' => '%',
            'trend' => 'stable'
        ];
        
        return $kpis;
    }
    
    /**
     * تحديث البيانات عبر AJAX
     */
    public function refresh() {
        $json = [];
        
        try {
            $json['main_statistics'] = $this->getMainStatistics();
            $json['attendance_statistics'] = $this->getAttendanceStatistics();
            $json['smart_alerts'] = $this->getSmartAlerts();
            $json['success'] = true;
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
