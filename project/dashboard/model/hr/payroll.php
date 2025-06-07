<?php
class ModelHrPayroll extends Model {

    // إعدادات الضرائب والاستقطاعات الافتراضية
    private $tax_rate = 0.14; // 14% ضريبة الدخل
    private $social_insurance_rate = 0.11; // 11% تأمينات اجتماعية
    private $medical_insurance_rate = 0.03; // 3% تأمين طبي

    public function getTotalPayrollPeriods($filter_data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM `cod_payroll_period` WHERE 1";

        if (!empty($filter_data['filter_name'])) {
            $sql .= " AND period_name LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%'";
        }

        if (!empty($filter_data['filter_date_start'])) {
            $sql .= " AND start_date >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }

        if (!empty($filter_data['filter_date_end'])) {
            $sql .= " AND end_date <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    public function getPayrollPeriods($filter_data = array()) {
        $sql = "SELECT * FROM `cod_payroll_period` WHERE 1";

        if (!empty($filter_data['filter_name'])) {
            $sql .= " AND period_name LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%'";
        }

        if (!empty($filter_data['filter_date_start'])) {
            $sql .= " AND start_date >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }

        if (!empty($filter_data['filter_date_end'])) {
            $sql .= " AND end_date <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        $sort_data = array('period_name','start_date','end_date','status');
        $sort = (isset($filter_data['sort']) && in_array($filter_data['sort'], $sort_data)) ? $filter_data['sort'] : 'start_date';
        $order = (isset($filter_data['order']) && ($filter_data['order'] == 'desc')) ? 'DESC' : 'ASC';

        $sql .= " ORDER BY " . $sort . " " . $order;

        $start = isset($filter_data['start']) ? (int)$filter_data['start'] : 0;
        $limit = isset($filter_data['limit']) ? (int)$filter_data['limit'] : 10;

        if ($start < 0) { $start = 0; }
        if ($limit < 1) { $limit = 10; }

        $sql .= " LIMIT " . $start . "," . $limit;

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getPayrollPeriodById($id) {
        $query = $this->db->query("SELECT * FROM `cod_payroll_period` WHERE payroll_period_id = '" . (int)$id . "'");
        return $query->row;
    }

    public function addPayrollPeriod($data) {
        $this->db->query("INSERT INTO `cod_payroll_period` SET
            period_name = '" . $this->db->escape($data['period_name']) . "',
            start_date = '" . $this->db->escape($data['start_date']) . "',
            end_date = '" . $this->db->escape($data['end_date']) . "',
            status = '" . $this->db->escape($data['status']) . "'");

        return $this->db->getLastId();
    }

    public function editPayrollPeriod($id, $data) {
        $this->db->query("UPDATE `cod_payroll_period` SET
            period_name = '" . $this->db->escape($data['period_name']) . "',
            start_date = '" . $this->db->escape($data['start_date']) . "',
            end_date = '" . $this->db->escape($data['end_date']) . "',
            status = '" . $this->db->escape($data['status']) . "'
            WHERE payroll_period_id = '" . (int)$id . "'");
    }

    public function deletePayrollPeriod($id) {
        $this->db->query("DELETE FROM `cod_payroll_period` WHERE payroll_period_id = '" . (int)$id . "'");
        // يمكن أيضاً حذف entries المرتبطة بهذه الفترة
        $this->db->query("DELETE FROM `cod_payroll_entry` WHERE payroll_period_id = '" . (int)$id . "'");
    }

    public function getTotalPayrollEntries($payroll_period_id) {
        $query = $this->db->query("SELECT COUNT(*) as total FROM `cod_payroll_entry` WHERE payroll_period_id = '" . (int)$payroll_period_id . "'");
        return $query->row['total'];
    }

    public function getPayrollEntries($payroll_period_id, $start=0, $limit=10, $sort='employee_name', $order='ASC') {
        // join مع user لجلب اسم الموظف
        // هنا نفترض أن payment_invoice_id عمود في نفس الجدول أو سيستم مختلف
        // لو لم يكن متوفر, تأكد من أسماء الأعمدة الحقيقية
        $sql = "SELECT pe.*, CONCAT(u.firstname,' ',u.lastname) as employee_name
                FROM `cod_payroll_entry` pe
                LEFT JOIN `cod_user` u ON (pe.user_id = u.user_id)
                WHERE pe.payroll_period_id = '" . (int)$payroll_period_id . "'";

        $sort_data = array('employee_name','base_salary','allowances','deductions','net_salary','payment_status');
        if (!in_array($sort, $sort_data)) {
            $sort = 'employee_name';
        }
        $order = ($order == 'DESC') ? 'DESC' : 'ASC';

        $sql .= " ORDER BY " . $sort . " " . $order;

        if ($start < 0) $start = 0;
        if ($limit < 1) $limit = 10;

        $sql .= " LIMIT " . (int)$start . "," . (int)$limit;

        $query = $this->db->query($sql);
        // نفترض وجود عمود payment_invoice_id من الاستلام السابق,
        // لو غير موجود، استبدله بـ payroll_entry_id
        // أو أي حقل تستخدمه في markPaid
        return $query->rows;
    }

    public function markEntryPaid($payment_invoice_id) {
        // تحديث حالة الدفع للسجل مع تاريخ الدفع
        $this->db->query("UPDATE `cod_payroll_entry` SET
            payment_status = 'paid',
            payment_date = NOW()
            WHERE payroll_entry_id = '" . (int)$payment_invoice_id . "'");
    }

    // حساب الراتب الإجمالي للموظف
    public function calculateEmployeeSalary($user_id, $payroll_period_id) {
        // جلب بيانات الموظف
        $employee_query = $this->db->query("SELECT ep.*, u.firstname, u.lastname
            FROM `cod_employee_profile` ep
            LEFT JOIN `cod_user` u ON (ep.user_id = u.user_id)
            WHERE ep.user_id = '" . (int)$user_id . "'");

        if (!$employee_query->num_rows) {
            return false;
        }

        $employee = $employee_query->row;
        $base_salary = $employee['salary'];

        // جلب فترة الراتب
        $period_query = $this->db->query("SELECT * FROM `cod_payroll_period`
            WHERE payroll_period_id = '" . (int)$payroll_period_id . "'");

        if (!$period_query->num_rows) {
            return false;
        }

        $period = $period_query->row;

        // حساب أيام العمل في الفترة
        $work_days = $this->calculateWorkDays($period['start_date'], $period['end_date']);

        // حساب أيام الحضور الفعلي
        $attendance_days = $this->getEmployeeAttendanceDays($user_id, $period['start_date'], $period['end_date']);

        // حساب ساعات العمل الإضافي
        $overtime_hours = $this->getEmployeeOvertimeHours($user_id, $period['start_date'], $period['end_date']);

        // حساب البدلات
        $allowances = $this->calculateAllowances($user_id, $base_salary);

        // حساب الاستقطاعات
        $deductions = $this->calculateDeductions($user_id, $base_salary, $allowances);

        // حساب راتب العمل الإضافي
        $overtime_pay = $this->calculateOvertimePay($base_salary, $overtime_hours);

        // الراتب الإجمالي
        $gross_salary = $base_salary + $allowances + $overtime_pay;

        // صافي الراتب
        $net_salary = $gross_salary - $deductions;

        return array(
            'user_id' => $user_id,
            'employee_name' => $employee['firstname'] . ' ' . $employee['lastname'],
            'base_salary' => $base_salary,
            'allowances' => $allowances,
            'overtime_pay' => $overtime_pay,
            'gross_salary' => $gross_salary,
            'deductions' => $deductions,
            'net_salary' => $net_salary,
            'work_days' => $work_days,
            'attendance_days' => $attendance_days,
            'overtime_hours' => $overtime_hours
        );
    }

    // حساب أيام العمل في فترة معينة (باستثناء الجمعة والسبت)
    private function calculateWorkDays($start_date, $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $work_days = 0;

        while ($start <= $end) {
            $day_of_week = $start->format('N'); // 1 = Monday, 7 = Sunday
            if ($day_of_week < 6) { // Monday to Friday
                $work_days++;
            }
            $start->add(new DateInterval('P1D'));
        }

        return $work_days;
    }

    // جلب أيام الحضور الفعلي للموظف
    private function getEmployeeAttendanceDays($user_id, $start_date, $end_date) {
        $query = $this->db->query("SELECT COUNT(*) as attendance_days
            FROM `cod_attendance`
            WHERE user_id = '" . (int)$user_id . "'
            AND date BETWEEN '" . $this->db->escape($start_date) . "'
            AND '" . $this->db->escape($end_date) . "'
            AND status IN ('present', 'late')");

        return $query->row['attendance_days'];
    }

    // جلب ساعات العمل الإضافي
    private function getEmployeeOvertimeHours($user_id, $start_date, $end_date) {
        $query = $this->db->query("SELECT SUM(
            CASE
                WHEN checkout_time IS NOT NULL AND checkin_time IS NOT NULL
                THEN GREATEST(0, TIMESTAMPDIFF(HOUR, checkin_time, checkout_time) - 8)
                ELSE 0
            END
        ) as overtime_hours
        FROM `cod_attendance`
        WHERE user_id = '" . (int)$user_id . "'
        AND date BETWEEN '" . $this->db->escape($start_date) . "'
        AND '" . $this->db->escape($end_date) . "'
        AND status = 'present'");

        return $query->row['overtime_hours'] ?: 0;
    }

    // حساب البدلات
    private function calculateAllowances($user_id, $base_salary) {
        $allowances = 0;

        // بدل مواصلات (10% من الراتب الأساسي)
        $transport_allowance = $base_salary * 0.10;

        // بدل وجبات (5% من الراتب الأساسي)
        $meal_allowance = $base_salary * 0.05;

        // بدل هاتف (مبلغ ثابت)
        $phone_allowance = 200;

        $allowances = $transport_allowance + $meal_allowance + $phone_allowance;

        return $allowances;
    }

    // حساب الاستقطاعات
    private function calculateDeductions($user_id, $base_salary, $allowances) {
        $gross_income = $base_salary + $allowances;

        // ضريبة الدخل
        $income_tax = $this->calculateIncomeTax($gross_income);

        // التأمينات الاجتماعية
        $social_insurance = $gross_income * $this->social_insurance_rate;

        // التأمين الطبي
        $medical_insurance = $gross_income * $this->medical_insurance_rate;

        // استقطاعات أخرى (قروض، سلف، إلخ)
        $other_deductions = $this->getOtherDeductions($user_id);

        return $income_tax + $social_insurance + $medical_insurance + $other_deductions;
    }

    // حساب ضريبة الدخل المتدرجة
    private function calculateIncomeTax($gross_income) {
        $annual_income = $gross_income * 12;
        $tax = 0;

        // الشرائح الضريبية المصرية
        if ($annual_income <= 15000) {
            $tax = 0; // معفى
        } elseif ($annual_income <= 30000) {
            $tax = ($annual_income - 15000) * 0.025; // 2.5%
        } elseif ($annual_income <= 45000) {
            $tax = 375 + ($annual_income - 30000) * 0.10; // 10%
        } elseif ($annual_income <= 60000) {
            $tax = 1875 + ($annual_income - 45000) * 0.15; // 15%
        } else {
            $tax = 4125 + ($annual_income - 60000) * 0.20; // 20%
        }

        return $tax / 12; // تحويل للشهري
    }

    // جلب الاستقطاعات الأخرى
    private function getOtherDeductions($user_id) {
        // يمكن إضافة جدول للاستقطاعات الأخرى
        return 0;
    }

    // حساب أجر العمل الإضافي
    private function calculateOvertimePay($base_salary, $overtime_hours) {
        $hourly_rate = $base_salary / (30 * 8); // افتراض 30 يوم × 8 ساعات
        return $overtime_hours * $hourly_rate * 1.5; // 150% للعمل الإضافي
    }

    // إنشاء سجلات الرواتب لجميع الموظفين في فترة معينة
    public function generatePayrollForPeriod($payroll_period_id) {
        // جلب جميع الموظفين النشطين
        $employees_query = $this->db->query("SELECT ep.user_id
            FROM `cod_employee_profile` ep
            WHERE ep.status = 'active'");

        $generated_count = 0;

        foreach ($employees_query->rows as $employee) {
            $salary_data = $this->calculateEmployeeSalary($employee['user_id'], $payroll_period_id);

            if ($salary_data) {
                // التحقق من عدم وجود سجل مسبق
                $existing_query = $this->db->query("SELECT payroll_entry_id
                    FROM `cod_payroll_entry`
                    WHERE payroll_period_id = '" . (int)$payroll_period_id . "'
                    AND user_id = '" . (int)$employee['user_id'] . "'");

                if (!$existing_query->num_rows) {
                    // إنشاء سجل راتب جديد
                    $this->db->query("INSERT INTO `cod_payroll_entry` SET
                        payroll_period_id = '" . (int)$payroll_period_id . "',
                        user_id = '" . (int)$employee['user_id'] . "',
                        base_salary = '" . (float)$salary_data['base_salary'] . "',
                        allowances = '" . (float)$salary_data['allowances'] . "',
                        deductions = '" . (float)$salary_data['deductions'] . "',
                        net_salary = '" . (float)$salary_data['net_salary'] . "',
                        payment_status = 'pending',
                        notes = 'تم إنشاؤه تلقائياً - أيام العمل: " . $salary_data['work_days'] . ", أيام الحضور: " . $salary_data['attendance_days'] . ", ساعات إضافية: " . $salary_data['overtime_hours'] . "'");

                    $generated_count++;
                }
            }
        }

        return $generated_count;
    }

    // جلب إحصائيات الرواتب لفترة معينة
    public function getPayrollStatistics($payroll_period_id) {
        $query = $this->db->query("SELECT
            COUNT(*) as total_employees,
            SUM(base_salary) as total_base_salary,
            SUM(allowances) as total_allowances,
            SUM(deductions) as total_deductions,
            SUM(net_salary) as total_net_salary,
            AVG(net_salary) as average_net_salary,
            COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_count,
            COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_count
            FROM `cod_payroll_entry`
            WHERE payroll_period_id = '" . (int)$payroll_period_id . "'");

        return $query->row;
    }

    // جلب تقرير مفصل للرواتب
    public function getDetailedPayrollReport($payroll_period_id) {
        $query = $this->db->query("SELECT
            pe.*,
            CONCAT(u.firstname, ' ', u.lastname) as employee_name,
            ep.job_title,
            pp.period_name,
            pp.start_date,
            pp.end_date
            FROM `cod_payroll_entry` pe
            LEFT JOIN `cod_user` u ON (pe.user_id = u.user_id)
            LEFT JOIN `cod_employee_profile` ep ON (pe.user_id = ep.user_id)
            LEFT JOIN `cod_payroll_period` pp ON (pe.payroll_period_id = pp.payroll_period_id)
            WHERE pe.payroll_period_id = '" . (int)$payroll_period_id . "'
            ORDER BY u.firstname, u.lastname");

        return $query->rows;
    }

    // تصدير الرواتب إلى Excel/CSV
    public function exportPayrollData($payroll_period_id, $format = 'csv') {
        $data = $this->getDetailedPayrollReport($payroll_period_id);

        if ($format == 'csv') {
            return $this->generateCSV($data);
        } elseif ($format == 'excel') {
            return $this->generateExcel($data);
        }

        return false;
    }

    // إنشاء ملف CSV
    private function generateCSV($data) {
        $csv_content = "اسم الموظف,المسمى الوظيفي,الراتب الأساسي,البدلات,الاستقطاعات,صافي الراتب,حالة الدفع\n";

        foreach ($data as $row) {
            $csv_content .= '"' . $row['employee_name'] . '",';
            $csv_content .= '"' . $row['job_title'] . '",';
            $csv_content .= $row['base_salary'] . ',';
            $csv_content .= $row['allowances'] . ',';
            $csv_content .= $row['deductions'] . ',';
            $csv_content .= $row['net_salary'] . ',';
            $csv_content .= '"' . $row['payment_status'] . '"' . "\n";
        }

        return $csv_content;
    }

    // البحث المتقدم في الرواتب
    public function searchPayrollEntries($search_data) {
        $sql = "SELECT
            pe.*,
            CONCAT(u.firstname, ' ', u.lastname) as employee_name,
            ep.job_title,
            pp.period_name
            FROM `cod_payroll_entry` pe
            LEFT JOIN `cod_user` u ON (pe.user_id = u.user_id)
            LEFT JOIN `cod_employee_profile` ep ON (pe.user_id = ep.user_id)
            LEFT JOIN `cod_payroll_period` pp ON (pe.payroll_period_id = pp.payroll_period_id)
            WHERE 1";

        if (!empty($search_data['employee_name'])) {
            $sql .= " AND CONCAT(u.firstname, ' ', u.lastname) LIKE '%" . $this->db->escape($search_data['employee_name']) . "%'";
        }

        if (!empty($search_data['period_id'])) {
            $sql .= " AND pe.payroll_period_id = '" . (int)$search_data['period_id'] . "'";
        }

        if (!empty($search_data['payment_status'])) {
            $sql .= " AND pe.payment_status = '" . $this->db->escape($search_data['payment_status']) . "'";
        }

        if (!empty($search_data['min_salary'])) {
            $sql .= " AND pe.net_salary >= '" . (float)$search_data['min_salary'] . "'";
        }

        if (!empty($search_data['max_salary'])) {
            $sql .= " AND pe.net_salary <= '" . (float)$search_data['max_salary'] . "'";
        }

        $sql .= " ORDER BY u.firstname, u.lastname";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    // حساب الضرائب السنوية للموظف
    public function calculateAnnualTax($user_id, $year) {
        $query = $this->db->query("SELECT
            SUM(pe.base_salary + pe.allowances) as annual_gross,
            SUM(pe.deductions) as annual_deductions,
            SUM(pe.net_salary) as annual_net
            FROM `cod_payroll_entry` pe
            LEFT JOIN `cod_payroll_period` pp ON (pe.payroll_period_id = pp.payroll_period_id)
            WHERE pe.user_id = '" . (int)$user_id . "'
            AND YEAR(pp.start_date) = '" . (int)$year . "'");

        return $query->row;
    }

    // إنشاء تقرير الضرائب السنوي
    public function generateAnnualTaxReport($year) {
        $query = $this->db->query("SELECT
            pe.user_id,
            CONCAT(u.firstname, ' ', u.lastname) as employee_name,
            ep.job_title,
            SUM(pe.base_salary + pe.allowances) as annual_gross,
            SUM(pe.deductions) as annual_deductions,
            SUM(pe.net_salary) as annual_net
            FROM `cod_payroll_entry` pe
            LEFT JOIN `cod_user` u ON (pe.user_id = u.user_id)
            LEFT JOIN `cod_employee_profile` ep ON (pe.user_id = ep.user_id)
            LEFT JOIN `cod_payroll_period` pp ON (pe.payroll_period_id = pp.payroll_period_id)
            WHERE YEAR(pp.start_date) = '" . (int)$year . "'
            GROUP BY pe.user_id
            ORDER BY u.firstname, u.lastname");

        return $query->rows;
    }

    // تحديث إعدادات الضرائب والاستقطاعات
    public function updateTaxSettings($tax_rate, $social_insurance_rate, $medical_insurance_rate) {
        $this->tax_rate = (float)$tax_rate;
        $this->social_insurance_rate = (float)$social_insurance_rate;
        $this->medical_insurance_rate = (float)$medical_insurance_rate;

        // حفظ الإعدادات في قاعدة البيانات
        $this->db->query("INSERT INTO `cod_setting` (store_id, `code`, `key`, `value`, serialized)
            VALUES (0, 'payroll', 'tax_rate', '" . $this->db->escape($tax_rate) . "', 0)
            ON DUPLICATE KEY UPDATE `value` = '" . $this->db->escape($tax_rate) . "'");

        $this->db->query("INSERT INTO `cod_setting` (store_id, `code`, `key`, `value`, serialized)
            VALUES (0, 'payroll', 'social_insurance_rate', '" . $this->db->escape($social_insurance_rate) . "', 0)
            ON DUPLICATE KEY UPDATE `value` = '" . $this->db->escape($social_insurance_rate) . "'");

        $this->db->query("INSERT INTO `cod_setting` (store_id, `code`, `key`, `value`, serialized)
            VALUES (0, 'payroll', 'medical_insurance_rate', '" . $this->db->escape($medical_insurance_rate) . "', 0)
            ON DUPLICATE KEY UPDATE `value` = '" . $this->db->escape($medical_insurance_rate) . "'");

        return true;
    }

    // جلب إعدادات الضرائب من قاعدة البيانات
    public function loadTaxSettings() {
        $tax_rate_query = $this->db->query("SELECT `value` FROM `cod_setting` WHERE `code` = 'payroll' AND `key` = 'tax_rate'");
        if ($tax_rate_query->num_rows) {
            $this->tax_rate = (float)$tax_rate_query->row['value'];
        }

        $social_insurance_query = $this->db->query("SELECT `value` FROM `cod_setting` WHERE `code` = 'payroll' AND `key` = 'social_insurance_rate'");
        if ($social_insurance_query->num_rows) {
            $this->social_insurance_rate = (float)$social_insurance_query->row['value'];
        }

        $medical_insurance_query = $this->db->query("SELECT `value` FROM `cod_setting` WHERE `code` = 'payroll' AND `key` = 'medical_insurance_rate'");
        if ($medical_insurance_query->num_rows) {
            $this->medical_insurance_rate = (float)$medical_insurance_query->row['value'];
        }
    }

    // إنشاء تقرير مقارنة الرواتب بين الفترات
    public function comparePayrollPeriods($period1_id, $period2_id) {
        $query = $this->db->query("SELECT
            CONCAT(u.firstname, ' ', u.lastname) as employee_name,
            ep.job_title,
            pe1.net_salary as period1_salary,
            pe2.net_salary as period2_salary,
            (pe2.net_salary - pe1.net_salary) as difference,
            ROUND(((pe2.net_salary - pe1.net_salary) / pe1.net_salary) * 100, 2) as percentage_change
            FROM `cod_payroll_entry` pe1
            LEFT JOIN `cod_payroll_entry` pe2 ON (pe1.user_id = pe2.user_id AND pe2.payroll_period_id = '" . (int)$period2_id . "')
            LEFT JOIN `cod_user` u ON (pe1.user_id = u.user_id)
            LEFT JOIN `cod_employee_profile` ep ON (pe1.user_id = ep.user_id)
            WHERE pe1.payroll_period_id = '" . (int)$period1_id . "'
            AND pe2.payroll_entry_id IS NOT NULL
            ORDER BY u.firstname, u.lastname");

        return $query->rows;
    }

    // حساب إجمالي تكلفة الرواتب الشهرية
    public function getMonthlyPayrollCost($year, $month) {
        $query = $this->db->query("SELECT
            SUM(pe.base_salary) as total_base_salary,
            SUM(pe.allowances) as total_allowances,
            SUM(pe.deductions) as total_deductions,
            SUM(pe.net_salary) as total_net_salary,
            COUNT(pe.payroll_entry_id) as total_employees
            FROM `cod_payroll_entry` pe
            LEFT JOIN `cod_payroll_period` pp ON (pe.payroll_period_id = pp.payroll_period_id)
            WHERE YEAR(pp.start_date) = '" . (int)$year . "'
            AND MONTH(pp.start_date) = '" . (int)$month . "'");

        return $query->row;
    }

    // إنشاء تقرير الموظفين الأعلى راتباً
    public function getTopEarners($limit = 10, $period_id = null) {
        $sql = "SELECT
            CONCAT(u.firstname, ' ', u.lastname) as employee_name,
            ep.job_title,
            pe.net_salary,
            pp.period_name
            FROM `cod_payroll_entry` pe
            LEFT JOIN `cod_user` u ON (pe.user_id = u.user_id)
            LEFT JOIN `cod_employee_profile` ep ON (pe.user_id = ep.user_id)
            LEFT JOIN `cod_payroll_period` pp ON (pe.payroll_period_id = pp.payroll_period_id)
            WHERE 1";

        if ($period_id) {
            $sql .= " AND pe.payroll_period_id = '" . (int)$period_id . "'";
        }

        $sql .= " ORDER BY pe.net_salary DESC LIMIT " . (int)$limit;

        $query = $this->db->query($sql);
        return $query->rows;
    }

    // تحديث حالة دفع متعددة
    public function bulkUpdatePaymentStatus($entry_ids, $status) {
        if (empty($entry_ids) || !in_array($status, ['pending', 'paid'])) {
            return false;
        }

        $ids = array_map('intval', $entry_ids);
        $ids_string = implode(',', $ids);

        $this->db->query("UPDATE `cod_payroll_entry` SET
            payment_status = '" . $this->db->escape($status) . "',
            payment_date = " . ($status == 'paid' ? 'NOW()' : 'NULL') . "
            WHERE payroll_entry_id IN (" . $ids_string . ")");

        return $this->db->countAffected();
    }

    // إنشاء تقرير مفصل للرواتب
    public function generateDetailedReport($period_id) {
        $period_info = $this->getPayrollPeriodById($period_id);

        if (!$period_info) {
            return false;
        }

        $query = $this->db->query("
            SELECT
                e.firstname, e.lastname, e.job_title, e.employee_id,
                pi.base_salary, pi.allowances, pi.deductions, pi.net_salary,
                pi.payment_status, pi.payment_date, pi.overtime_hours,
                pi.work_days, pi.attendance_days
            FROM " . DB_PREFIX . "payment_invoice pi
            LEFT JOIN " . DB_PREFIX . "employee e ON pi.employee_id = e.employee_id
            WHERE pi.payroll_period_id = '" . (int)$period_id . "'
            ORDER BY e.lastname, e.firstname
        ");

        $entries = array();
        $totals = array(
            'total_base' => 0,
            'total_allowances' => 0,
            'total_deductions' => 0,
            'total_net' => 0,
            'total_employees' => 0
        );

        foreach ($query->rows as $row) {
            $entries[] = array(
                'employee_name' => $row['firstname'] . ' ' . $row['lastname'],
                'job_title' => $row['job_title'],
                'base_salary' => $row['base_salary'],
                'allowances' => $row['allowances'],
                'deductions' => $row['deductions'],
                'net_salary' => $row['net_salary'],
                'payment_status' => $row['payment_status'],
                'payment_date' => $row['payment_date'],
                'overtime_hours' => $row['overtime_hours'],
                'work_days' => $row['work_days'],
                'attendance_days' => $row['attendance_days']
            );

            $totals['total_base'] += $row['base_salary'];
            $totals['total_allowances'] += $row['allowances'];
            $totals['total_deductions'] += $row['deductions'];
            $totals['total_net'] += $row['net_salary'];
            $totals['total_employees']++;
        }

        return array(
            'period_info' => $period_info,
            'entries' => $entries,
            'totals' => $totals,
            'generated_at' => date('Y-m-d H:i:s')
        );
    }

    // الحصول على التكاليف الشهرية
    public function getMonthlyCosts($year) {
        $query = $this->db->query("
            SELECT
                MONTH(pp.start_date) as month,
                MONTHNAME(pp.start_date) as month_name,
                COUNT(pi.payment_invoice_id) as employee_count,
                SUM(pi.base_salary) as total_base_salary,
                SUM(pi.allowances) as total_allowances,
                SUM(pi.deductions) as total_deductions,
                SUM(pi.net_salary) as total_net_salary,
                AVG(pi.net_salary) as average_net_salary
            FROM " . DB_PREFIX . "payroll_period pp
            LEFT JOIN " . DB_PREFIX . "payment_invoice pi ON pp.payroll_period_id = pi.payroll_period_id
            WHERE YEAR(pp.start_date) = '" . (int)$year . "'
            GROUP BY MONTH(pp.start_date)
            ORDER BY MONTH(pp.start_date)
        ");

        return $query->rows;
    }

    // حساب راتب موظف مخصص
    public function calculateEmployeeSalary($employee_id, $period_id, $custom_data = array()) {
        // الحصول على معلومات الموظف
        $employee_query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "employee
            WHERE employee_id = '" . (int)$employee_id . "'
        ");

        if (!$employee_query->num_rows) {
            return false;
        }

        $employee = $employee_query->row;

        // الحصول على معلومات الفترة
        $period_info = $this->getPayrollPeriodById($period_id);
        if (!$period_info) {
            return false;
        }

        // الراتب الأساسي
        $base_salary = isset($custom_data['base_salary']) ?
            (float)$custom_data['base_salary'] : (float)$employee['salary'];

        // حساب أيام العمل والحضور
        $work_days = isset($custom_data['work_days']) ?
            (int)$custom_data['work_days'] : $this->calculateWorkDays($period_info['start_date'], $period_info['end_date']);

        $attendance_days = isset($custom_data['attendance_days']) ?
            (int)$custom_data['attendance_days'] : $this->getEmployeeAttendanceDays($employee_id, $period_info['start_date'], $period_info['end_date']);

        // حساب الراتب بناءً على الحضور
        $attendance_ratio = $work_days > 0 ? ($attendance_days / $work_days) : 1;
        $adjusted_base_salary = $base_salary * $attendance_ratio;

        // حساب البدلات
        $allowances = 0;
        if (isset($custom_data['allowances'])) {
            foreach ($custom_data['allowances'] as $allowance) {
                $allowances += (float)$allowance['amount'];
            }
        } else {
            // البدلات الافتراضية
            $allowances += $this->calculateTransportAllowance($employee);
            $allowances += $this->calculateMealAllowance($employee);
            $allowances += $this->calculatePhoneAllowance($employee);
        }

        // حساب العمل الإضافي
        $overtime_hours = isset($custom_data['overtime_hours']) ?
            (float)$custom_data['overtime_hours'] : 0;
        $overtime_pay = $this->calculateOvertimePay($base_salary, $overtime_hours);

        // إجمالي الراتب قبل الاستقطاعات
        $gross_salary = $adjusted_base_salary + $allowances + $overtime_pay;

        // حساب الاستقطاعات
        $deductions = 0;

        // ضريبة الدخل
        $income_tax = $gross_salary * $this->tax_rate;
        $deductions += $income_tax;

        // التأمينات الاجتماعية
        $social_insurance = $gross_salary * $this->social_insurance_rate;
        $deductions += $social_insurance;

        // التأمين الطبي
        $medical_insurance = $gross_salary * $this->medical_insurance_rate;
        $deductions += $medical_insurance;

        // استقطاعات إضافية
        if (isset($custom_data['additional_deductions'])) {
            foreach ($custom_data['additional_deductions'] as $deduction) {
                $deductions += (float)$deduction['amount'];
            }
        }

        // صافي الراتب
        $net_salary = $gross_salary - $deductions;

        return array(
            'employee_id' => $employee_id,
            'employee_name' => $employee['firstname'] . ' ' . $employee['lastname'],
            'period_id' => $period_id,
            'base_salary' => $base_salary,
            'adjusted_base_salary' => $adjusted_base_salary,
            'allowances' => $allowances,
            'overtime_pay' => $overtime_pay,
            'gross_salary' => $gross_salary,
            'income_tax' => $income_tax,
            'social_insurance' => $social_insurance,
            'medical_insurance' => $medical_insurance,
            'total_deductions' => $deductions,
            'net_salary' => $net_salary,
            'work_days' => $work_days,
            'attendance_days' => $attendance_days,
            'attendance_ratio' => $attendance_ratio,
            'overtime_hours' => $overtime_hours
        );
    }

    // حساب أيام العمل في فترة
    private function calculateWorkDays($start_date, $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $work_days = 0;

        while ($start <= $end) {
            // تجاهل الجمعة والسبت (عطلة نهاية الأسبوع)
            if ($start->format('N') < 6) {
                $work_days++;
            }
            $start->add(new DateInterval('P1D'));
        }

        return $work_days;
    }

    // الحصول على أيام حضور الموظف
    private function getEmployeeAttendanceDays($employee_id, $start_date, $end_date) {
        $query = $this->db->query("
            SELECT COUNT(DISTINCT DATE(check_in_time)) as attendance_days
            FROM " . DB_PREFIX . "attendance
            WHERE employee_id = '" . (int)$employee_id . "'
            AND DATE(check_in_time) BETWEEN '" . $this->db->escape($start_date) . "'
            AND '" . $this->db->escape($end_date) . "'
            AND status = 'present'
        ");

        return $query->num_rows ? (int)$query->row['attendance_days'] : 0;
    }

    // حساب بدل المواصلات
    private function calculateTransportAllowance($employee) {
        // يمكن تخصيص هذا حسب سياسة الشركة
        return isset($employee['transport_allowance']) ? (float)$employee['transport_allowance'] : 200.00;
    }

    // حساب بدل الوجبات
    private function calculateMealAllowance($employee) {
        return isset($employee['meal_allowance']) ? (float)$employee['meal_allowance'] : 150.00;
    }

    // حساب بدل الهاتف
    private function calculatePhoneAllowance($employee) {
        return isset($employee['phone_allowance']) ? (float)$employee['phone_allowance'] : 100.00;
    }

    // حساب أجر العمل الإضافي
    private function calculateOvertimePay($base_salary, $overtime_hours) {
        if ($overtime_hours <= 0) {
            return 0;
        }

        // حساب الأجر بالساعة (بناءً على 30 يوم عمل، 8 ساعات يومياً)
        $hourly_rate = $base_salary / (30 * 8);

        // أجر العمل الإضافي = 1.5 × الأجر العادي
        $overtime_rate = $hourly_rate * 1.5;

        return $overtime_hours * $overtime_rate;
    }

    // الحصول على سجل راتب موظف
    public function getPayrollEntryById($entry_id) {
        $query = $this->db->query("
            SELECT pi.*, pp.period_name, pp.start_date, pp.end_date,
                   e.firstname, e.lastname, e.job_title, e.email
            FROM " . DB_PREFIX . "payment_invoice pi
            LEFT JOIN " . DB_PREFIX . "payroll_period pp ON pi.payroll_period_id = pp.payroll_period_id
            LEFT JOIN " . DB_PREFIX . "employee e ON pi.employee_id = e.employee_id
            WHERE pi.payment_invoice_id = '" . (int)$entry_id . "'
        ");

        return $query->num_rows ? $query->row : false;
    }

    // تحديث سجل راتب موظف
    public function updatePayrollEntry($entry_id, $data) {
        $sql = "UPDATE " . DB_PREFIX . "payment_invoice SET ";
        $updates = array();

        if (isset($data['base_salary'])) {
            $updates[] = "base_salary = '" . (float)$data['base_salary'] . "'";
        }
        if (isset($data['allowances'])) {
            $updates[] = "allowances = '" . (float)$data['allowances'] . "'";
        }
        if (isset($data['deductions'])) {
            $updates[] = "deductions = '" . (float)$data['deductions'] . "'";
        }
        if (isset($data['net_salary'])) {
            $updates[] = "net_salary = '" . (float)$data['net_salary'] . "'";
        }
        if (isset($data['payment_status'])) {
            $updates[] = "payment_status = '" . $this->db->escape($data['payment_status']) . "'";
        }
        if (isset($data['overtime_hours'])) {
            $updates[] = "overtime_hours = '" . (float)$data['overtime_hours'] . "'";
        }

        if (empty($updates)) {
            return false;
        }

        $sql .= implode(', ', $updates);
        $sql .= " WHERE payment_invoice_id = '" . (int)$entry_id . "'";

        $this->db->query($sql);

        return $this->db->countAffected() > 0;
    }

    // حذف سجل راتب موظف
    public function deletePayrollEntry($entry_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "payment_invoice WHERE payment_invoice_id = '" . (int)$entry_id . "'");
        return $this->db->countAffected() > 0;
    }

    // البحث المتقدم في سجلات الرواتب
    public function searchPayrollEntries($search_data) {
        $sql = "
            SELECT pi.*, pp.period_name, e.firstname, e.lastname, e.job_title
            FROM " . DB_PREFIX . "payment_invoice pi
            LEFT JOIN " . DB_PREFIX . "payroll_period pp ON pi.payroll_period_id = pp.payroll_period_id
            LEFT JOIN " . DB_PREFIX . "employee e ON pi.employee_id = e.employee_id
            WHERE 1=1
        ";

        if (!empty($search_data['employee_name'])) {
            $sql .= " AND CONCAT(e.firstname, ' ', e.lastname) LIKE '%" . $this->db->escape($search_data['employee_name']) . "%'";
        }

        if (!empty($search_data['period_id'])) {
            $sql .= " AND pi.payroll_period_id = '" . (int)$search_data['period_id'] . "'";
        }

        if (!empty($search_data['payment_status'])) {
            $sql .= " AND pi.payment_status = '" . $this->db->escape($search_data['payment_status']) . "'";
        }

        if (!empty($search_data['min_salary'])) {
            $sql .= " AND pi.net_salary >= '" . (float)$search_data['min_salary'] . "'";
        }

        if (!empty($search_data['max_salary'])) {
            $sql .= " AND pi.net_salary <= '" . (float)$search_data['max_salary'] . "'";
        }

        $sql .= " ORDER BY e.lastname, e.firstname";

        $query = $this->db->query($sql);

        $results = array();
        foreach ($query->rows as $row) {
            $results[] = array(
                'employee_name' => $row['firstname'] . ' ' . $row['lastname'],
                'job_title' => $row['job_title'],
                'period_name' => $row['period_name'],
                'base_salary' => $row['base_salary'],
                'allowances' => $row['allowances'],
                'deductions' => $row['deductions'],
                'net_salary' => $row['net_salary'],
                'payment_status' => $row['payment_status']
            );
        }

        return $results;
    }

    // تحليل اتجاهات الرواتب
    public function analyzePayrollTrends($year) {
        $query = $this->db->query("
            SELECT
                MONTH(pp.start_date) as month,
                COUNT(pi.payment_invoice_id) as employee_count,
                AVG(pi.net_salary) as average_salary,
                SUM(pi.net_salary) as total_cost,
                MIN(pi.net_salary) as min_salary,
                MAX(pi.net_salary) as max_salary
            FROM " . DB_PREFIX . "payroll_period pp
            LEFT JOIN " . DB_PREFIX . "payment_invoice pi ON pp.payroll_period_id = pi.payroll_period_id
            WHERE YEAR(pp.start_date) = '" . (int)$year . "'
            GROUP BY MONTH(pp.start_date)
            ORDER BY MONTH(pp.start_date)
        ");

        $trends = array();
        $previous_month = null;

        foreach ($query->rows as $row) {
            $trend_data = array(
                'month' => $row['month'],
                'employee_count' => $row['employee_count'],
                'average_salary' => $row['average_salary'],
                'total_cost' => $row['total_cost'],
                'min_salary' => $row['min_salary'],
                'max_salary' => $row['max_salary']
            );

            // حساب التغيير من الشهر السابق
            if ($previous_month) {
                $trend_data['employee_change'] = $row['employee_count'] - $previous_month['employee_count'];
                $trend_data['cost_change'] = $row['total_cost'] - $previous_month['total_cost'];
                $trend_data['avg_salary_change'] = $row['average_salary'] - $previous_month['average_salary'];

                // النسب المئوية
                $trend_data['employee_change_percent'] = $previous_month['employee_count'] > 0 ?
                    (($trend_data['employee_change'] / $previous_month['employee_count']) * 100) : 0;
                $trend_data['cost_change_percent'] = $previous_month['total_cost'] > 0 ?
                    (($trend_data['cost_change'] / $previous_month['total_cost']) * 100) : 0;
                $trend_data['avg_salary_change_percent'] = $previous_month['average_salary'] > 0 ?
                    (($trend_data['avg_salary_change'] / $previous_month['average_salary']) * 100) : 0;
            }

            $trends[] = $trend_data;
            $previous_month = $row;
        }

        return $trends;
    }

    // تقرير توزيع الرواتب
    public function getSalaryDistribution($period_id) {
        $query = $this->db->query("
            SELECT
                CASE
                    WHEN pi.net_salary < 3000 THEN 'أقل من 3000'
                    WHEN pi.net_salary BETWEEN 3000 AND 5000 THEN '3000 - 5000'
                    WHEN pi.net_salary BETWEEN 5001 AND 8000 THEN '5001 - 8000'
                    WHEN pi.net_salary BETWEEN 8001 AND 12000 THEN '8001 - 12000'
                    WHEN pi.net_salary BETWEEN 12001 AND 20000 THEN '12001 - 20000'
                    ELSE 'أكثر من 20000'
                END as salary_range,
                COUNT(*) as employee_count,
                AVG(pi.net_salary) as average_salary,
                SUM(pi.net_salary) as total_salary
            FROM " . DB_PREFIX . "payment_invoice pi
            WHERE pi.payroll_period_id = '" . (int)$period_id . "'
            GROUP BY salary_range
            ORDER BY MIN(pi.net_salary)
        ");

        return $query->rows;
    }

    // تقرير الاستقطاعات المفصل
    public function getDeductionsReport($period_id) {
        $query = $this->db->query("
            SELECT
                e.firstname, e.lastname, e.job_title,
                pi.base_salary, pi.allowances, pi.deductions, pi.net_salary,
                (pi.base_salary + pi.allowances) as gross_salary,
                ROUND((pi.deductions / (pi.base_salary + pi.allowances)) * 100, 2) as deduction_percentage
            FROM " . DB_PREFIX . "payment_invoice pi
            LEFT JOIN " . DB_PREFIX . "employee e ON pi.employee_id = e.employee_id
            WHERE pi.payroll_period_id = '" . (int)$period_id . "'
            ORDER BY deduction_percentage DESC
        ");

        return $query->rows;
    }

    // تقرير العمل الإضافي
    public function getOvertimeReport($period_id) {
        $query = $this->db->query("
            SELECT
                e.firstname, e.lastname, e.job_title,
                pi.overtime_hours, pi.base_salary,
                ROUND((pi.base_salary / (30 * 8)) * 1.5 * pi.overtime_hours, 2) as overtime_pay
            FROM " . DB_PREFIX . "payment_invoice pi
            LEFT JOIN " . DB_PREFIX . "employee e ON pi.employee_id = e.employee_id
            WHERE pi.payroll_period_id = '" . (int)$period_id . "'
            AND pi.overtime_hours > 0
            ORDER BY pi.overtime_hours DESC
        ");

        return $query->rows;
    }

    // إحصائيات الحضور والرواتب
    public function getAttendancePayrollStats($period_id) {
        $query = $this->db->query("
            SELECT
                e.firstname, e.lastname,
                pi.work_days, pi.attendance_days,
                ROUND((pi.attendance_days / pi.work_days) * 100, 2) as attendance_percentage,
                pi.base_salary, pi.net_salary,
                ROUND((pi.attendance_days / pi.work_days) * pi.base_salary, 2) as adjusted_salary
            FROM " . DB_PREFIX . "payment_invoice pi
            LEFT JOIN " . DB_PREFIX . "employee e ON pi.employee_id = e.employee_id
            WHERE pi.payroll_period_id = '" . (int)$period_id . "'
            ORDER BY attendance_percentage DESC
        ");

        return $query->rows;
    }

}
